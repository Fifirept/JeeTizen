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
	 * Widget télécommande Samsung
	 * - "none" : widget natif Jeedom avec icônes 2x plus grosses
	 * - "dark"/"light" : image PNG cliquable → modale avec télécommande HTML
	 */
	public function toHtml($_version = 'dashboard') {
		$template = $this->getConfiguration('widget_template', 'dark');
		$eqId = $this->getId();

		// --- Template "aucun" : widget natif avec icônes 2× plus grosses ---
		if ($template == 'none') {
			$html = parent::toHtml($_version);
			if ($html == '') {
				return '';
			}
			$bigCss = '<style>'
				. '[data-eqlogic_id="' . $eqId . '"] .cmd.cmd-widget .execute{font-size:28px !important;min-width:60px !important;min-height:50px !important;padding:10px 14px !important}'
				. '[data-eqlogic_id="' . $eqId . '"] .cmd.cmd-widget .execute i{font-size:28px !important}'
				. '</style>';
			$pos = strpos($html, '>');
			if ($pos !== false) {
				$html = substr($html, 0, $pos + 1) . $bigCss . substr($html, $pos + 1);
			}
			return $html;
		}

		// --- Templates sombre/clair : image PNG → modale télécommande ---
		$_version = jeedom::versionAlias($_version);
		if (!$this->hasRight('r')) {
			return '';
		}

		// Charger le fichier template HTML
		$tplFile = ($template == 'light') ? 'light' : 'dark';
		$tplPath = __DIR__ . '/../template/widget/remote_' . $tplFile . '.html';
		if (!file_exists($tplPath)) {
			return parent::toHtml($_version);
		}
		$tplHtml = file_get_contents($tplPath);

		// Map logicalId -> cmd_id
		$cmdMap = array();
		foreach ($this->getCmd() as $cmd) {
			$lid = $cmd->getLogicalId();
			if (!empty($lid)) {
				$cmdMap[$lid] = $cmd->getId();
			}
		}
		if (count($cmdMap) < 3) {
			return parent::toHtml($_version);
		}

		// État
		$stateCmd = $this->getCmd('info', 'state');
		$stateOn = (is_object($stateCmd) && $stateCmd->execCmd());
		$ledColor = $stateOn ? 'rgb(0,200,100)' : 'rgb(80,80,80)';

		// Remplacements dans le template
		$replace = array('#eqId#' => $eqId, '#ledColor#' => $ledColor);
		$allCmds = array('on_off','source','up','down','left','right','enter',
			'vol_up','vol_down','mute','ch_up','ch_down','return','home','tv','hdmi1','hdmi2');
		foreach ($allCmds as $logId) {
			$replace['#cmd_' . $logId . '#'] = isset($cmdMap[$logId]) ? $cmdMap[$logId] : '';
		}
		$tplHtml = str_replace(array_keys($replace), array_values($replace), $tplHtml);

		// Échapper le HTML pour JS
		$tplEscaped = json_encode($tplHtml);

		$name = $this->getName();
		$uid = 'eqLogic' . $eqId . '__' . mt_rand() . '__';
		$eqLink = 'index.php?v=d&p=JeeTizen&m=JeeTizen&id=' . $eqId;

		// Widget compact : image + LED + nom
		$html = '<div class="eqLogic eqLogic-widget allowResize multimedia" '
			. 'data-eqtype="JeeTizen" '
			. 'data-eqlogic_id="' . $eqId . '" '
			. 'data-eqlogic_uid="' . $uid . '" '
			. 'data-version="' . $_version . '" '
			. 'data-category="multimedia" '
			. 'style="margin:4px;padding:0px;height:120px;width:116px;">';

		// Nom widget
		$html .= '<center class="widget-name">'
			. '<a href="' . $eqLink . '" style="font-size:1.1em;">' . $name . '</a>'
			. '</center>';

		// Div modale (cachée)
		$html .= '<div id="md_modal_jt_' . $eqId . '"></div>';

		// LED état
		$html .= '<div style="text-align:center;margin:2px 0;">'
			. '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . $ledColor . '"></span>'
			. '</div>';

		// Image cliquable
		$html .= '<img class="jt-tv-img-' . $eqId . '" '
			. 'src="plugins/JeeTizen/plugin_info/JeeTizen_icon.png" '
			. 'style="max-width:100%;max-height:100%;cursor:pointer;display:block;margin:0 auto;"/>';

		// Script modale jQuery UI
		$html .= '<script>'
			. '(function(){'
			. 'var eqId="' . $eqId . '";'
			. 'var tpl=' . $tplEscaped . ';'
			. 'var name="' . addslashes($name) . '";'
			// Init modale
			. '$("#md_modal_jt_"+eqId).dialog({'
			. 'modal:true,autoOpen:false,title:name,'
			. 'width:300,border:0,resizable:false,'
			. 'position:{my:"center",at:"center",of:window},'
			. 'closeText:"",'
			. 'open:function(){$(this).css("overflow","auto").css("padding","0");}'
			. '});'
			// Clic image → ouvrir modale
			. '$(".jt-tv-img-"+eqId).on("click",function(){'
			. 'var $m=$("#md_modal_jt_"+eqId);'
			. '$m.html(tpl).dialog("open");'
			. '});'
			. '})();'
			. '</script>';

		$html .= '</div>';

		return $html;
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
