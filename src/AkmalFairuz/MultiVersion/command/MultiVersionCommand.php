<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion\command;

use AkmalFairuz\MultiVersion\Loader;
use AkmalFairuz\MultiVersion\MultiVersion;
use AkmalFairuz\MultiVersion\network\ProtocolConstants;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function count;
use function strlen;
use function substr;

class MultiVersionCommand extends PluginCommand{

    const PREFIX = TextFormat::YELLOW . "[" . TextFormat::GREEN . "Multi" . TextFormat::GOLD . "Version" . TextFormat::YELLOW . "] " . TextFormat::LIGHT_PURPLE;

    public function __construct(string $name, Loader $owner){
        parent::__construct($name, $owner);
        $this->setDescription("MultiVersion command");
        $this->setAliases(["mv"]);
        $this->setPermission("multiversion.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(count($args) === 0) {
            return;
        }
        switch($args[0]) {
            case "player":
                if(count($args) !== 2) {
                    $sender->sendMessage(self::PREFIX . "Usage: /multiversion player <name>");
                    return;
                }
                $target = Server::getInstance()->getPlayerExact($args[1]);
                if(!$target instanceof Player) {
                    $sender->sendMessage(self::PREFIX . "Player " . $args[1] . " is not found!");
                    return;
                }
                $protocol = MultiVersion::getProtocol($target);
                $ver = ProtocolConstants::MINECRAFT_VERSION[$protocol];
                $sender->sendMessage(self::PREFIX . $target->getName() . " is using version " . $ver . " (Protocol: " . $protocol . " )");
                return;
            case "all":
                foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                    $protocol = MultiVersion::getProtocol($player);
                    $ver = ProtocolConstants::MINECRAFT_VERSION[$protocol];
                    $msg = $player->getName() . " [Protocol: " . $protocol . ", Version: " . $ver . "]";
                    $sender->sendMessage(self::PREFIX . $msg . "\n");
                }
                return;
            default:
                $sender->sendMessage(self::PREFIX . " Usage: /multiversion <player|all>");
        }
    }
}