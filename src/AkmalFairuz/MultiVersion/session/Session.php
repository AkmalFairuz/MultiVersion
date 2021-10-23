<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\session;

use pocketmine\Player;

class Session{

    /** @var int */
    public $protocol;
    /** @var Player */
    private $player;

    public function __construct(Player $player, int $protocol) {
        $this->player = $player;
        $this->protocol = $protocol;
    }
}