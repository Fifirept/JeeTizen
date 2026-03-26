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
		// [logicalId, nom, type, subType, isVisible, config]
		['on_off',   'Marche/Arrêt', 'action', 'other',   1, []],
		['off',      'Extinction',   'action', 'other',   1, []],
		['mute',     'Mute',         'action', 'other',   1, []],
		['vol_up',   'Volume +',     'action', 'other',   1, []],
		['vol_down', 'Volume -',     'action', 'other',   1, []],
		['ch_up',    'Chaîne +',     'action', 'other',   1, []],
		['ch_down',  'Chaîne -',     'action', 'other',   1, []],
		['source',   'Source',       'action', 'other',   1, []],
		['state',    'Etat',         'info',   'binary',  1, []],
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
		foreach (self::DEFAULT_COMMANDS as [$logicalId, $name, $type, $subType, $isVisible, $config]) {
			// Chercher par logicalId d'abord
			$cmd = $this->getCmd($type, $logicalId);
			// Chercher aussi par nom (migration depuis ancienne version)
			if (!is_object($cmd)) {
				$cmd = $this->getCmd(null, $logicalId);
			}
			if (!is_object($cmd)) {
				// Vérifier qu'une commande avec ce nom n'existe pas déjà
				foreach ($this->getCmd() as $existingCmd) {
					if ($existingCmd->getName() == $name) {
						// Mettre à jour le logicalId de la commande existante
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

	/**
	 * Widget dashboard personnalisé style télécommande Samsung
	 */
	public function toHtml($_version = 'dashboard') {
		$_version = jeedom::versionAlias($_version);

		// Récupérer les commandes
		$cmds = array();
		foreach ($this->getCmd() as $cmd) {
			$cmds[$cmd->getLogicalId()] = $cmd;
		}

		// État de la TV
		$stateValue = 0;
		$stateClass = 'jt-off';
		if (isset($cmds['state']) && is_object($cmds['state'])) {
			$stateValue = $cmds['state']->execCmd();
			$stateClass = $stateValue ? 'jt-on' : 'jt-off';
		}

		// Construire les boutons
		$btnPower = isset($cmds['on_off']) ? '#' . $cmds['on_off']->getId() . '#' : '';
		$btnOff = isset($cmds['off']) ? '#' . $cmds['off']->getId() . '#' : '';
		$btnMute = isset($cmds['mute']) ? '#' . $cmds['mute']->getId() . '#' : '';
		$btnVolUp = isset($cmds['vol_up']) ? '#' . $cmds['vol_up']->getId() . '#' : '';
		$btnVolDown = isset($cmds['vol_down']) ? '#' . $cmds['vol_down']->getId() . '#' : '';
		$btnChUp = isset($cmds['ch_up']) ? '#' . $cmds['ch_up']->getId() . '#' : '';
		$btnChDown = isset($cmds['ch_down']) ? '#' . $cmds['ch_down']->getId() . '#' : '';
		$btnSource = isset($cmds['source']) ? '#' . $cmds['source']->getId() . '#' : '';

		$eqId = $this->getId();
		$name = $this->getName();
		$uid = 'jeetizen_' . $eqId . '_' . mt_rand();

		$html = <<<HTML
<div class="eqLogic eqLogic-widget allowResize multimedia" data-eqlogic_id="{$eqId}" data-eqlogic_uid="{$uid}" data-version="{$_version}" style="min-width:220px;min-height:200px;">
<style>
.jt-remote-{$eqId} {
	background: linear-gradient(145deg, #1a1a2e, #16213e);
	border-radius: 16px;
	padding: 12px;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
	color: #e0e0e0;
	box-shadow: 0 4px 20px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.05);
}
.jt-remote-{$eqId} .jt-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 10px;
	padding-bottom: 8px;
	border-bottom: 1px solid rgba(255,255,255,0.08);
}
.jt-remote-{$eqId} .jt-title {
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 0.5px;
	color: #fff;
}
.jt-remote-{$eqId} .jt-status {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	transition: all 0.3s;
}
.jt-remote-{$eqId} .jt-on { background: #00e676; box-shadow: 0 0 8px rgba(0,230,118,0.6); }
.jt-remote-{$eqId} .jt-off { background: #555; }
.jt-remote-{$eqId} .jt-controls {
	display: grid;
	grid-template-columns: 1fr 1fr 1fr;
	gap: 6px;
}
.jt-remote-{$eqId} .jt-btn {
	background: linear-gradient(145deg, #2a2a4a, #1e1e3a);
	border: 1px solid rgba(255,255,255,0.06);
	border-radius: 10px;
	color: #ccc;
	font-size: 18px;
	padding: 10px 0;
	cursor: pointer;
	text-align: center;
	transition: all 0.15s ease;
	user-select: none;
	line-height: 1;
}
.jt-remote-{$eqId} .jt-btn:hover {
	background: linear-gradient(145deg, #3a3a5a, #2e2e4a);
	color: #fff;
	transform: scale(1.03);
}
.jt-remote-{$eqId} .jt-btn:active {
	transform: scale(0.96);
	background: linear-gradient(145deg, #1a1a3a, #151530);
}
.jt-remote-{$eqId} .jt-btn-power {
	background: linear-gradient(145deg, #4a1a1a, #3a1010);
	border-color: rgba(255,60,60,0.15);
	color: #ff5252;
}
.jt-remote-{$eqId} .jt-btn-power:hover {
	background: linear-gradient(145deg, #5a2a2a, #4a1a1a);
	color: #ff7070;
}
.jt-remote-{$eqId} .jt-btn-mute {
	font-size: 14px;
}
.jt-remote-{$eqId} .jt-btn-source {
	grid-column: span 3;
	font-size: 12px;
	padding: 8px 0;
	letter-spacing: 1px;
	text-transform: uppercase;
	color: #80cbc4;
}
.jt-remote-{$eqId} .jt-label {
	font-size: 9px;
	display: block;
	margin-top: 2px;
	opacity: 0.5;
	letter-spacing: 0.5px;
}
</style>
<div class="jt-remote-{$eqId}">
	<div class="jt-header">
		<a href="index.php?v=d&p=JeeTizen&m=JeeTizen&id={$eqId}" class="jt-title" style="color:#fff;text-decoration:none;">{$name}</a>
		<div class="jt-status {$stateClass}" title="{STATE_LABEL}"></div>
	</div>
	<div class="jt-controls">
		<div class="jt-btn jt-btn-power" onclick="jeedom.cmd.execute({id:'{POWER_ID}'})">⏻<span class="jt-label">ON/OFF</span></div>
		<div class="jt-btn jt-btn-mute" onclick="jeedom.cmd.execute({id:'{MUTE_ID}'})">🔇<span class="jt-label">MUTE</span></div>
		<div class="jt-btn" onclick="jeedom.cmd.execute({id:'{OFF_ID}'})">⏼<span class="jt-label">OFF</span></div>
		<div class="jt-btn" onclick="jeedom.cmd.execute({id:'{VOL_UP_ID}'})">🔊<span class="jt-label">VOL+</span></div>
		<div class="jt-btn" onclick="jeedom.cmd.execute({id:'{CH_UP_ID}'})">▲<span class="jt-label">CH+</span></div>
		<div class="jt-btn" onclick="jeedom.cmd.execute({id:'{VOL_DOWN_ID}'})">🔉<span class="jt-label">VOL-</span></div>
		<div class="jt-btn" style="visibility:hidden;"></div>
		<div class="jt-btn" onclick="jeedom.cmd.execute({id:'{CH_DOWN_ID}'})">▼<span class="jt-label">CH-</span></div>
		<div class="jt-btn" style="visibility:hidden;"></div>
		<div class="jt-btn jt-btn-source" onclick="jeedom.cmd.execute({id:'{SOURCE_ID}'})">⎆ Source</div>
	</div>
</div>
</div>
HTML;

		// Remplacer les IDs
		$replacements = array(
			'{POWER_ID}'  => isset($cmds['on_off']) ? $cmds['on_off']->getId() : '',
			'{OFF_ID}'    => isset($cmds['off']) ? $cmds['off']->getId() : '',
			'{MUTE_ID}'   => isset($cmds['mute']) ? $cmds['mute']->getId() : '',
			'{VOL_UP_ID}' => isset($cmds['vol_up']) ? $cmds['vol_up']->getId() : '',
			'{VOL_DOWN_ID}' => isset($cmds['vol_down']) ? $cmds['vol_down']->getId() : '',
			'{CH_UP_ID}'  => isset($cmds['ch_up']) ? $cmds['ch_up']->getId() : '',
			'{CH_DOWN_ID}' => isset($cmds['ch_down']) ? $cmds['ch_down']->getId() : '',
			'{SOURCE_ID}' => isset($cmds['source']) ? $cmds['source']->getId() : '',
			'{STATE_LABEL}' => $stateValue ? 'TV allumée' : 'TV éteinte',
		);
		$html = str_replace(array_keys($replacements), array_values($replacements), $html);

		return $html;
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
