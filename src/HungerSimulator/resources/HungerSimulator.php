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

class HungerSimulator extends PluginBase implements Listener{
    public $plr, $config;
    public $version_plugin = 0.1;
    
    public $foodExhaustion= array();
    
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
       
    public function onEnable(){
        $this->plr = new Config("./plugins/HungerSimulator/src/HungerSimulator/plr.yml", Config::YAML);
        $this->config = new Config("./plugins/HungerSimulator/src/HungerSimulator/config.yml", Config::YAML, array("versionPlugin" => $this->version_plugin, "defaultDifficult" => "hard"));

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), 20 * 4 );
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        if($command->getName() == "hunger"){
            if($sender->isOp()){
                if($args == true){
                    switch($args[0]){
                        case "set":
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0] , 4);
                            if(count($args)!=4){
                                $this->chat($sender, "Invalid arguments", 1);
                                $mex = array("/food set <food> <value> <player>", "/food set <saturation> <value> <player>");
                                foreach($mex as $var){
                                    $this->chat($sender, $var, 4);
                                }    
                                break;
                            }
                            if($args[1] == "food"){
                                if(is_numeric($args[2])){
                                    if($args[3] != ""){
                                        if($this->plr->exists($args[3])){
                                            $get = $this->plr->get($args[3]);
                                            $get["foodLevel"] = $args[2];
                                            $this->plr->set($args[3], array_merge($get));
                                            $this->plr->save();              
                                            $this->chat($sender, "FoodLevel of the player ".$args[3]." set to ".$args[2], 3);                              
                                        }else{
                                            $this->chat($sender, "The player does not exist, or you have the wrong name", 1);
                                            break;
                                        }
                                    }else{
                                        $this->chat($sender, "Invalid arguments", 1);
                                        break;
                                    }
                                }else{
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;                          
                                }    
                            }else{
                                if($args[1] == "saturation"){
                                    if(is_numeric($args[2])){
                                        if($args[3] != ""){
                                            if($this->plr->exists($args[3])){
                                                $get = $this->plr->get($args[3]);
                                                $get["foodSaturationLevel"] = $args[2];
                                                $this->plr->set($args[3], array_merge($get));
                                                $this->plr->save();     
                                                $this->chat($sender, "SaturationLevel of the player ".$args[3]." set to ".$args[2], 3);
                                            }else{
                                                $this->chat($sender, "The player does not exist, or you have the wrong name", 1);
                                                break;
                                            }
                                        }else{
                                            $this->chat($sender, "Invalid arguments", 1);
                                            break;
                                        }
                                    }else{
                                        $this->chat($sender, "Invalid arguments", 1);
                                        break;                          
                                    }     
                                }else{
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;
                                }
                            }
                            break;
                            
                        case "info":
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0] , 4);
                            if(count($args) == 2){
                                if($args[1] != "" ){
                                    $get = $this->plr->get($args[1]);                                           
                                    $mex = array("Info of the player ".$args[1] ,"foodLevel = ".$get["foodLevel"], "foodSaturationLevel = ".$get["foodSaturationLevel"] ,"foodExaustionLevel = ".$get["foodExaustionLevel"]);
                                    foreach($mex as $var){
                                        $this->chat($sender, $var, 4);
                                    }
                                }else{
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;
                                }
                            }else{
                                $this->chat($sender, "Invalid arguments", 1);
                                break;
                            }
                            break;
                        case "difficult":
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0] , 4);
                            if(count($args) == 3){
                                if(($args[1] == "easy" || $args[1] == "normal" || $args[1] == "hard") && $args[2] != "" ){
                                    $get = $this->plr->get($args[2]);
                                    $get["difficult"] = $args[1];
                                    $this->plr->set($args[2], array_merge($get));
                                    $this->chat($sender, "Difficulty of the player ".$args[2]." set to ".$args[1], 3);
                                }else{
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break;
                                }
                            }else{
                                $this->chat($sender, "Invalid arguments", 1);
                                break;
                            }
                            break;
                        case "setup":
                            $this->chat($sender, "Using /".$command->getName()." ".$args[0] , 4);
                            if(count($args) == 3){
                                if($args[1] == "difficult" && ($args[2] == "easy" || $args[2] == "normal" || $args[2] == "hard" )){
                                    $this->config->set("defaultDifficult", $args[2]);
                                    $this->config->save();
                                    $this->chat($sender, "The default difficulty set to ".$args[2], 3);
                                }else{
                                    $this->chat($sender, "Invalid arguments", 1);
                                    break; 
                                }
                            }else{
                                $this->chat($sender, "Invalid arguments", 1);
                                break;
                            }
                            break;
                    }
                }else{
                    $mex = array("/hunger set <food-saturation> <value> <player>", "/hunger info <player>","/hunger difficult <easy-normal-hard> <player>", "/hunger setup difficult <easy-normal-hard>");
                    foreach($mex as $var){
                        $this->chat($sender, $var, 4);
                    }
                }
            }
            if($command->getName() == "food"){
                if($args == false){ 
                    $perc = ($this->plr->get($sender->getName())["foodLevel"] / 2) * 10; 
                    $this->chat($sender, "You have ".$perc."% of the level of food", 4);                        
                }else{
                    $this->chat($sender, "Using /".$command->getName()." ".$args[0] , 4);
                    $this->chat($sender, "You need to be admin/OP to run this command", 1);
                }
            }
        }
    }
    
    public function onDisable() {
        $this->plr->save();
        $this->config->save();
    }   
 
    public function removePlayer($player){
        $this->plr->remove($player);
        $this->plr->save();
    }   

    public function addPlayer($player){
        $this->plr->set($player, array("foodLevel" => 20, "foodSaturationLevel" => 0, "foodExaustionLevel" => 0.0, "difficult" => "hard"));
        $this->plr->save();        
    }
    
    public function timerChange(){
        $online = Server::getInstance()->getOnlinePlayers();
        if(count($online) != 0){
            foreach($online as $var){
                $food_level = $this->plr->get($var->getDisplayName())["foodLevel"];
                if($food_level >= 17){
                    if($var->getHealth() != 20){
                        $var->setHealth($var->getHealth() + 1);
                    }
                }else{
                    if($food_level <= 0){
                        if($var->getHealth() <= 0 || $var->getHealth() - 1 <= 0){
                            $var->kill();
                        }else{
                            $var->setHealth($var->getHealth() - 1);
                        }
                    }
                }
            }
        }
    }
    
    public function addFoodLevel($player, $value){
        if($this->plr->exists($player)){
            $var = $this->plr->get($player)["foodLevel"] + $value;
            if($var <=20){
               $this->setFoodLevel($player, $var);  
            }
        }else{
            $this->addPlayer($player);            
        }
    }
    
    public function setFoodLevel($player, $value){
        if($value >= 0){
            if(!$this->plr->exists($player)){
                $this->addPlayer($player);
            }
            $var = $this->plr->get($player);
            $var["foodLevel"] = $value;
            $this->plr->set($player, array_merge($var));
            $this->plr->save();
        }
    }
          
    public function addFoodSaturationLevel($player, $value){
        if($this->plr->exists($player)){
            $var = $this->plr->get($player)["foodSaturationLevel"] + $value;
            if($var <=20 && $var >= 0){
               $this->setFoodSaturationLevel($player, $var);  
            }
        }else{
            $this->addPlayer($player);            
        }
    }
    
    public function setFoodSaturationLevel($player, $value){
        if($value >= 0){
            if(!$this->plr->exists($player)){
                $this->addPlayer($player);
            }
            $var = $this->plr->get($player);
            $var["foodSaturationLevel"] = $value;
            $this->plr->set($player, array_merge($var));
            $this->plr->save();
        }
    }
    
    public function isExistsItem($item_to_found){
        foreach($this->items as $var => $c){
            if($item_to_found == $var){
                return true;
                break;
            }
        }
        return false;
    }
    
    public function addFoodExhaustion(Player $player, $value){
        $player = $player->getDisplayName();
        if($value >0){
            if($this->foodExhaustion[$player] + $value >= 4){
                $this->foodExhaustion[$player] = ($this->foodExhaustion[$player] + $value) - 3.9;
                
                if($this->plr->get($player)["foodSaturationLevel"] > 0){
                    $this->addFoodSaturationLevel($player, -1);
                }else{
                    $this->addFoodLevel($player, -1);                    
                }
            }else{
                $this->foodExhaustion[$player] = (float)$this->foodExhaustion[$player] + $value;
            }            
        }
    }
    
    public function setPlayerPoisoned($player, $value){
        $i = 0;
        $z = 0;
        $value = $value / 10;
        for($i=0; $i<=9; $i = $i+1){
            if(rand(0, 1) == 1){
                $z = $z + 1;
            }            
        }
        if($z >= $value){
            $this->playerPoisoned($player); 
            return true;
        }        
    }
    
    public function playerPoisoned($player){
        $var = $this->plr->get($player);
        if($var["foodLevel"] >0){
            $var["foodLevel"] = $var["foodLevel"] - 2;
            $var["foodSaturationLevel"] = 0.0;
            $this->plr->set($player, array_merge($var));
            $this->plr->save();
        }        
    }
    
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
    
    public function chat($player, $mex, $style){
        /*
         * 0 default
         * 1 error
         * 2 warning
         * 3 success
         * 4 info
         */ 
        $style_mex= $style;
        $p= "[HungerSimulator] ";
        switch($style_mex){
            case 1:
                $player->sendMessage(TextFormat::RED.$p.$mex);
                break;
            case 2:
                $player->sendMessage(TextFormat::YELLOW.$p.$mex);
                break;
            case 3:
                $player->sendMessage(TextFormat::GREEN.$p.$mex);
                break;
            case 4:
                $player->sendMessage(TextFormat::AQUA.$p.$mex);
                break;
            default:
                $player->sendMessage($p.$mex);
                break;
        }
    }
}