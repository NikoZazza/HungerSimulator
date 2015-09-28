<?php
namespace HungerSimulator;
use pocketmine\scheduler\PluginTask;
class Timer extends PluginTask{
    public function onRun($currentTick){
        $this->getOwner()->timerChange();
    }
}