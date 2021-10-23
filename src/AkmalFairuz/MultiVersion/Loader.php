<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\plugin\PluginBase;

class Loader extends PluginBase{

    public static $resourcesPath;

    public function onEnable(){
        self::$resourcesPath = $this->getDataFolder();
        MultiVersionRuntimeBlockMapping::init();

        foreach($this->getResources() as $k => $v) {
            $this->saveResource($k, true);
        }

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}