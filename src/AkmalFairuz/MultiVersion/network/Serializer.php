<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\block\BlockIds;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\types\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\utils\BinaryStream;
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
        [$netId, $netData] = ItemTranslator::getInstance()->toNetworkId($item->getId(), $coreData, $protocol);

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

                if($netId === ItemTypeDictionary::getInstance()->fromStringId("minecraft:shield", $protocol)){
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

}