<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\session;

use pocketmine\Player;

class SessionManager{

    /** @var Session[] */
    private static $sessions = [];

    public static function get(Player $player) : ?Session{
        return self::$sessions[$player->getRawUniqueId()] ?? null;
    }

    public static function remove(Player $player) {
        unset(self::$sessions[$player->getRawUniqueId()]);
    }

    public static function create(Player $player, int $protocol) {
        self::$sessions[$player->getRawUniqueId()] = new Session($player, $protocol);
    }

    public static function getProtocol(Player $player): ?int{
        if(($session = self::get($player)) !== null) {
            return $session->protocol;
        }
        return null;
    }
}