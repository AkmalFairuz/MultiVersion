<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;

class MobEquipmentPacketTranslator{

    public static function serialize(MobEquipmentPacket $packet, int $protocol){
        $packet->putEntityRuntimeId($packet->entityRuntimeId);
        Serializer::putItem($packet, $protocol, $packet->item->getItemStack(), $packet->item->getStackId());
        ($packet->buffer .= \chr($packet->inventorySlot));
        ($packet->buffer .= \chr($packet->hotbarSlot));
        ($packet->buffer .= \chr($packet->windowId));
    }
}