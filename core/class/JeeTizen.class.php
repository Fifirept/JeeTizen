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
	 * Widget avec style custom - appelle le rendu natif + injecte le CSS
	 */
	public function toHtml($_version = 'dashboard') {
		$html = parent::toHtml($_version);
		if ($html == '') {
			return '';
		}

		$eqId = $this->getId();
		$W = '.eqLogic-widget[data-eqtype="JeeTizen"]';

		$css = '<style>'
			/* Fond sombre */
			. $W . '{background:rgb(30,32,44) !important;border:none !important}'
			/* Nom widget */
			. $W . ' .widget-name a,' . $W . ' .widget-name span{color:rgb(200,200,210) !important;font-weight:600 !important}'
			. $W . ' .widget-name .object_name{color:rgb(110,110,130) !important;font-size:0.8em;font-weight:400 !important}'
			/* Cacher les boutons originaux - on les reconstruit */
			. $W . ' .cmds>.action-buttons,' . $W . ' .cmds>.cmd.cmd-widget[data-type="info"]{display:none !important}'
			/* Container custom */
			. $W . ' .jt-remote{display:flex;flex-direction:column;align-items:center;gap:10px;padding:8px 4px}'
			/* Rangée */
			. $W . ' .jt-row{display:flex;align-items:center;justify-content:center;gap:10px}'
			/* Bouton rond simple */
			. $W . ' .jt-btn{'
			. 'background:rgb(42,44,58);'
			. 'border:none;border-radius:50%;'
			. 'box-shadow:3px 3px 6px rgba(0,0,0,0.4),-2px -2px 5px rgba(60,62,80,0.5);'
			. 'color:rgb(160,165,200);width:42px;height:42px;'
			. 'display:inline-flex;align-items:center;justify-content:center;'
			. 'font-size:16px;cursor:pointer;transition:all .12s;user-select:none}'
			. $W . ' .jt-btn:hover{color:rgb(200,205,240);box-shadow:4px 4px 8px rgba(0,0,0,0.5),-3px -3px 6px rgba(60,62,80,0.6)}'
			. $W . ' .jt-btn:active{box-shadow:inset 2px 2px 4px rgba(0,0,0,0.4),inset -2px -2px 4px rgba(60,62,80,0.3)}'
			/* Power */
			. $W . ' .jt-btn-pwr{color:rgb(200,70,80)}'
			. $W . ' .jt-btn-pwr:hover{color:rgb(240,90,100)}'
			/* Bloc vertical (VOL, CH) */
			. $W . ' .jt-vblock{'
			. 'background:rgb(42,44,58);'
			. 'border-radius:22px;'
			. 'box-shadow:3px 3px 6px rgba(0,0,0,0.4),-2px -2px 5px rgba(60,62,80,0.5);'
			. 'display:flex;flex-direction:column;align-items:center;'
			. 'padding:4px 6px;gap:2px;min-width:48px}'
			/* Boutons dans un bloc vertical */
			. $W . ' .jt-vblock .jt-vbtn{'
			. 'background:none;border:none;color:rgb(160,165,200);'
			. 'width:40px;height:32px;display:flex;align-items:center;justify-content:center;'
			. 'font-size:16px;cursor:pointer;transition:color .12s;border-radius:10px}'
			. $W . ' .jt-vblock .jt-vbtn:hover{color:rgb(220,220,255);background:rgba(255,255,255,0.04)}'
			. $W . ' .jt-vblock .jt-vbtn:active{background:rgba(0,0,0,0.15)}'
			/* Label dans bloc */
			. $W . ' .jt-vblock .jt-vlbl{color:rgb(120,125,160);font-size:9px;font-weight:600;letter-spacing:0.5px;user-select:none}'
			/* Source pill */
			. $W . ' .jt-pill{'
			. 'background:rgb(42,44,58);'
			. 'border-radius:18px;'
			. 'box-shadow:3px 3px 6px rgba(0,0,0,0.4),-2px -2px 5px rgba(60,62,80,0.5);'
			. 'display:inline-flex;align-items:center;gap:6px;'
			. 'padding:8px 16px;color:rgb(160,165,200);font-size:11px;font-weight:600;letter-spacing:0.5px;'
			. 'cursor:pointer;transition:all .12s;user-select:none}'
			. $W . ' .jt-pill:hover{color:rgb(220,220,255)}'
			. $W . ' .jt-pill:active{box-shadow:inset 2px 2px 4px rgba(0,0,0,0.4),inset -2px -2px 4px rgba(60,62,80,0.3)}'
			/* LED état */
			. $W . ' .jt-led{width:6px;height:6px;border-radius:50%;display:inline-block;margin-bottom:2px}'
			. '</style>';

		// JS : reconstruire le layout
		$js = '<script>'
			. '(function(){'
			. 'var w=document.querySelector(\'[data-eqlogic_id="' . $eqId . '"]\');'
			. 'if(!w||w.querySelector(".jt-remote"))return;'
			// Récupérer les IDs des commandes par leur texte
			. 'var ids={};'
			. 'w.querySelectorAll(".execute").forEach(function(b){'
			. 'ids[b.textContent.trim()]=b.closest(".cmd").getAttribute("data-cmd_id");'
			. '});'
			// Déterminer état
			. 'var stOn=!!w.querySelector(".icon_green");'
			. 'var ledC=stOn?"rgb(0,200,100)":"rgb(80,80,80)";'
			// Helper
			. 'function mkBtn(label,cls){'
			. 'var id=ids[label];if(!id)return"";'
			. 'return id;'
			. '}'
			. 'function cmd(id){jeedom.cmd.execute({id:id});}'
			// Construire le HTML custom
			. 'var h=\'<div class="jt-remote">\';'
			// LED
			. 'h+=\'<div class="jt-led" style="background:\'+ledC+\'"></div>\';'
			// Rangée 1 : Power + Mute + Source
			. 'h+=\'<div class="jt-row">\';'
			. 'if(ids["MarcheArr\\u00eat"]||ids["Marche/Arr\\u00eat"]){'
			. 'var pid=ids["MarcheArr\\u00eat"]||ids["Marche/Arr\\u00eat"];'
			. 'h+=\'<div class="jt-btn jt-btn-pwr" data-id="\'+pid+\'"><i class="fas fa-power-off"></i></div>\';}'
			. 'if(ids["Mute"]){'
			. 'h+=\'<div class="jt-btn" data-id="\'+ids["Mute"]+\'"><i class="fas fa-volume-mute"></i></div>\';}'
			. 'if(ids["Source"]){'
			. 'h+=\'<div class="jt-pill" data-id="\'+ids["Source"]+\'"><i class="fas fa-sign-in-alt"></i> SOURCE</div>\';}'
			. 'h+=\'</div>\';'
			// Rangée 2 : VOL block + CH block
			. 'h+=\'<div class="jt-row">\';'
			// VOL block
			. 'if(ids["Volume +"]&&ids["Volume -"]){'
			. 'h+=\'<div class="jt-vblock">\';'
			. 'h+=\'<div class="jt-vbtn" data-id="\'+ids["Volume +"]+\'"><i class="fas fa-plus"></i></div>\';'
			. 'h+=\'<div class="jt-vlbl">VOL</div>\';'
			. 'h+=\'<div class="jt-vbtn" data-id="\'+ids["Volume -"]+\'"><i class="fas fa-minus"></i></div>\';'
			. 'h+=\'</div>\';}'
			// Extinction au centre
			. 'if(ids["Extinction"]){'
			. 'h+=\'<div class="jt-btn" data-id="\'+ids["Extinction"]+\'" style="width:36px;height:36px;font-size:13px"><i class="fas fa-stop"></i></div>\';}'
			// CH block
			. 'if(ids["Cha\\u00eene +"]&&ids["Cha\\u00eene -"]){'
			. 'h+=\'<div class="jt-vblock">\';'
			. 'h+=\'<div class="jt-vbtn" data-id="\'+ids["Cha\\u00eene +"]+\'"><i class="fas fa-chevron-up"></i></div>\';'
			. 'h+=\'<div class="jt-vlbl">CH</div>\';'
			. 'h+=\'<div class="jt-vbtn" data-id="\'+ids["Cha\\u00eene -"]+\'"><i class="fas fa-chevron-down"></i></div>\';'
			. 'h+=\'</div>\';}'
			. 'h+=\'</div>\';'
			// Fin
			. 'h+=\'</div>\';'
			// Injecter dans .cmds
			. 'var cmds=w.querySelector(".cmds");'
			. 'if(cmds){cmds.insertAdjacentHTML("beforeend",h);}'
			// Bind clicks
			. 'w.querySelectorAll("[data-id]").forEach(function(el){'
			. 'el.addEventListener("click",function(){cmd(el.getAttribute("data-id"));});'
			. '});'
			. '})();'
			. '</script>';

		$pos = strpos($html, '>');
		if ($pos !== false) {
			$html = substr($html, 0, $pos + 1) . $css . substr($html, $pos + 1);
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
