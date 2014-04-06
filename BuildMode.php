<?php
/*
__PocketMine Plugin__
name=BuildMode
version=0.0.1
description=Plugin to turn off and on build and destroy mode
author=linuxboytoo
class=BuildMode
apiversion=12,13
*/


class BuildMode implements Plugin{
        private $api, $sessions, $path, $config, $userstate;
        public function __construct(ServerAPI $api, $server = false){
                $this->api = $api;
                $this->sessions = array();
        }

        public function init(){
		$defaults['plugin'] = [ "defaults" => [ "userstate" => "false" ], "messages" => [ "enabled" => "You have been granted build privileges!", "disabled" => "Your build privileges have been revoked", "cannotbuild" => "You do not have build privileges." ] ];

                $this->config['plugin'] = new Config($this->api->plugin->configPath($this) . "config.json", CONFIG_JSON, $defaults['plugin']);
		$this->config['userstate'] = new Config($this->api->plugin->configPath($this) . "userstate.json", CONFIG_JSON); 

		$this->api->addHandler("player.block.break", array($this, "blockBreak"));
		$this->api->addHandler("player.block.place", array($this, "blockPlace"));

		$this->api->console->register("buildmode", "Change Build Mode", array($this, "buildmode"));
	}

        public function __destruct(){
        }

	public function blockPlace($data) { return $this->blockAction($data); }
	public function blockBreak($data) { return $this->blockAction($data); }

	public function blockAction($data) {
		$player = (string)$data['player'];
		if($this->config['userstate']->exists($player)) {
                	if($this->config['userstate']->get($player)=='true') { return true; }
		}
		else { 
			$defaultmode = $this->config['plugin']->get('defaults')['userstate'];
			console("[BuildMode] Setting build mode of ".$defaultmode." for ".$player); 
			$this->config['userstate']->set($player,$defaultmode); 
			$this->config['userstate']->save(); 
		}
		$data['player']->sendChat($this->config['plugin']->get('messages')['cannotbuild']); 
		return false;
	}
	
	public function buildmode($cmd, $params, $issuer, $alias){
		$player = (string)$params[0];
		if(empty($player)) { return "You must specify a target user - /buildmode <user> (on|off)"; return false; }

		if($params[1]=='on') { $action = "enable"; }
		elseif($params[1]=='off') { $action = "disable"; }
		elseif(empty($params[1])) { return "Build mode of ".$params[0]." is currently ".$this->config['userstate']->get($player); }
		else { return "Invalid action specified - /buildmode <user> (on|off)"; }

                if($action=='disable') { $this->config['userstate']->set($player,"false"); $output = "disabled";}
		else { $this->config['userstate']->set($player,'true'); $output = "enabled"; }
		$this->config['userstate']->save();
		
		console("[BuildMode] ".$issuer." ".$output." build mode for ".$player);
                if($target = $this->api->player->get($player)) { $target->sendChat($this->config['plugin']->get("messages")[$output]); } 
		return "Buildmode has been ".$output." for ".$player;
	}
}
