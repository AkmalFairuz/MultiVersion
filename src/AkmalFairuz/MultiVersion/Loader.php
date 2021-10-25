<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\plugin\PluginBase;

class Loader extends PluginBase{

    public static $resourcesPath;

    private static $instance;

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

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}