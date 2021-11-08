<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\command\MultiVersionCommand;
use AkmalFairuz\MultiVersion\network\convert\MultiVersionCraftingManager;
use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use AkmalFairuz\MultiVersion\task\CheckUpdateTask;
use pocketmine\inventory\CraftingManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use function in_array;

class Loader extends PluginBase{

    /** @var string */
    public static $resourcesPath;

    /** @var self */
    private static $instance;

    /** @var MultiVersionCraftingManager */
    public $craftingManager;

    /** @var bool */
    public $canJoin = false;

    public static function getInstance() : self{
        return self::$instance;
    }

    public function onEnable(){
        self::$instance = $this;

        foreach($this->getResources() as $k => $v) {
            $this->saveResource($k, $k !== "config.yml");
        }

        Config::init($this->getDataFolder() . "config.yml");

        self::$resourcesPath = $this->getDataFolder();
        MultiVersionRuntimeBlockMapping::init();

        // wait until other plugin register custom craft
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void {
            $this->craftingManager = new MultiVersionCraftingManager();
            $this->canJoin = true;
        }), 1);

        $this->getServer()->getCommandMap()->register("multiversion", new MultiVersionCommand("multiversion", $this));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        CheckUpdateTask::init($this->getDescription()->getVersion());
    }

    public function isProtocolDisabled(int $protocol): bool{
        $config = Config::$DISABLED_PROTOCOLS;
        return in_array($protocol, $config, true);
    }
}