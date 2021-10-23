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
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use function base64_encode;
use function var_dump;

class Translator{

    public static function fromClient(DataPacket $packet, int $protocol) : DataPacket{
        if($packet->isEncoded) {
            $packet->decode();
        }
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
                    $packet->clientData["SkinGeometryDataEngineVersion"] = "";
                }
                return $packet;
        }
        // todo
        return $packet;
    }

    public static function fromServer(DataPacket $packet, int $protocol) : DataPacket {
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
                $packet->blockRuntimeId = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                $packet->encode();
                return $packet;
            case CraftingDataPacket::NETWORK_ID:
                /** @var CraftingDataPacket $packet */
                self::encodeHeader($packet);
                CraftingDataPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case PlayerListPacket::NETWORK_ID:
                /** @var PlayerListPacket $packet */
                self::encodeHeader($packet);
                PlayerListPacketTranslator::serialize($packet, $protocol);
                return $packet;
            case StartGamePacket::NETWORK_ID:
                /** @var StartGamePacket $packet */
                self::encodeHeader($packet);
                StartGamePacketTranslator::serialize($packet, $protocol);
                return $packet;
        }
        return $packet;
    }

    public static function encodeHeader(DataPacket $packet) {
        $packet->reset();
        $packet->putUnsignedVarInt(
            $packet::NETWORK_ID |
            ($packet->senderSubId << 10) |
            ($packet->recipientSubId << 12)
        );
        $packet->isEncoded = true;
    }
}