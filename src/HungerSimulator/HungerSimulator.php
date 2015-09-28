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
    public $plr, $config, $event_player, $lang;
    public $version_plugin = 0.1;  

       
    public function onEnable(){
        $this->plr = new Config("./plugins/HungerSimulator/src/HungerSimulator/plr.yml", Config::YAML);
        $this->config = new Config("./plugins/HungerSimulator/src/HungerSimulator/config.yml", Config::YAML, array("versionPlugin" => $this->version_plugin, "defaultDifficult" => "hard"));
        $this->event_player = new EventPlayer($this);
        
        $this->lang = new Config("./plugins/HungerSimulator/src/HungerSimulator/multi_lang.yml", Config::YAML);
           
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Timer($this), 20 * 4 );
        $this->getServer()->getPluginManager()->registerEvents($this->event_player, $this);
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        if($command->getName() == "hunger"){
            if($sender->isOp()){
                if($args == true){
                    switch($args[0]){
                        case "set":
                            $this->chat($sender, "invArg", 1, "IT");
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
            
            if($this->event_player->foodExhaustion[$player] + $value >= 4){
                $this->event_player->foodExhaustion[$player] = ($this->event_player->foodExhaustion[$player] + $value) - 3.9;
                
                if($this->plr->get($player)["foodSaturationLevel"] > 0){
                    $this->addFoodSaturationLevel($player, -1);
                }else{
                    $this->addFoodLevel($player, -1);                    
                }
            }else{
                $this->event_player->foodExhaustion[$player] = (float)$this->event_player->foodExhaustion[$player] + $value;
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
            $this->
            $var["foodLevel"] = $var["foodLevel"] - 2;
            $var["foodSaturationLevel"] = 0.0;
            $this->plr->set($player, array_merge($var));
            $this->plr->save();
        }        
    }
    
    public function chat($player, $mex, $style, $lang){
        if(!$this->lang->exists($lang)){
            $lang = "EN";
        }
        $testo = $this->lang->get($lang);

        $testo = $testo[$mex];
        $testo= $testo["mex"];
        if(preg_match("@@", $testo)){
            $testo = str_replace("@@", $args[0], $testo);
        }
        if(preg_match("##", $testo)){
            $testo = str_replace("##", $args[1], $testo);
        }
        $mex = $testo;
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