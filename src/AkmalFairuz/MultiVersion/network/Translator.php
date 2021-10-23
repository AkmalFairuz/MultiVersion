<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use AkmalFairuz\MultiVersion\network\translator\CraftingDataPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\PlayerListPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\StartGamePacketTranslator;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class Translator{

    public static function fromClient(DataPacket $packet, int $protocol) : DataPacket{
        // todo
        return $packet;
    }

    public static function fromServer(DataPacket $packet, int $protocol) : DataPacket {
        if($packet->isEncoded) {
            $packet->decode();
        }
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
                $packet->blockRuntimeId = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                return $packet;
            case CraftingDataPacket::NETWORK_ID:
                /** @var CraftingDataPacket $packet */
                CraftingDataPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case PlayerListPacket::NETWORK_ID:
                /** @var PlayerListPacket $packet */
                PlayerListPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case StartGamePacket::NETWORK_ID:
                /** @var StartGamePacket $packet */
                StartGamePacketTranslator::serialize($packet, $protocol);
                return $packet;
        }
        return $packet;
    }
}