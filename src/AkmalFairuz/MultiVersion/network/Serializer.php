<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\protocol\DataPacket;
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

}