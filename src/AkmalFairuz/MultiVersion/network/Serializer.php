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
        self::putSkinImage($skin->getCapeImage(), $packet);
        ($packet->buffer .= (\pack("V", count($skin->getAnimations()))));
        foreach($skin->getAnimations() as $animation){
            self::putSkinImage($skin->getCapeImage(), $packet);
            ($packet->buffer .= (\pack("V", $animation->getType())));
            ($packet->buffer .= (\pack("g", $animation->getFrames())));
            ($packet->buffer .= (\pack("V", $animation->getExpressionType())));
        }
        self::putSkinImage($skin->getCapeImage(), $packet);
        $packet->putString($skin->getGeometryData());
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30) {
            $packet->putString("1.17.30"); // SkinGeometryDataEngineVersion
        }
        $packet->putString($skin->getAnimationData());
        if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
            ($packet->buffer .= ($skin->isPremium() ? "\x01" : "\x00"));
            ($packet->buffer .= ($skin->isPersona() ? "\x01" : "\x00"));
            ($packet->buffer .= ($skin->isPersonaCapeOnClassic() ? "\x01" : "\x00"));
        }
        $packet->putString($skin->getCapeId());
        $packet->putString($skin->getFullSkinId());
        $packet->putString($skin->getArmSize());
        $packet->putString($skin->getSkinColor());
        ($packet->buffer .= (\pack("V", count($skin->getPersonaPieces()))));
        foreach($skin->getPersonaPieces() as $piece){
            $packet->putString($piece->getPieceId());
            $packet->putString($piece->getPieceType());
            $packet->putString($piece->getPackId());
            ($packet->buffer .= ($piece->isDefaultPiece() ? "\x01" : "\x00"));
            $packet->putString($piece->getProductId());
        }
        ($packet->buffer .= (\pack("V", count($skin->getPieceTintColors()))));
        foreach($skin->getPieceTintColors() as $tint){
            $packet->putString($tint->getPieceType());
            ($packet->buffer .= (\pack("V", count($tint->getColors()))));
            foreach($tint->getColors() as $color){
                $packet->putString($color);
            }
        }
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            ($packet->buffer .= ($skin->isPremium() ? "\x01" : "\x00"));
            ($packet->buffer .= ($skin->isPersona() ? "\x01" : "\x00"));
            ($packet->buffer .= ($skin->isPersonaCapeOnClassic() ? "\x01" : "\x00"));
            $packet->putByte(1); // IsPrimaryUser
        }
    }

    public function getSkin(DataPacket $packet, int $protocol) : SkinData{
        $skinId = $packet->getString();
        $skinPlayFabId = $packet->getString();
        $skinResourcePatch = $packet->getString();
        $skinData = self::getSkinImage($packet);
        $animationCount = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
        $animations = [];
        for($i = 0; $i < $animationCount; ++$i){
            $skinImage = self::getSkinImage($packet);
            $animationType = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
            $animationFrames = ((\unpack("g", $packet->get(4))[1]));
            $expressionType = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
            $animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
        }
        $capeData = self::getSkinImage($packet);
        $geometryData = $packet->getString();
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30) {
            $packet->getString(); // SkinGeometryDataEngineVersion
        }
        $animationData = $packet->getString();
        if($protocol < ProtocolConstants::BEDROCK_1_17_30){
            $premium = (($packet->get(1) !== "\x00"));
            $persona = (($packet->get(1) !== "\x00"));
            $capeOnClassic = (($packet->get(1) !== "\x00"));
        }
        $capeId = $packet->getString();
        $fullSkinId = $packet->getString();
        $armSize = $packet->getString();
        $skinColor = $packet->getString();
        $personaPieceCount = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
        $personaPieces = [];
        for($i = 0; $i < $personaPieceCount; ++$i){
            $pieceId = $packet->getString();
            $pieceType = $packet->getString();
            $packId = $packet->getString();
            $isDefaultPiece = (($packet->get(1) !== "\x00"));
            $productId = $packet->getString();
            $personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
        }
        $pieceTintColorCount = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
        $pieceTintColors = [];
        for($i = 0; $i < $pieceTintColorCount; ++$i){
            $pieceType = $packet->getString();
            $colorCount = ((\unpack("V", $packet->get(4))[1] << 32 >> 32));
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
            $premium = (($packet->get(1) !== "\x00"));
            $persona = (($packet->get(1) !== "\x00"));
            $capeOnClassic = (($packet->get(1) !== "\x00"));
            $packet->get(1); // IsPrimaryUser
        }
        return new SkinData($skinId, $skinPlayFabId, $skinResourcePatch, $skinData, $animations, $capeData, $geometryData, $animationData, $premium, $persona, $capeOnClassic, $capeId, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors);
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