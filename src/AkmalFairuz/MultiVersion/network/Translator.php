<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use AkmalFairuz\MultiVersion\network\translator\AnimateEntityPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\CraftingDataPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\PlayerListPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\PlayerSkinPacketTranslator;
use AkmalFairuz\MultiVersion\network\translator\StartGamePacketTranslator;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;

class Translator{

    public static function fromClient(DataPacket $packet, int $protocol, Player $player) : DataPacket{
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
                    $packet->clientData["SkinGeometryDataEngineVersion"] = "";
                }
                return $packet;
            case PlayerSkinPacket::NETWORK_ID:
                /** @var PlayerSkinPacket $packet */
                if($protocol < ProtocolConstants::BEDROCK_1_17_30) {
                    self::decodeHeader($packet);
                    PlayerSkinPacketTranslator::unserialize($packet, $protocol);
                }
                return $packet;
        }
        return $packet;
    }

    public static function fromServer(DataPacket $packet, int $protocol, Player $player) : DataPacket {
        $pid = $packet::NETWORK_ID;
        switch($pid) {
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
                $packet->blockRuntimeId = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                return $packet;
            case LevelEventPacket::NETWORK_ID:
                /** @var LevelEventPacket $packet */
                switch($packet->evid) {
                    case LevelEventPacket::EVENT_PARTICLE_DESTROY:
                        $block = RuntimeBlockMapping::fromStaticRuntimeId($packet->data);
                        $packet->data = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block[0], $block[1], $protocol);
                        break;
                    case LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK:
                        $position = $packet->position;
                        $block = $player->getLevel()->getBlock($position);
                        // todo, idk how to get face
                        $packet->data = MultiVersionRuntimeBlockMapping::toStaticRuntimeId($block->getId(), $block->getDamage(), $protocol) | (1 << 24);
                        break;
                }
                return $packet;
            case AnimateEntityPacket::NETWORK_ID:
                /** @var AnimateEntityPacket $packet */
                self::encodeHeader($packet);
                AnimateEntityPacketTranslator::serialize($packet, $protocol);
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
            case PlayerSkinPacket::NETWORK_ID:
                /** @var PlayerSkinPacket $packet */
                self::encodeHeader($packet);
                PlayerSkinPacketTranslator::serialize($packet, $protocol);
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

    public static function decodeHeader(DataPacket $packet) {
        $packet->isEncoded = false;
        $packet->offset = 0;
        $header = $packet->getUnsignedVarInt();
        $pid = $header & $packet::PID_MASK;
        if($pid !== $packet::NETWORK_ID){
            throw new \UnexpectedValueException("Expected " . $packet::NETWORK_ID . " for packet ID, got $pid");
        }
        $packet->senderSubId = ($header >> 10) & 0x03;
        $packet->recipientSubId = ($header >> 12) & 0x03;
    }
}