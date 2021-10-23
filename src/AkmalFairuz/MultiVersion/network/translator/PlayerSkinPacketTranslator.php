<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;

class PlayerSkinPacketTranslator{

    public static function serialize(PlayerSkinPacket $packet, int $protocol) {
        $packet->putUUID($packet->uuid);
        Serializer::putSkin($packet->skin, $packet, $protocol);
        $packet->putString($packet->newSkinName);
        $packet->putString($packet->oldSkinName);
        ($packet->buffer .= ($packet->skin->isVerified() ? "\x01" : "\x00"));
    }
}