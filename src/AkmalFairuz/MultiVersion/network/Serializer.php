<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionItemTranslator;
use AkmalFairuz\MultiVersion\network\convert\MultiVersionItemTypeDictionary;
use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\block\BlockIds;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use function count;

class Serializer{

    public static function putSkin(SkinData $skin, DataPacket $packet, int $protocol){
        $packet->putString($skin->getSkinId());
        $packet->putString($skin->getPlayFabId());
        $packet->putString($skin->getResourcePatch());
        self::putSkinImage($skin->getSkinImage(), $packet);
        $packet->putLInt(count($skin->getAnimations()));
        foreach($skin->getAnimations() as $animation){
            self::putSkinImage($animation->getImage(), $packet);
            $packet->putLInt($animation->getType());
            $packet->putLFloat($animation->getFrames());
            $packet->putLInt($animation->getExpressionType());
        }
        self::putSkinImage($skin->getCapeImage(), $packet);
        $packet->putString($skin->getGeometryData());
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $packet->putString($skin->getGeometryDataEngineVersion());
        }
        $packet->putString($skin->getAnimationData());
        if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
            $packet->putBool($skin->isPremium());
            $packet->putBool($skin->isPersona());
            $packet->putBool($skin->isPersonaCapeOnClassic());
        }
        $packet->putString($skin->getCapeId());
        $packet->putString($skin->getFullSkinId());
        $packet->putString($skin->getArmSize());
        $packet->putString($skin->getSkinColor());
        $packet->putLInt(count($skin->getPersonaPieces()));
        foreach($skin->getPersonaPieces() as $piece){
            $packet->putString($piece->getPieceId());
            $packet->putString($piece->getPieceType());
            $packet->putString($piece->getPackId());
            $packet->putBool($piece->isDefaultPiece());
            $packet->putString($piece->getProductId());
        }
        $packet->putLInt(count($skin->getPieceTintColors()));
        foreach($skin->getPieceTintColors() as $tint){
            $packet->putString($tint->getPieceType());
            $packet->putLInt(count($tint->getColors()));
            foreach($tint->getColors() as $color){
                $packet->putString($color);
            }
        }
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $packet->putBool($skin->isPremium());
            $packet->putBool($skin->isPersona());
            $packet->putBool($skin->isPersonaCapeOnClassic());
            $packet->putBool($skin->isPrimaryUser());
        }
    }

    public static function getSkin(DataPacket $packet, int $protocol) : SkinData{
        $skinId = $packet->getString();
        $skinPlayFabId = $packet->getString();
        $skinResourcePatch = $packet->getString();
        $skinData = self::getSkinImage($packet);
        $animationCount = $packet->getLInt();
        $animations = [];
        for($i = 0; $i < $animationCount; ++$i){
            $skinImage = self::getSkinImage($packet);
            $animationType = $packet->getLInt();
            $animationFrames = $packet->getLFloat();
            $expressionType = $packet->getLInt();
            $animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
        }
        $capeData = self::getSkinImage($packet);
        $geometryData = $packet->getString();
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $geometryDataVersion = $packet->getString();
        }
        $animationData = $packet->getString();
        if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
            $premium = $packet->getBool();
            $persona = $packet->getBool();
            $capeOnClassic = $packet->getBool();
        }
        $capeId = $packet->getString();
        $fullSkinId = $packet->getString();
        $armSize = $packet->getString();
        $skinColor = $packet->getString();
        $personaPieceCount = $packet->getLInt();
        $personaPieces = [];
        for($i = 0; $i < $personaPieceCount; ++$i){
            $pieceId = $packet->getString();
            $pieceType = $packet->getString();
            $packId = $packet->getString();
            $isDefaultPiece = $packet->getBool();
            $productId = $packet->getString();
            $personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
        }
        $pieceTintColorCount = $packet->getLInt();
        $pieceTintColors = [];
        for($i = 0; $i < $pieceTintColorCount; ++$i){
            $pieceType = $packet->getString();
            $colorCount = $packet->getLInt();
            $colors = [];
            for($j = 0; $j < $colorCount; ++$j){
                $colors[] = $packet->getString();
            }
            $pieceTintColors[] = new PersonaPieceTintColor(
                $pieceType,
                $colors
            );
        }
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $premium = $packet->getBool();
            $persona = $packet->getBool();
            $capeOnClassic = $packet->getBool();
            $isPrimaryUser = $packet->getBool();
        }

        return new SkinData($skinId, $skinPlayFabId, $skinResourcePatch, $skinData, $animations, $capeData, $geometryData, $geometryDataVersion ?? "1.17.30", $animationData, $capeId, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors, true, $premium ?? false, $persona ?? false, $capeOnClassic ?? false, $isPrimaryUser ?? true);
    }

    public static function putSkinImage(SkinImage $image, DataPacket $packet) : void{
        ($packet->buffer .= (\pack("V", $image->getWidth())));
        ($packet->buffer .= (\pack("V", $image->getHeight())));
        $packet->putString($image->getData());
    }

    public static function getSkinImage(DataPacket $packet) : SkinImage{
        $width = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
        $height = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
        $data = $packet->getString();
        return new SkinImage($height, $width, $data);
    }

    public static function putItemStack(NetworkBinaryStream $packet, int $protocol, Item $item, callable $writeExtraCrapInTheMiddle) {
        if($item->getId() === 0){
            $packet->putVarInt(0);

            return;
        }

        $coreData = $item->getDamage();
        [$netId, $netData] = MultiVersionItemTranslator::getInstance()->toNetworkId($item->getId(), $coreData, $protocol);

        $packet->putVarInt($netId);
        ($packet->buffer .= (\pack("v", $item->getCount())));
        $packet->putUnsignedVarInt($netData);

        $writeExtraCrapInTheMiddle($packet);

        $blockRuntimeId = 0;
        $isBlockItem = $item->getId() < 256;
        if($isBlockItem){
            $block = $item->getBlock();
            if($block->getId() !== BlockIds::AIR){
                $blockRuntimeId = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block->getId(), $block->getDamage(), $protocol);
            }
        }
        $packet->putVarInt($blockRuntimeId);

        $nbt = null;
        if($item->hasCompoundTag()){
            $nbt = clone $item->getNamedTag();
        }
        if($item instanceof Durable and $coreData > 0){
            if($nbt !== null){
                if(($existing = $nbt->getTag("Damage")) !== null){
                    $nbt->removeTag("Damage");
                    $existing->setName("___Damage_ProtocolCollisionResolution___");
                    $nbt->setTag($existing);
                }
            }else{
                $nbt = new CompoundTag();
            }
            $nbt->setInt("Damage", $coreData);
        }elseif($isBlockItem && $coreData !== 0){
            //TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
            //client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
            //client-side. Aside from being very annoying, this also breaks various server-side behaviours.
            if($nbt === null){
                $nbt = new CompoundTag();
            }
            $nbt->setInt("___Meta___", $coreData);
        }

        $packet->putString(
            (static function() use ($protocol, $nbt, $netId) : string{
                $extraData = new NetworkBinaryStream();

                if($nbt !== null){
                    $extraData->putLShort(0xffff);
                    $extraData->putByte(1); //TODO: NBT data version (?)
                    $extraData->put((new LittleEndianNBTStream())->write($nbt));
                }else{
                    $extraData->putLShort(0);
                }

                $extraData->putLInt(0); //CanPlaceOn entry count (TODO)
                $extraData->putLInt(0); //CanDestroy entry count (TODO)

                if($netId === MultiVersionItemTypeDictionary::getInstance()->fromStringId("minecraft:shield", $protocol)){
                    $extraData->putLLong(0); //"blocking tick" (ffs mojang)
                }
                return $extraData->getBuffer();
            })());
    }

    public static function putItem(NetworkBinaryStream $packet, int $protocol, Item $item, int $stackId) {
        self::putItemStack($packet, $protocol, $item, function(NetworkBinaryStream $out) use ($stackId){
            $out->putBool($stackId !== 0);
            if($stackId !== 0) {
                $out->putVarInt($stackId);
            }
        });
    }

    public static function putRecipeIngredient(NetworkBinaryStream $packet, Item $item, int $protocol) {
        if($item->isNull()){
            $packet->putVarInt(0);
        }else{
            if($item->hasAnyDamageValue()){
                [$netId, ] = MultiVersionItemTranslator::getInstance()->toNetworkId($item->getId(), 0, $protocol);
                $netData = 0x7fff;
            }else{
                [$netId, $netData] = MultiVersionItemTranslator::getInstance()->toNetworkId($item->getId(), $item->getDamage(), $protocol);
            }
            $packet->putVarInt($netId);
            $packet->putVarInt($netData);
            $packet->putVarInt($item->getCount());
        }
    }

    public static function putItemStackWithoutStackId(NetworkBinaryStream $packet, Item $item, int $protocol) : void{
        self::putItemStack($packet, $protocol, $item, function() : void{});
    }

    public static function getItemStack(DataPacket $packet, \Closure $readExtraCrapInTheMiddle, int $protocol) : Item{
        $netId = $packet->getVarInt();
        if($netId === 0){
            return ItemFactory::get(0, 0, 0);
        }

        $cnt = $packet->getLShort();
        $netData = $packet->getUnsignedVarInt();

        $null = null;
        [$id, $meta] = MultiVersionItemTranslator::getInstance()->fromNetworkId($netId, $netData, $null, $protocol);

        $readExtraCrapInTheMiddle($packet);

        $packet->getVarInt();

        $extraData = new NetworkBinaryStream($packet->getString());
        return (static function() use ($protocol, $extraData, $netId, $id, $meta, $cnt) : Item{
            $nbtLen = $extraData->getLShort();

            /** @var CompoundTag|null $nbt */
            $nbt = null;
            if($nbtLen === 0xffff){
                $nbtDataVersion = $extraData->getByte();
                if($nbtDataVersion !== 1){
                    throw new \UnexpectedValueException("Unexpected NBT data version $nbtDataVersion");
                }
                $decodedNBT = (new LittleEndianNBTStream())->read($extraData->buffer, false, $extraData->offset, 512);
                if(!($decodedNBT instanceof CompoundTag)){
                    throw new \UnexpectedValueException("Unexpected root tag type for itemstack");
                }
                $nbt = $decodedNBT;
            }elseif($nbtLen !== 0){
                throw new \UnexpectedValueException("Unexpected fake NBT length $nbtLen");
            }

            //TODO
            for($i = 0, $canPlaceOn = $extraData->getLInt(); $i < $canPlaceOn; ++$i){
                $extraData->get($extraData->getLShort());
            }

            //TODO
            for($i = 0, $canDestroy = $extraData->getLInt(); $i < $canDestroy; ++$i){
                $extraData->get($extraData->getLShort());
            }

            if($netId === MultiVersionItemTypeDictionary::getInstance()->fromStringId("minecraft:shield", $protocol)){
                $extraData->getLLong(); //"blocking tick" (ffs mojang)
            }

            if(!$extraData->feof()){
                throw new \UnexpectedValueException("Unexpected trailing extradata for network item $netId");
            }

            if($nbt !== null){
                if($nbt->hasTag("Damage", IntTag::class)){
                    $meta = $nbt->getInt("Damage");
                    $nbt->removeTag("Damage");
                    if(($conflicted = $nbt->getTag("___Damage_ProtocolCollisionResolution___")) !== null){
                        $nbt->removeTag("___Damage_ProtocolCollisionResolution___");
                        $conflicted->setName("Damage");
                        $nbt->setTag($conflicted);
                    }elseif($nbt->count() === 0){
                        $nbt = null;
                    }
                }elseif(($metaTag = $nbt->getTag("___Meta___")) instanceof IntTag){
                    //TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
                    //client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
                    //client-side. Aside from being very annoying, this also breaks various server-side behaviours.
                    $meta = $metaTag->getValue();
                    $nbt->removeTag("___Meta___");
                    if($nbt->count() === 0){
                        $nbt = null;
                    }
                }
            }
            return ItemFactory::get($id, $meta, $cnt, $nbt);
        })();
    }

    public static function getItemStackWrapper(DataPacket $packet, int $protocol): ItemStackWrapper{
        $stackId = 0;
        $stack = self::getItemStack($packet, function(NetworkBinaryStream $in) use (&$stackId) : void{
            $hasNetId = $in->getBool();
            if($hasNetId){
                $stackId = $in->readGenericTypeNetworkId();
            }
        }, $protocol);
        return new ItemStackWrapper($stackId, $stack);
    }

    public static function putEntityLink(DataPacket $packet, EntityLink $link) {
        $packet->putEntityUniqueId($link->fromEntityUniqueId);
        $packet->putEntityUniqueId($link->toEntityUniqueId);
        ($packet->buffer .= \chr($link->type));
        ($packet->buffer .= ($link->immediate ? "\x01" : "\x00"));
        ($packet->buffer .= ($link->causedByRider ? "\x01" : "\x00"));
    }

    public static function putGameRules(DataPacket $packet, array $rules, int $protocol){
        $packet->putUnsignedVarInt(count($rules));
        foreach($rules as $name => $rule){
            $packet->putString($name);
            if($protocol >= ProtocolConstants::BEDROCK_1_17_0){
                $packet->putBool($rule[2]);
            }
            $packet->putUnsignedVarInt($rule[0]);
            switch($rule[0]){
                case GameRuleType::BOOL:
                    $packet->putBool($rule[1]);
                    break;
                case GameRuleType::INT:
                    $packet->putUnsignedVarInt($rule[1]);
                    break;
                case GameRuleType::FLOAT:
                    $packet->putLFloat($rule[1]);
                    break;
            }
        }
    }

}