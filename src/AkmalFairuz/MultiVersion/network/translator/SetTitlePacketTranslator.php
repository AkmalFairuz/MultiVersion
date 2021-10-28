<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

class SetTitlePacketTranslator{

    public static function serialize(SetTitlePacket $packet, int $protocol) {
        $packet->putVarInt($packet->type);
        $packet->putString($packet->text);
        $packet->putVarInt($packet->fadeInTime);
        $packet->putVarInt($packet->stayTime);
        $packet->putVarInt($packet->fadeOutTime);
        if($protocol >= ProtocolConstants::BEDROCK_1_17_10){
            $packet->putString($packet->xuid);
            $packet->putString($packet->platformOnlineId);
        }
    }
}