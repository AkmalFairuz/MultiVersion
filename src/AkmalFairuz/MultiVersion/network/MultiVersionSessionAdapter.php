<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\network;

use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;
use pocketmine\Server;

class MultiVersionSessionAdapter extends PlayerNetworkSessionAdapter{

    /** @var int */
    protected $protocol;

    public function __construct(Server $server, Player $player, int $protocol){
        parent::__construct($server, $player);
        $this->protocol = $protocol;
    }

    public function handleDataPacket(DataPacket $packet){
        if($packet instanceof BatchPacket) {
            foreach($packet->getPackets() as $packet) {
                parent::handleDataPacket(Translator::fromClient($packet, $this->protocol));
            }
        }
        parent::handleDataPacket($packet);
    }
}