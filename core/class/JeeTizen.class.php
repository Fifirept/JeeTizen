<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__ . '/../../../../core/php/core.inc.php';

/*
* Les librairies Samsung (TvConnector, TvParametres, etc.) sont chargées
* en lazy loading dans getTvParametres() pour ne pas bloquer la déclaration
* des classes JeeTizen/JeeTizenCmd au chargement du plugin.
*/

class JeeTizen extends eqLogic {
	/*     * *************************Attributs****************************** */

	const DEFAULT_COMMANDS = [
		// [logicalId, nom, type, subType, isVisible, config, genericType, icon]
		['on_off',   'Marche/Arrêt', 'action', 'other',   1, [], 'ENERGY_ON', 'fas fa-power-off'],
		['off',      'Extinction',   'action', 'other',   0, [], 'ENERGY_OFF', 'fas fa-stop'],
		['mute',     'Mute',         'action', 'other',   1, [], 'VOLUME_MUTE', 'fas fa-volume-mute'],
		['vol_up',   'Volume +',     'action', 'other',   1, [], 'VOLUME_UP', 'fas fa-volume-up'],
		['vol_down', 'Volume -',     'action', 'other',   1, [], 'VOLUME_DOWN', 'fas fa-volume-down'],
		['ch_up',    'Chaîne +',     'action', 'other',   1, [], 'CHANNEL_UP', 'fas fa-chevron-up'],
		['ch_down',  'Chaîne -',     'action', 'other',   1, [], 'CHANNEL_DOWN', 'fas fa-chevron-down'],
		['source',   'Source',       'action', 'other',   1, [], '', 'fas fa-external-link-alt'],
		['up',       'Haut',         'action', 'other',   1, [], '', 'fas fa-caret-up'],
		['down',     'Bas',          'action', 'other',   1, [], '', 'fas fa-caret-down'],
		['left',     'Gauche',       'action', 'other',   1, [], '', 'fas fa-caret-left'],
		['right',    'Droite',       'action', 'other',   1, [], '', 'fas fa-caret-right'],
		['enter',    'OK',           'action', 'other',   1, [], '', 'fas fa-check-circle'],
		['return',   'Retour',       'action', 'other',   1, [], '', 'fas fa-undo'],
		['home',     'Home',         'action', 'other',   1, [], '', 'fas fa-home'],
		['tv',       'TV',           'action', 'other',   1, [], '', 'fas fa-tv'],
		['hdmi1',    'HDMI 1',       'action', 'other',   0, [], '', 'fas fa-plug'],
		['hdmi2',    'HDMI 2',       'action', 'other',   0, [], '', 'fas fa-plug'],
		['state',    'Etat',         'info',   'binary',  1, [], 'ENERGY_STATE', 'fas fa-tv'],
	];

	/*     * ***********************Methode static*************************** */

	public static function cron() {
		foreach (self::byType(__CLASS__, true) as $eqLogic) {
			$ip = $eqLogic->getConfiguration('ip_tv', '');
			if (empty($ip)) {
				continue;
			}
			$port = (int)$eqLogic->getConfiguration('port_tv', 8002);
			$state = @fsockopen($ip, $port, $errno, $errstr, 2);
			$isOn = ($state !== false);
			if ($state) {
				fclose($state);
			}
			$cmdState = $eqLogic->getCmd('info', 'state');
			if (is_object($cmdState)) {
				$eqLogic->checkAndUpdateCmd($cmdState, $isOn ? 1 : 0);
			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {
		// Valeurs par défaut pour un nouvel équipement
		if ($this->getConfiguration('port_tv', '') == '') {
			$this->setConfiguration('port_tv', '8002');
		}
		if ($this->getConfiguration('ssl_tv', '') === '') {
			$this->setConfiguration('ssl_tv', '1');
		}
		if ($this->getConfiguration('scenario_tps_pause', '') == '') {
			$this->setConfiguration('scenario_tps_pause', '100');
		}
		if ($this->getConfiguration('scenario_tps_pause_num', '') == '') {
			$this->setConfiguration('scenario_tps_pause_num', '500');
		}
		if ($this->getConfiguration('modele_tv', '') == '') {
			$this->setConfiguration('modele_tv', 'tizen');
		}
		if ($this->getConfiguration('sub_modele_tv', '') === '') {
			$this->setConfiguration('sub_modele_tv', '3');
		}
		if ($this->getConfiguration('app_tv', '') == '') {
			$this->setConfiguration('app_tv', 'jeedom.jeetizen.samsung');
		}
		if ($this->getConfiguration('widget_template', '') == '') {
			$this->setConfiguration('widget_template', 'dark');
		}
	}

	public function postInsert() {
	}

	public function preUpdate() {
		// Vérification IP obligatoire
		$ip_tv = $this->getConfiguration('ip_tv');
		if (empty($ip_tv)) {
			throw new Exception(__('Adresse IP TV non renseignée', __FILE__));
		}
		if (!filter_var($ip_tv, FILTER_VALIDATE_IP)) {
			throw new Exception(__('Format adresse IP TV incorrect !', __FILE__));
		}

		// Vérifier port
		$port_tv = $this->getConfiguration('port_tv');
		if ($port_tv == '' || !is_numeric($port_tv)) {
			throw new Exception(__('Port TV non renseigné ou non numérique!', __FILE__));
		}
		if ($port_tv < 1 || $port_tv > 65536) {
			throw new Exception(__('Port TV incorrect, doit être entre 1 et 65536', __FILE__));
		}

		// Si modèle K : vérifier délai
		if ($this->getConfiguration('sub_modele_tv') == '1') {
			$delai = $this->getConfiguration('sub_modele_tv_delay');
			if ($delai == '' || !is_numeric($delai)) {
				throw new Exception(__('Délai modèle K non renseigné ou non numérique!', __FILE__));
			}
			if ($delai < 500 || $delai > 1000) {
				throw new Exception(__('Délai modèle K incorrect, doit être entre 500 et 1000 ms', __FILE__));
			}
		} else {
			$this->setConfiguration('sub_modele_tv_delay', 0);
		}

		// WOL : vérifier MAC si activé
		if ($this->getConfiguration('wol_tv') == 1) {
			$mac = $this->getConfiguration('adresse_mac_tv');
			if (empty($mac)) {
				throw new Exception(__('Adresse MAC non renseignée!', __FILE__));
			}
			if (!preg_match('/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/', $mac)) {
				throw new Exception(__('Format adresse MAC incorrect !', __FILE__));
			}
			if ($this->getConfiguration('wol_tv_direct') === '1') {
				$wolIp = $this->getConfiguration('wol_broadcast_ip_direct', '');
				if (empty($wolIp) || !filter_var($wolIp, FILTER_VALIDATE_IP)) {
					throw new Exception(__('IP broadcast direct non renseignée ou incorrecte !', __FILE__));
				}
			}
			if ($this->getConfiguration('wol_tv_broadcast') === '1') {
				$subnet = $this->getConfiguration('wol_broadcast_subnet', '');
				if (empty($subnet) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
					throw new Exception(__('Subnet masque non renseigné ou incorrect !', __FILE__));
				}
			}
		} else {
			$this->setConfiguration('adresse_mac_tv', '');
		}

		// Application TV par défaut
		if (empty($this->getConfiguration('app_tv'))) {
			$this->setConfiguration('app_tv', 'jeedom.jeetizen.samsung');
		}

		// Vérifier latences
		$latence = $this->getConfiguration('scenario_tps_pause');
		if ($latence === '' || !is_numeric($latence) || $latence < 0 || $latence > 2000) {
			throw new Exception(__('Temps de latence incorrect (doit être entre 0 et 2000 ms)', __FILE__));
		}
		$latenceNum = $this->getConfiguration('scenario_tps_pause_num');
		if ($latenceNum === '' || !is_numeric($latenceNum) || $latenceNum <= 0 || $latenceNum > 2000) {
			throw new Exception(__('Temps de latence (NUM) incorrect (doit être entre 1 et 2000 ms)', __FILE__));
		}
	}

	public function postUpdate() {
	}

	public function preSave() {
	}

	public function postSave() {
		foreach (self::DEFAULT_COMMANDS as $cmdDef) {
			$logicalId  = $cmdDef[0];
			$name       = $cmdDef[1];
			$type       = $cmdDef[2];
			$subType    = $cmdDef[3];
			$isVisible  = $cmdDef[4];
			$config     = $cmdDef[5];
			$genericType = isset($cmdDef[6]) ? $cmdDef[6] : '';
			$icon       = isset($cmdDef[7]) ? $cmdDef[7] : '';

			// Chercher par logicalId d'abord
			$cmd = $this->getCmd($type, $logicalId);
			// Chercher aussi sans filtre type (migration)
			if (!is_object($cmd)) {
				$cmd = $this->getCmd(null, $logicalId);
			}
			if (!is_object($cmd)) {
				// Vérifier qu'une commande avec ce nom n'existe pas déjà
				foreach ($this->getCmd() as $existingCmd) {
					if ($existingCmd->getName() == $name) {
						$existingCmd->setLogicalId($logicalId);
						$existingCmd->setType($type);
						$existingCmd->setSubType($subType);
						$existingCmd->save();
						$cmd = $existingCmd;
						break;
					}
				}
			}
			if (!is_object($cmd)) {
				try {
					$cmd = new JeeTizenCmd();
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId($logicalId);
					$cmd->setName($name);
					$cmd->setType($type);
					$cmd->setSubType($subType);
					$cmd->setIsVisible($isVisible);
					if (!empty($genericType)) {
						$cmd->setGeneric_type($genericType);
					}
					if (!empty($icon)) {
						$cmd->setDisplay('icon', '<i class="' . $icon . '"></i>');
					}
					foreach ($config as $k => $v) {
						$cmd->setConfiguration($k, $v);
					}
					$cmd->save();
				} catch (\Exception $e) {
					log::add('JeeTizen', 'warning', 'Commande ' . $name . ' non créée : ' . $e->getMessage());
				}
			}
		}
	}

	public function preRemove() {
	}

	public function postRemove() {
	}

	/**
	 * Widget télécommande Samsung - template sombre ou clair
	 */
	public function toHtml($_version = 'dashboard') {
		$html = parent::toHtml($_version);
		if ($html == '') {
			return '';
		}

		$template = $this->getConfiguration('widget_template', 'dark');
		if ($template == 'none') {
			return $html;
		}

		$eqId = $this->getId();

		// Map logicalId -> cmd_id
		$cmdMap = array();
		foreach ($this->getCmd() as $cmd) {
			$lid = $cmd->getLogicalId();
			if (!empty($lid)) {
				$cmdMap[$lid] = $cmd->getId();
			}
		}
		$stateCmd = $this->getCmd('info', 'state');
		$stateOn = (is_object($stateCmd) && $stateCmd->execCmd()) ? 'true' : 'false';
		$cmdJson = json_encode($cmdMap);
		$isDark = ($template !== 'light') ? 'true' : 'false';

		$js = '<script>'
			. '(function(){'
			. 'function build(){'
			. 'var w=document.querySelector(\'[data-eqlogic_id="' . $eqId . '"]\');'
			. 'if(!w||w.querySelector(".jt-rc"))return;'
			. 'var C=' . $cmdJson . ';'
			. 'if(!C.on_off&&!C.mute)return;'
			. 'var dk=' . $isDark . ',stOn=' . $stateOn . ';'
			// Couleurs thème
			. 'var bg=dk?"rgb(26,26,26)":"rgb(253,250,240)";'
			. 'var bdr=dk?"rgb(51,51,51)":"rgb(239,230,213)";'
			. 'var btnBg=dk?"rgb(51,51,51)":"rgb(230,223,204)";'
			. 'var btnBdr=dk?"rgb(68,68,68)":"rgb(220,211,188)";'
			. 'var txtC=dk?"white":"rgb(68,68,68)";'
			. 'var lblC=dk?"rgb(102,102,102)":"rgb(85,85,68)";'
			. 'var padBg=dk?"rgb(34,34,34)":"rgb(230,223,204)";'
			. 'var okBg=dk?"radial-gradient(circle,rgb(68,68,68),rgb(34,34,34))":"rgb(253,250,240)";'
			. 'var okBdr=dk?"rgb(85,85,85)":"rgb(220,211,188)";'
			. 'var ledC=stOn?"rgb(0,200,100)":"rgb(80,80,80)";'
			// Style
			. 'w.style.cssText="background:"+bg+"!important;border:1px solid "+bdr+"!important;border-radius:24px!important;padding:0!important;overflow:hidden";'
			// Helper
			. 'function ex(id){jeedom.cmd.execute({id:String(id)});}'
			. 'function B(id,inner,cls,style){'
			. 'return \'<button class="\'+cls+\'" data-id="\'+id+\'" style="\'+style+\'">\'+inner+\'</button>\';}'
			// SVGs
			. 'var svgPwr=\'<svg viewBox="0 0 24 24" style="width:28px;height:28px;stroke:white;fill:none"><path d="M12 2v10M18.4 6.6a9 9 0 1 1-12.77 0" stroke-width="2.5" stroke-linecap="round"/></svg>\';'
			. 'var svgSrc=\'<svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:none;stroke:\'+txtC+\';stroke-width:2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M11 12h7M15 9l3 3-3 3"/></svg>\';'
			. 'var svgUp=\'<svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:\'+lblC+\'"><path d="M7 14l5-5 5 5z"/></svg>\';'
			. 'var svgDn=\'<svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:\'+lblC+\'"><path d="M7 10l5 5 5-5z"/></svg>\';'
			. 'var svgLt=\'<svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:\'+lblC+\'"><path d="M14 7l-5 5 5 5z"/></svg>\';'
			. 'var svgRt=\'<svg viewBox="0 0 24 24" style="width:40px;height:40px;fill:\'+lblC+\'"><path d="M10 17l5-5-5-5z"/></svg>\';'
			. 'var svgMute=\'<svg viewBox="0 0 24 24" style="width:60%;height:60%;fill:\'+txtC+\'"><path d="M5 9v6h4l5 5V4l-5 5H5z"/><line x1="4" y1="4" x2="20" y2="20" stroke="\'+txtC+\'" stroke-width="2"/></svg>\';'
			. 'var svgRet=\'<svg viewBox="0 0 24 24" style="width:90%;height:24px;fill:\'+txtC+\'"><path d="M12.5 8c-2.6 0-5 1-6.9 2.6L2 7v9h9l-3.6-3.6c1.4-1.2 3.1-1.9 5.1-1.9 3.5 0 6.5 2.3 7.6 5.5l2.4-.8c-1.3-4.1-5.2-7.1-9.9-7.1z"/></svg>\';'
			. 'var svgHome=\'<svg viewBox="0 0 24 24" style="width:90%;height:24px;fill:\'+txtC+\'"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>\';'
			// Styles communs
			. 'var sBtn="background:"+btnBg+";border:1px solid "+btnBdr+";color:"+txtC+";cursor:pointer;display:flex;align-items:center;justify-content:center;";'
			. 'var sVert=sBtn+"border-radius:15px;width:70px;height:70px;flex-direction:column;padding:0;font-size:14px;";'
			. 'var sRect=sBtn+"border-radius:12px;height:50px;flex:1;font-weight:bold;flex-direction:column;padding:4px;";'
			// Construire
			. 'var h=\'<div class="jt-rc" style="padding:16px;display:flex;flex-direction:column;align-items:center;gap:14px">\';'
			// LED
			. 'h+=\'<div style="width:6px;height:6px;border-radius:50%;background:\'+ledC+\'"></div>\';'
			// Row 1 : Power + Source
			. 'h+=\'<div style="display:flex;width:100%;justify-content:space-between;align-items:center">\';'
			. 'if(C.on_off)h+=\'<button class="jt-x" data-id="\'+C.on_off+\'" style="width:50px;height:50px;border-radius:50%;border:none;cursor:pointer;background:radial-gradient(circle at 35% 35%,rgb(255,51,51),rgb(170,0,0),rgb(102,0,0));display:flex;justify-content:center;align-items:center">\'+svgPwr+\'</button>\';'
			. 'if(C.source)h+=\'<button class="jt-x" data-id="\'+C.source+\'" style="\'+sBtn+\'width:90px;height:38px;border-radius:10px;font-weight:bold;gap:6px;font-size:11px">\'+svgSrc+\' SOURCE</button>\';'
			. 'h+=\'</div>\';'
			// PAD navigation
			. 'if(C.up&&C.down&&C.left&&C.right&&C.enter){'
			. 'h+=\'<div style="position:relative;width:160px;height:160px;border-radius:50%;background:\'+padBg+\';overflow:hidden;box-shadow:inset 0 0 15px rgba(0,0,0,\'+(dk?0.5:0.05)+\')">\';'
			. 'var aS="position:absolute;width:100%;height:100%;top:0;left:0;background:transparent;border:none;cursor:pointer;padding:0;z-index:2;";'
			. 'var svgS="pointer-events:none;position:absolute;";'
			. 'h+=\'<button class="jt-x" data-id="\'+C.up+\'" style="\'+aS+\'clip-path:polygon(50% 50%,0% 0%,100% 0%)"><div style="\'+svgS+\'top:10px;left:50%;transform:translateX(-50%)">\'+svgUp+\'</div></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.down+\'" style="\'+aS+\'clip-path:polygon(50% 50%,0% 100%,100% 100%)"><div style="\'+svgS+\'bottom:10px;left:50%;transform:translateX(-50%)">\'+svgDn+\'</div></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.left+\'" style="\'+aS+\'clip-path:polygon(50% 50%,0% 0%,0% 100%)"><div style="\'+svgS+\'left:10px;top:50%;transform:translateY(-50%)">\'+svgLt+\'</div></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.right+\'" style="\'+aS+\'clip-path:polygon(50% 50%,100% 0%,100% 100%)"><div style="\'+svgS+\'right:10px;top:50%;transform:translateY(-50%)">\'+svgRt+\'</div></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.enter+\'" style="position:absolute;top:50%;left:50%;width:60px;height:60px;transform:translate(-50%,-50%);border-radius:50%;background:\'+okBg+\';border:1px solid \'+okBdr+\';color:\'+txtC+\';font-weight:bold;font-size:16px;z-index:10;cursor:pointer;display:flex;justify-content:center;align-items:center">OK</button>\';'
			. 'h+=\'</div>\';}'
			// VOL + MUTE + CH
			. 'h+=\'<div style="display:flex;width:100%;justify-content:center;gap:8px;align-items:center">\';'
			. 'if(C.vol_up&&C.vol_down){'
			. 'h+=\'<div style="display:flex;flex-direction:column;gap:8px">\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.vol_up+\'" style="\'+sVert+\'"><b style="font-size:22px">+</b><span style="font-size:11px;font-weight:bold">VOL</span></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.vol_down+\'" style="\'+sVert+\'"><span style="font-size:11px;font-weight:bold">VOL</span><b style="font-size:22px">-</b></button>\';'
			. 'h+=\'</div>\';}'
			. 'if(C.mute)h+=\'<button class="jt-x" data-id="\'+C.mute+\'" style="\'+sBtn+\'width:55px;height:55px;border-radius:50%">\'+svgMute+\'</button>\';'
			. 'if(C.ch_up&&C.ch_down){'
			. 'h+=\'<div style="display:flex;flex-direction:column;gap:8px">\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.ch_up+\'" style="\'+sVert+\'"><b style="font-size:18px">▲</b><span style="font-size:11px;font-weight:bold">CH</span></button>\';'
			. 'h+=\'<button class="jt-x" data-id="\'+C.ch_down+\'" style="\'+sVert+\'"><span style="font-size:11px;font-weight:bold">CH</span><b style="font-size:18px">▼</b></button>\';'
			. 'h+=\'</div>\';}'
			. 'h+=\'</div>\';'
			// RETOUR + HOME
			. 'h+=\'<div style="display:flex;width:100%;gap:10px">\';'
			. 'if(C["return"])h+=\'<button class="jt-x" data-id="\'+C["return"]+\'" style="\'+sRect+\'">\'+svgRet+\'<span style="font-size:11px">RETOUR</span></button>\';'
			. 'if(C.home)h+=\'<button class="jt-x" data-id="\'+C.home+\'" style="\'+sRect+\'">\'+svgHome+\'<span style="font-size:11px">HOME</span></button>\';'
			. 'h+=\'</div>\';'
			// TV + HDMI
			. 'h+=\'<div style="display:flex;width:100%;gap:8px">\';'
			. 'if(C.tv)h+=\'<button class="jt-x" data-id="\'+C.tv+\'" style="\'+sRect+\'font-size:12px">TV</button>\';'
			. 'if(C.hdmi1)h+=\'<button class="jt-x" data-id="\'+C.hdmi1+\'" style="\'+sRect+\'font-size:12px">HDMI 1</button>\';'
			. 'if(C.hdmi2)h+=\'<button class="jt-x" data-id="\'+C.hdmi2+\'" style="\'+sRect+\'font-size:12px">HDMI 2</button>\';'
			. 'h+=\'</div>\';'
			. 'h+=\'</div>\';'
			// Cacher natifs + injecter
			. 'w.querySelectorAll(".cmds>.action-buttons,.cmds>.cmd.cmd-widget[data-type=info]").forEach(function(el){el.style.display="none";});'
			. 'var cmds=w.querySelector(".cmds");'
			. 'if(cmds)cmds.insertAdjacentHTML("beforeend",h);'
			// Active effect
			. 'w.querySelectorAll(".jt-x").forEach(function(el){'
			. 'el.addEventListener("click",function(){ex(el.getAttribute("data-id"));});'
			. 'el.style.transition="filter 0.1s";'
			. 'el.addEventListener("mousedown",function(){el.style.filter="brightness(1.4)";});'
			. 'el.addEventListener("mouseup",function(){el.style.filter="";});'
			. 'el.addEventListener("mouseleave",function(){el.style.filter="";});'
			. '});'
			. '}'
			. 'build();setTimeout(build,500);setTimeout(build,1500);'
			. '})();'
			. '</script>';

		$pos = strpos($html, '>');
		if ($pos !== false) {
			// Cacher widget-name natif si template actif
			$hideStyle = '<style>[data-eqlogic_id="' . $eqId . '"] .widget-name{color:' . ($template == 'light' ? 'rgb(68,68,68)' : 'rgb(200,200,210)') . ' !important}[data-eqlogic_id="' . $eqId . '"] .widget-name a{color:inherit !important}[data-eqlogic_id="' . $eqId . '"] .widget-name .object_name{color:' . ($template == 'light' ? 'rgb(140,140,140)' : 'rgb(110,110,130)') . ' !important;font-size:0.8em}</style>';
			$html = substr($html, 0, $pos + 1) . $hideStyle . substr($html, $pos + 1);
		}

		return $html . $js;
	}

	/**
	 * Récupération des paramètres de configuration TV
	 * Instancie TvParametres pour le bon connecteur Samsung
	 */
	public function getTvParametres() {
		// Lazy loading des librairies Samsung
		if (!class_exists('TvConnector')) {
			include_file('core', 'Logger', 'class', 'JeeTizen');
			include_file('core', 'Telecommande/Connectors/JeeTizen.TvConnector', 'class', 'JeeTizen');
			include_file('core', 'Telecommande/JeeTizen.CommunicationStatus', 'class', 'JeeTizen');
		}
		return TvParametres::getInstanceFromConfig($this);
	}

	/*     * **********************Getteur Setteur*************************** */
}

class JeeTizenCmd extends cmd {
	/*     * *************************Attributs****************************** */

	const KEY_MAP = [
		'mute'     => 'KEY_MUTE',
		'vol_up'   => 'KEY_VOLUP',
		'vol_down' => 'KEY_VOLDOWN',
		'ch_up'    => 'KEY_CHUP',
		'ch_down'  => 'KEY_CHDOWN',
		'source'   => 'KEY_SOURCE',
		'up'       => 'KEY_UP',
		'down'     => 'KEY_DOWN',
		'left'     => 'KEY_LEFT',
		'right'    => 'KEY_RIGHT',
		'enter'    => 'KEY_ENTER',
		'return'   => 'KEY_RETURN',
		'home'     => 'KEY_HOME',
		'tv'       => 'KEY_TV',
		'hdmi1'    => 'KEY_HDMI1',
		'hdmi2'    => 'KEY_HDMI2',
	];

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		$defaults = array_column(JeeTizen::DEFAULT_COMMANDS, 0);
		return in_array($this->getLogicalId(), $defaults);
	}

	public function execute($_options = array()) {
		if ($this->type != 'action') {
			return;
		}

		$eqLogic = $this->getEqLogic();
		$logId   = $this->getLogicalId();
		$tvParametres = $eqLogic->getTvParametres();

		Logger::debug('Exec commande', $this->getName() . '/' . $logId);

		// Commandes simples mappées vers une touche
		if (isset(self::KEY_MAP[$logId])) {
			$this->sendKey(self::KEY_MAP[$logId], $tvParametres);
			return;
		}

		switch ($logId) {
			case 'on_off':
				$this->sendKey('KEY_POWER', $tvParametres);
				break;

			case 'off':
				$tvParametres->setWolOff();
				$keyOff = $tvParametres->isLegacyTv() ? 'KEY_POWEROFF' : 'KEY_POWER';
				$this->sendKey($keyOff, $tvParametres);
				break;

			default:
				Logger::error('Commande inconnue', $this->getName() . '/' . $logId);
				break;
		}
	}

	private function getTvParametres() {
		return $this->getEqLogic()->getTvParametres();
	}

	private function sendKey($tv_key, $tvParametres = null) {
		Logger::debug('sendKey (1 touche) ->', $tv_key);
		if ($tvParametres === null) {
			$tvParametres = $this->getTvParametres();
		}
		$communicationStatus = TvConnector::sendToTv($tvParametres, $tv_key);
		Logger::debug('sendToTv.$communicationStatus:', $communicationStatus->toString());
		if ($communicationStatus->getStatus() != 0) {
			Logger::error('Erreur Tv sendkey() ' . $communicationStatus->toString(), '');
			throw new Exception(__('Erreur Tv sendKey()', __FILE__) . ' ' . $communicationStatus->toString());
		}
		if ($communicationStatus->getCommande_Type() == 'daemon_cmd') {
			$_comm_status = json_decode($communicationStatus->getCommande_Message(), true);
			if (isset($_comm_status['status']) && $_comm_status['status'] == 'KO') {
				Logger::error('Erreur Tv sendkey() ' . $_comm_status['message'], '');
				throw new Exception(__('Erreur [service] Tv sendKey()', __FILE__) . ' ' . $_comm_status['message']);
			}
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
