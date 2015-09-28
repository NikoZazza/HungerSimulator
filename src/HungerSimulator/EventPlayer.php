<?php
namespace HungerSimulator;

use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
/*
 * Based on the information on this page http://minecraft.gamepedia.com/Hunger
 */
class EventPlayer {
    //items -> array(foodLevel, foodLevelSaturation)
    public $items = array(
        364 => array(4, 12.8),
        260 => array(2, 2.4),
        393 => array(3, 7.2),
        297 => array(3, 6.0),
        354 => array(7, 2.4),
        391 => array(2, 4.8),
        350 => array(3, 6.0),
        366 => array(3, 7.2),
        320 => array(4, 12.8),
        357 => array(1, 0.4),
        322 => array(2, 9.6),
        396 => array(3, 14.4),
        360 => array(1, 1.2),
        282 => array(3, 7.2),
        394 => array(1, 1.2),
        392 => array(1, 0.6),
        400 => array(4, 4.8),
        363 => array(2, 1.8),
        365 => array(1, 1.2),
        349 => array(1, 1.2),
        319 => array(2, 1.8),
        367 => array(2, 0.8),
        375 => array(1, 3.2)        
    );
    
    public $poison = array(
        365 => 30,
        367 => 80
    );
    
    public $action = array(
        "walk" => 0.01, 
        "racing" => 0.1, //TODO next version of MCPE
        "swim" => 0.015,
        "jump" => 0.2,
        "jump-racing" => 0.8, //TODO next version of MCPE
        "break-block" => 0.025,
        "attack" => 0.3, //TODO
        "damage" => 0.3 //TODO
    );
        
    public $foodExhaustion= array();
    
    
    public function getGamemode(Player $player){
        return $player->getGamemode();
    }
    
    public function playerItemConsume(PlayerItemConsumeEvent $event){
        $player = $event->getPlayer();
        if($player->getGamemode() ==0){       
        $item = $event->getItem();
        $item_consumed = $item->getID();
        
        if($this->isExistsItem($item_consumed)){
            $event->setCancelled();
            
            $count = $item->getCount();
            if($count == 1){
                $item = Item::get(0, 0, 0);
            }else{
                $item->setCount($count - 1); 
            }
            $player->getInventory()->setItemInHand($item);
            if($item_consumed == 367 || $item_consumed == 365){
                if($this->setPlayerPoisoned($player->getDisplayName(), $this->poison[$item_consumed])==true){
                    $this->chat($player , "You have been poisoned by food", 4); 
                }
            }
            
            $var = $this->items[$item_consumed];
            $this->addFoodLevel($player, $var[0]);
            $this->addFoodSaturationLevel($player, $var[1]);
            $this->test($player->getDisplayName());
        }
        }
    }
    
    public function playerQuit(PlayerQuitEvent $event){
        $player= $event->getPlayer()->getDisplayName();
        $var = $this->plr->get($player);
        
        $var["foodExaustionLevel"] = $this->foodExhaustion[$player];
        $this->plr->set($player, array_merge($var));
        $this->plr->save();
        
        $this->foodExhaustion[$event->getPlayer()->getDisplayName()] = false;
    }
      
    public function playerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer()->getDisplayName();        
        $var = 0.0;
        if($this->plr->exists($player)){
            $var = $this->plr->get($player)["foodExaustionLevel"];
        }else{
            $this->addPlayer($player);
        }
        $this->foodExhaustion[$player] = $var;
    }
        
    public function playerMove(EntityMoveEvent $event){
        $player = $event->getEntity();
        if($this->getGamemode($player) ==0){
     
        $id = $event->getEntity()->getLevel()->getBlockIdAt((int)$event->getEntity()->getX(), (int)$event->getEntity()->getY(), (int)$event->getEntity()->getZ()); 
        if($id == 9 || $id == 8){
            if((int)$event->getEntity()->lastY != (int)$event->getEntity()->getY() ||(int)$event->getEntity()->lastX != (int)$event->getEntity()->getX()){
                $this->addFoodExhaustion($event->getEntity(), $this->action["swim"]);
            }
        }else{
            if((int)$event->getEntity()->lastY < (int)$event->getEntity()->getY()){
                $this->addFoodExhaustion($event->getEntity(), $this->action["jump"]);
            }
            if((int)$player->lastX != (int) $player->getX() || (int)$player->lastY != (int) $player->getY()){
                $this->addFoodExhaustion($player, $this->action["walk"]);
            }
        }  
        }
    }
    
    public function playerDead(EntityDeathEvent $event){ 
        $this->playerRespawn($event->getEntity());
    }
    
    public function playerRespawn(Player $player){ 
        $player = $player->getDisplayName();
        $this->foodExhaustion[$player] = false;
        Server::getInstance()->broadcastMessage("morto");
        $this->removePlayer($player);
        $this->addPlayer($player);
    }
    
    public function playerBlockBreak(BlockBreakEvent $event){
        if($event->getPlayer()->getGamemode() != 0){
            $event->setCancelled();
        }
        if(!$event->isCancelled()){
            $this->addFoodExhaustion($event->getPlayer()->getDisplayName(), $this->action["break-block"]);
        }
    }
}
