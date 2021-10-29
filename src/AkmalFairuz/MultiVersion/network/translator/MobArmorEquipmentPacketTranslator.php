<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network\translator;

use AkmalFairuz\MultiVersion\network\Serializer;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class MobArmorEquipmentPacketTranslator{

    public static function serialize(MobArmorEquipmentPacket $packet, int $protocol) {
        $packet->putEntityRuntimeId($packet->entityRuntimeId);
        Serializer::putItem($packet, $protocol, $packet->head->getItemStack(), $packet->head->getStackId());
        Serializer::putItem($packet, $protocol, $packet->chest->getItemStack(), $packet->chest->getStackId());
        Serializer::putItem($packet, $protocol, $packet->legs->getItemStack(), $packet->legs->getStackId());
        Serializer::putItem($packet, $protocol, $packet->feet->getItemStack(), $packet->feet->getStackId());
    }

    public static function deserialize(MobArmorEquipmentPacket $packet, int $protocol) {
        $packet->entityRuntimeId = $packet->getEntityRuntimeId();
        $packet->head = Serializer::getItemStackWrapper($packet, $protocol);
        $packet->chest = Serializer::getItemStackWrapper($packet, $protocol);
        $packet->legs = Serializer::getItemStackWrapper($packet, $protocol);
        $packet->feet = Serializer::getItemStackWrapper($packet, $protocol);
    }
}