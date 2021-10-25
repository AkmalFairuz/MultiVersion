<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;

class AddItemActorPacketTranslator{

    public static function serialize(AddItemActorPacket $packet, int $protocol) {
        $packet->putEntityUniqueId($packet->entityUniqueId ?? $packet->entityRuntimeId);
        $packet->putEntityRuntimeId($packet->entityRuntimeId);
        Serializer::putItem($packet, $protocol, $packet->item->getItemStack(), $packet->item->getStackId());
        $packet->putVector3($packet->position);
        $packet->putVector3Nullable($packet->motion);
        $packet->putEntityMetadata($packet->metadata);
        ($packet->buffer .= ($packet->isFromFishing ? "\x01" : "\x00"));
    }
}