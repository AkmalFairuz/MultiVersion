<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\network\MultiVersionSessionAdapter;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use AkmalFairuz\MultiVersion\network\Translator;
use AkmalFairuz\MultiVersion\session\SessionManager;
use AkmalFairuz\MultiVersion\task\CompressTask;
use AkmalFairuz\MultiVersion\task\DecompressTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\Server;
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
        if($packet instanceof PacketViolationWarningPacket) {
            Loader::getInstance()->getLogger()->info("PacketViolationWarningPacket packet=" . PacketPool::getPacketById($packet->getPacketId())->getName() . ",message=" . $packet->getMessage() . ",type=" . $packet->getType() . ",severity=" . $packet->getSeverity());
        }
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

            $protocol = $packet->protocol;
            $packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;

            $reflection = new \ReflectionClass($player);
            $prop = $reflection->getProperty("sessionAdapter");
            $prop->setAccessible(true);
            $prop->setValue($player, new MultiVersionSessionAdapter($player->getServer(), $player, $protocol));

            SessionManager::create($player, $protocol);

            Translator::fromClient($packet, $protocol, $player);
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
            if($packet->isEncoded){
                if(Config::get("async_batch_decompression")) {
                    $task = new DecompressTask($packet, function(BatchPacket $packet) use ($player, $protocol) {
                        $this->translateBatchPacketAndSend($packet, $player, $protocol);
                    });
                    Server::getInstance()->getAsyncPool()->submitTask($task);
                    $event->setCancelled();
                    return;
                }
                $packet->decode();
            }

            $this->translateBatchPacketAndSend($packet, $player, $protocol);
            $event->setCancelled();
        } else {
            if($packet->isEncoded){
                $packet->decode();
            }
            $newPacket = Translator::fromServer($packet, $protocol, $player);
            $this->cancel_send = true;
            $player->sendDataPacket($newPacket);
            $this->cancel_send = false;
            $event->setCancelled();
        }
    }

    private function translateBatchPacketAndSend(BatchPacket $packet, Player $player, int $protocol) {
        $newPacket = new BatchPacket();
        foreach($packet->getPackets() as $buf){
            $pk = PacketPool::getPacket($buf);
            $pk->decode();
            if(!$pk->canBeBatched()){
                throw new \UnexpectedValueException("Received invalid " . get_class($pk) . " inside BatchPacket");
            }
            $translated = Translator::fromServer($pk, $protocol, $player);
            if($translated === null) {
                continue;
            }
            $newPacket->addPacket($translated);
        }
        if(Config::get("async_batch_compression")){
            $task = new CompressTask($newPacket, function(BatchPacket $packet) use ($player) {
                $this->cancel_send = true;
                $player->sendDataPacket($packet);
                $this->cancel_send = false;
            });
            Server::getInstance()->getAsyncPool()->submitTask($task);
            return;
        }

        $this->cancel_send = true;
        $player->sendDataPacket($newPacket);
        $this->cancel_send = false;
    }
}