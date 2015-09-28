<?php
namespace HungerSimulator;

class Chat{ 
    public function isExist($var, $config){
        if(count($config) !=0){
            foreach($config as $z => $c){
                if($var == $z){
                    return true;
                }
            }
        }else{
            return false;
        }       
    }
    
    public function getMex($mex, $lang, $args, $config){
        $lang = strtoupper($lang);
        if(!$this->isExist($lang, $config)){
            $lang = "EN";
        }
        $testo = $config[$lang][$mex]["mex"];       
        if(preg_match("@@", $testo)){
            $testo = str_replace("@@", $args[0], $testo);
        }
        if(preg_match("##", $testo)){
            $testo = str_replace("##", $args[1], $testo);
        }
        return $testo;        
    }
}
