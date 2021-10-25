<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use function count;

class AddPlayerPacketTranslator{

    public static function serialize(AddPlayerPacket $packet, int $protocol){
        $packet->putUUID($packet->uuid);
        $packet->putString($packet->username);
        $packet->putEntityUniqueId($packet->entityUniqueId ?? $packet->entityRuntimeId);
        $packet->putEntityRuntimeId($packet->entityRuntimeId);
        $packet->putString($packet->platformChatId);
        $packet->putVector3($packet->position);
        $packet->putVector3Nullable($packet->motion);
        ($packet->buffer .= (\pack("g", $packet->pitch)));
        ($packet->buffer .= (\pack("g", $packet->yaw)));
        ($packet->buffer .= (\pack("g", $packet->headYaw ?? $packet->yaw)));
        Serializer::putItem($packet, $protocol, $packet->item->getItemStack(), $packet->item->getStackId());
        $packet->putEntityMetadata($packet->metadata);

        $packet->putUnsignedVarInt($packet->uvarint1);
        $packet->putUnsignedVarInt($packet->uvarint2);
        $packet->putUnsignedVarInt($packet->uvarint3);
        $packet->putUnsignedVarInt($packet->uvarint4);
        $packet->putUnsignedVarInt($packet->uvarint5);

        ($packet->buffer .= (\pack("VV", $packet->long1 & 0xFFFFFFFF, $packet->long1 >> 32)));

        $packet->putUnsignedVarInt(count($packet->links));
        foreach($packet->links as $link){
            Serializer::putEntityLink($packet, $link);
        }

        $packet->putString($packet->deviceId);
        ($packet->buffer .= (\pack("V", $packet->buildPlatform)));
    }
}