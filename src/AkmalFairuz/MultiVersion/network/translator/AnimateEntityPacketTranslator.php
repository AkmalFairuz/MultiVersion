<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;

class AnimateEntityPacketTranslator{

    public static function serialize(AnimateEntityPacket $packet, int $protocol) {
        $packet->putString($packet->getAnimation());
        $packet->putString($packet->getNextState());
        $packet->putString($packet->getStopExpression());
        if($protocol >= ProtocolConstants::BEDROCK_1_17_30){
            $packet->putLInt($packet->getStopExpressionVersion());
        }
        $packet->putString($packet->getController());
        $packet->putLFloat($packet->getBlendOutTime());
        $packet->putUnsignedVarInt(count($packet->getActorRuntimeIds()));
        foreach($packet->getActorRuntimeIds() as $id){
            $packet->putEntityRuntimeId($id);
        }
    }
}