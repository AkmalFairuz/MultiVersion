<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\network\MultiVersionSessionAdapter;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\network\Translator;
use AkmalFairuz\MultiVersion\session\SessionManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function get_class;
use function in_array;

class EventListener implements Listener{

    /** @var bool */
    public $cancel_send = false; // prevent recursive call

    /**
     * @throws \ReflectionException
     * @priority NORMAL
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if($packet instanceof LoginPacket) {
            if(!in_array($packet->protocol, ProtocolConstants::SUPPORTED_PROTOCOLS, true)) {
                $player->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
                $player->close("", $player->getServer()->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$packet->protocol]), false);
                $event->setCancelled();
                return;
            }
            if($packet->protocol === ProtocolInfo::CURRENT_PROTOCOL) {
                return;
            }
            $reflection = new \ReflectionClass($player);
            $prop = $reflection->getProperty("sessionAdapter");
            $prop->setAccessible(true);
            $prop->setValue(new MultiVersionSessionAdapter($player->getServer(), $player, $packet->protocol));

            SessionManager::create($player, $packet->protocol);

            $packet = Translator::fromClient($packet, $packet->protocol);

            $packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
        }
    }

    /**
     * @param PlayerQuitEvent $event
     * @priority NORMAL
     */
    public function onPlayerQuit(PlayerQuitEvent $event) {
        SessionManager::remove($event->getPlayer());
    }

    /**
     * @param DataPacketSendEvent $event
     * @priority HIGHEST
     * @ignoreCancelled
     */
    public function onDataPacketSend(DataPacketSendEvent $event) {
        if($this->cancel_send) {
            return;
        }
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        $protocol = SessionManager::getProtocol($player);
        if($protocol === null) {
            return;
        }
        if($packet instanceof BatchPacket) {
            $packet->decode();

            $newPacket = new BatchPacket();
            foreach($packet->getPackets() as $buf){
                $pk = PacketPool::getPacket($buf);

                if(!$pk->canBeBatched()){
                    throw new \UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
                }
                $newPacket->addPacket(Translator::fromServer($pk, $protocol));
            }
            $newPacket->encode();

            $this->cancel_send = true;
            $player->sendDataPacket($newPacket, false, true);
            $this->cancel_send = false;
            $event->setCancelled();
        } else {
            $newPacket = Translator::fromServer($packet, $protocol);
            $this->cancel_send = true;
            $player->sendDataPacket($newPacket, false, true);
            $this->cancel_send = false;
        }

    }
}