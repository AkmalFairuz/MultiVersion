<?php

declare(strict_types=1);

namespace AkmalFairuz\MultiVersion;

use AkmalFairuz\MultiVersion\network\convert\MultiVersionRuntimeBlockMapping;
use pocketmine\plugin\PluginBase;
use function mkdir;

class Loader extends PluginBase{

    public static $resourcesPath;

    public function onEnable(){
        foreach($this->getResources() as $k => $v) {
            $this->saveResource($k, true);
        }

        self::$resourcesPath = $this->getDataFolder();
        MultiVersionRuntimeBlockMapping::init();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}