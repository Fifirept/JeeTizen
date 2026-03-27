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
		$eqId     = $this->getId();

		if ($template == 'none') {
			$html = parent::toHtml($_version);
			if ($html == '') return '';
			$bigCss = '<style>'
				. '[data-eqlogic_id="' . $eqId . '"] .cmd.cmd-widget .execute{font-size:28px !important;min-width:60px !important;min-height:50px !important;padding:10px 14px !important}'
				. '[data-eqlogic_id="' . $eqId . '"] .cmd.cmd-widget .execute i{font-size:28px !important}'
				. '</style>';
			$pos = strpos($html, '>');
			if ($pos !== false) $html = substr($html, 0, $pos + 1) . $bigCss . substr($html, $pos + 1);
			return $html;
		}

		$_version = jeedom::versionAlias($_version);
		if (!$this->hasRight('r')) return '';

		$cmdMap = array();
		foreach ($this->getCmd() as $cmd) {
			$lid = $cmd->getLogicalId();
			if (!empty($lid)) $cmdMap[$lid] = $cmd->getId();
		}
		if (count($cmdMap) < 3) return parent::toHtml($_version);

		$c = function($logId) use ($cmdMap) {
			return isset($cmdMap[$logId]) ? $cmdMap[$logId] : '';
		};

		$stateCmd = $this->getCmd('info', 'state');
		$stateOn  = (is_object($stateCmd) && $stateCmd->execCmd());
		$ledColor = $stateOn ? 'rgb(0,200,100)' : 'rgb(80,80,80)';

		$name   = $this->getName();
		$uid    = 'eqLogic' . $eqId . '__' . mt_rand() . '__';
		$eqLink = 'index.php?v=d&p=JeeTizen&m=JeeTizen&id=' . $eqId;
		$isDark = ($template !== 'light');

		$scale = (int)$this->getConfiguration('widget_scale', 100);
		if (!in_array($scale, array(30, 50, 75, 100))) $scale = 100;
		$ratio = round($scale / 100.0, 2);

		// ── Palette ──────────────────────────────────────────────────────────
		if ($isDark) {
			$bg='#111111'; $btn='#2d2d2d'; $bord='#3a3a3a';
			$txt='#ffffff'; $logo='#2a2a2a';
			$pg1='#2a2a2a'; $pg2='#1a1a1a';
			$og1='#4a4a4a'; $og2='#282828'; $okc='#cccccc';
			$mg1='#3a3a3a'; $mg2='#1e1e1e'; $mb='#444444'; $mf='#cccccc';
			$af='#999999';
		} else {
			$bg='#e0e0e0'; $btn='#cacaca'; $bord='#aaaaaa';
			$txt='#111111'; $logo='#bbbbbb';
			$pg1='#c8c8c8'; $pg2='#a8a8a8';
			$og1='#d8d8d8'; $og2='#b0b0b0'; $okc='#222222';
			$mg1='#c0c0c0'; $mg2='#a0a0a0'; $mb='#999999'; $mf='#333333';
			$af='#555555';
		}

		$styleId = 'jt-style-' . $eqId . '-' . $template . '-' . $scale;

		// ── CSS ───────────────────────────────────────────────────────────────
		$css = ''
			// Neutraliser les resets Bootstrap/Jeedom sur button dans notre remote
			. '#jt-remote-' . $eqId . ' button,'
			. '#jt-remote-' . $eqId . ' button:focus,'
			. '#jt-remote-' . $eqId . ' button:active{'
			.   'outline:none;box-shadow:none;'
			. '}'
			// Telecommande — le scale est applique ici via zoom (pas transform)
			// zoom n affecte pas border-radius contrairement a transform:scale
			. '#jt-remote-' . $eqId . '{'
			.   'background:' . $bg . ' !important;'
			.   'padding:25px 20px 20px;border-radius:40px;width:280px;'
			.   'display:flex;flex-direction:column;align-items:center;gap:14px;'
			.   'box-shadow:0 8px 40px rgba(0,0,0,0.95);border:1px solid ' . $bord . ';'
			.   'box-sizing:border-box;'
			. '}'
			. '#jt-remote-' . $eqId . ' .row{'
			.   'display:flex;width:100%;justify-content:center;gap:10px;align-items:center;'
			. '}'
			// ── Pad ──
			. '#jt-remote-' . $eqId . ' .pad-wrap{'
			.   'position:relative;width:210px;height:210px;flex-shrink:0;'
			. '}'
			. '#jt-remote-' . $eqId . ' .pad-bg{'
			.   'position:absolute;top:0;left:0;width:100%;height:100%;border-radius:50%;'
			.   'background:radial-gradient(circle at 50% 50%,' . $pg1 . ' 0%,' . $pg2 . ' 100%);'
			.   'box-shadow:0 4px 20px rgba(0,0,0,0.8),inset 0 1px 0 rgba(255,255,255,0.05);'
			. '}'
			. '#jt-remote-' . $eqId . ' .pad-arrows{'
			.   'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:2;'
			. '}'
			. '#jt-remote-' . $eqId . ' .pad-canvas{'
			.   'position:absolute;top:0;left:0;width:210px;height:210px;'
			.   'border-radius:50%;cursor:pointer;z-index:3;display:block;'
			. '}'
			// Bouton OK remplace par un <div role=button>
			// Les <div> ne sont pas affectes par le reset Bootstrap button{border-radius:0}
			. '#jt-remote-' . $eqId . ' .ok{'
			.   'position:absolute;top:50%;left:50%;width:90px;height:90px;'
			.   'transform:translate(-50%,-50%);'
			.   'border-radius:50%;'
			.   'background:radial-gradient(circle at 40% 35%,' . $og1 . ',' . $og2 . ');'
			.   'border:1px solid ' . $mb . ';color:' . $okc . ';'
			.   'font-weight:700;font-size:20px;z-index:10;cursor:pointer;'
			.   'display:flex;justify-content:center;align-items:center;'
			.   'box-shadow:0 4px 14px rgba(0,0,0,0.7),inset 0 1px 0 rgba(255,255,255,0.08);'
			.   'letter-spacing:1px;user-select:none;-webkit-user-select:none;'
			. '}'
			. '#jt-remote-' . $eqId . ' .ok:active{filter:brightness(1.3);}'
			// Boutons VOL/CH
			. '#jt-remote-' . $eqId . ' .btn-vert{'
			.   'background:' . $btn . ' !important;border:1px solid ' . $bord . ';color:' . $txt . ';'
			.   'border-radius:12px !important;width:62px;height:82px;cursor:pointer;'
			.   'display:flex;flex-direction:column;align-items:center;justify-content:center;'
			.   'padding:0;gap:4px;box-shadow:0 2px 8px rgba(0,0,0,0.4);'
			. '}'
			. '#jt-remote-' . $eqId . ' .btn-vert svg{width:22px;height:22px;display:block;}'
			. '#jt-remote-' . $eqId . ' .btn-vert span{font-size:13px;font-weight:700;color:' . $txt . ';line-height:1;}'
			// Bouton MUTE — aussi un <div role=button>
			. '#jt-remote-' . $eqId . ' .btn-mute{'
			.   'width:42px;height:42px;min-width:42px;border-radius:50%;'
			.   'background:radial-gradient(circle at 40% 35%,' . $mg1 . ',' . $mg2 . ');'
			.   'border:1px solid ' . $mb . ';cursor:pointer;'
			.   'display:flex;justify-content:center;align-items:center;'
			.   'box-shadow:0 2px 8px rgba(0,0,0,0.5);user-select:none;flex-shrink:0;'
			. '}'
			// Boutons RETOUR/HOME
			. '#jt-remote-' . $eqId . ' .btn-rect{'
			.   'background:' . $btn . ' !important;border:1px solid ' . $bord . ';color:' . $txt . ';'
			.   'border-radius:12px !important;height:62px;flex:1;cursor:pointer;'
			.   'display:flex;flex-direction:column;align-items:center;justify-content:center;'
			.   'gap:4px;box-shadow:0 2px 8px rgba(0,0,0,0.4);padding:0;'
			. '}'
			. '#jt-remote-' . $eqId . ' .btn-rect svg{width:24px;height:24px;fill:' . $txt . ';}'
			. '#jt-remote-' . $eqId . ' .btn-rect span{font-size:12px;font-weight:700;color:' . $txt . ';line-height:1;}'
			// Bouton POWER — aussi un <div role=button>
			. '#jt-remote-' . $eqId . ' .btn-power{'
			.   'width:62px;height:62px;border-radius:50%;'
			.   'background:radial-gradient(circle at 38% 32%,#ff4444 0%,#cc0000 55%,#880000 100%);'
			.   'display:flex;justify-content:center;align-items:center;'
			.   'box-shadow:0 3px 12px rgba(200,0,0,0.5);'
			.   'cursor:pointer;flex-shrink:0;user-select:none;'
			. '}'
			. '#jt-remote-' . $eqId . ' .btn-power svg{width:32px;height:32px;stroke:white;fill:none;stroke-width:2.5;stroke-linecap:round;}'
			// Bouton SOURCE
			. '#jt-remote-' . $eqId . ' .btn-source{'
			.   'height:38px;padding:0 14px;'
			.   'border-radius:8px !important;'
			.   'background:' . $btn . ' !important;border:1px solid ' . $bord . ';'
			.   'color:' . $txt . ';font-weight:700;font-size:13px;'
			.   'display:flex;align-items:center;justify-content:center;'
			.   'gap:6px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.4);'
			. '}'
			. '#jt-remote-' . $eqId . ' .btn-source svg{width:16px;height:16px;flex-shrink:0;}'
			// Boutons TV/HDMI
			. '#jt-remote-' . $eqId . ' .btn-flat{'
			.   'background:' . $btn . ' !important;border:1px solid ' . $bord . ';color:' . $txt . ';'
			.   'border-radius:10px !important;height:42px;flex:1;font-weight:700;font-size:13px;cursor:pointer;'
			.   'display:flex;align-items:center;justify-content:center;'
			.   'box-shadow:0 2px 8px rgba(0,0,0,0.4);padding:0;'
			. '}'
			. '#jt-remote-' . $eqId . ' button:active,'
			. '#jt-remote-' . $eqId . ' .ok:active,'
			. '#jt-remote-' . $eqId . ' .btn-power:active,'
			. '#jt-remote-' . $eqId . ' .btn-mute:active{filter:brightness(1.35);}'
			. '#jt-remote-' . $eqId . ' .samsung-logo{'
			.   'font-size:22px;font-weight:900;color:' . $logo . ';letter-spacing:5px;'
			.   'margin-top:4px;text-transform:uppercase;'
			. '}'
		;

		// ── SVG icons ─────────────────────────────────────────────────────────
		$svgPower   = '<svg viewBox="0 0 24 24"><path d="M12 3v9"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/></svg>';
		$svgSource  = '<svg viewBox="0 0 24 24" fill="none" stroke="' . $txt . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="15" rx="2"/><path d="M10 12h6m-3-3 3 3-3 3"/></svg>';
		$svgMute    = '<svg viewBox="0 0 24 24" fill="none" stroke="' . $mf . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5 6 9H2v6h4l5 4V5z" fill="' . $mf . '" stroke="none"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>';
		$svgVolUp   = '<svg viewBox="0 0 24 24"><polygon points="12,4 20,16 4,16" fill="' . $txt . '"/></svg>';
		$svgVolDown = '<svg viewBox="0 0 24 24"><polygon points="12,20 4,8 20,8" fill="' . $txt . '"/></svg>';
		$svgChUp    = '<svg viewBox="0 0 24 24"><polygon points="12,4 20,16 4,16" fill="' . $txt . '"/></svg>';
		$svgChDown  = '<svg viewBox="0 0 24 24"><polygon points="12,20 4,8 20,8" fill="' . $txt . '"/></svg>';
		$svgReturn  = '<svg viewBox="0 0 24 24" fill="none" stroke="' . $txt . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9,14 4,9 9,4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>';
		$svgHome    = '<svg viewBox="0 0 24 24" fill="' . $txt . '"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
		$svgArrows  = '<svg class="pad-arrows" viewBox="0 0 210 210">'
		            . '<polygon points="105,22 94,42 116,42" fill="' . $af . '"/>'
		            . '<polygon points="105,188 94,168 116,168" fill="' . $af . '"/>'
		            . '<polygon points="22,105 42,94 42,116" fill="' . $af . '"/>'
		            . '<polygon points="188,105 168,94 168,116" fill="' . $af . '"/>'
		            . '</svg>';

		// ── HTML télécommande ─────────────────────────────────────────────────
		// .ok, .btn-power, .btn-mute sont des <div role="button"> et non des <button>
		// => non affectes par le reset Bootstrap button{border-radius:0}
		$r  = '<div id="jt-remote-' . $eqId . '">';
		$r .= '<div style="width:7px;height:7px;border-radius:50%;background:' . $ledColor . ';margin:0 auto;box-shadow:0 0 5px ' . $ledColor . ';"></div>';

		$r .= '<div class="row" style="justify-content:space-between;">';
		$r .=   '<div class="btn-power" role="button" data-id="' . $c('on_off') . '">' . $svgPower . '</div>';
		$r .=   '<button class="btn-source" data-id="' . $c('source') . '">' . $svgSource . ' SOURCE</button>';
		$r .= '</div>';

		$r .= '<div class="pad-wrap"'
		    . ' data-up="'    . $c('up')    . '"'
		    . ' data-down="'  . $c('down')  . '"'
		    . ' data-left="'  . $c('left')  . '"'
		    . ' data-right="' . $c('right') . '">';
		$r .=   '<div class="pad-bg"></div>';
		$r .=   $svgArrows;
		$r .=   '<canvas class="pad-canvas" width="210" height="210"></canvas>';
		// OK est un <div> — jamais affecte par button{border-radius:0}
		$r .=   '<div class="ok" role="button" data-id="' . $c('enter') . '">OK</div>';
		$r .= '</div>';

		$r .= '<div class="row" style="justify-content:space-between;">';
		$r .=   '<div style="display:flex;flex-direction:column;gap:10px;">';
		$r .=     '<button class="btn-vert" data-id="' . $c('vol_up')   . '">' . $svgVolUp   . '<span>VOL</span></button>';
		$r .=     '<button class="btn-vert" data-id="' . $c('vol_down') . '">' . $svgVolDown . '<span>VOL</span></button>';
		$r .=   '</div>';
		// MUTE est un <div>
		$r .=   '<div class="btn-mute" role="button" data-id="' . $c('mute') . '">' . $svgMute . '</div>';
		$r .=   '<div style="display:flex;flex-direction:column;gap:10px;">';
		$r .=     '<button class="btn-vert" data-id="' . $c('ch_up')   . '">' . $svgChUp   . '<span>CH</span></button>';
		$r .=     '<button class="btn-vert" data-id="' . $c('ch_down') . '">' . $svgChDown . '<span>CH</span></button>';
		$r .=   '</div>';
		$r .= '</div>';

		$r .= '<div class="row">';
		$r .=   '<button class="btn-rect" data-id="' . $c('return') . '">' . $svgReturn . '<span>RETOUR</span></button>';
		$r .=   '<button class="btn-rect" data-id="' . $c('home')   . '">' . $svgHome   . '<span>HOME</span></button>';
		$r .= '</div>';

// Touches KEY_ non fonctionnelles
//		$r .= '<div class="row" style="gap:8px;">';
//		$r .=   '<button class="btn-flat" data-id="' . $c('tv')    . '">TV</button>';
//		$r .=   '<button class="btn-flat" data-id="' . $c('hdmi1') . '">HDMI 1</button>';
//		$r .=   '<button class="btn-flat" data-id="' . $c('hdmi2') . '">HDMI 2</button>';
//		$r .= '</div>';

		$r .= '<div class="samsung-logo">SAMSUNG</div>';
		$r .= '</div>'; // #jt-remote

		// ── Widget compact dashboard ──────────────────────────────────────────
		$savedW = $this->getDisplay('width', '116px');
		$savedH = $this->getDisplay('height', '120px');
		$html  = '<div class="eqLogic eqLogic-widget allowResize multimedia" '
			. 'data-eqtype="JeeTizen" data-eqlogic_id="' . $eqId . '" '
			. 'data-eqlogic_uid="' . $uid . '" data-version="' . $_version . '" '
			. 'data-category="multimedia" style="margin:4px;padding:0;height:' . $savedH . ';width:' . $savedW . ';">';
		$html .= '<center class="widget-name">'
			. '<a href="' . $eqLink . '" style="font-size:1.1em;">' . htmlspecialchars($name) . '</a>'
			. '</center>';
		$html .= '<div id="md_modal_jt_' . $eqId . '" style="display:none;">' . $r . '</div>';
		$html .= '<div style="text-align:center;margin:2px 0;">'
			. '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . $ledColor . '"></span>'
			. '</div>';
		$html .= '<img class="jt-tv-img-' . $eqId . '" src="plugins/JeeTizen/core/template/widget/samsung_tizen.png" '
			. 'style="max-width:100%;max-height:100%;cursor:pointer;display:block;margin:0 auto;"/>';

		$eid = (int)$eqId;

		// CSS directement dans le HTML (disponible immédiatement, pas de race condition)
		$html .= '<style id="' . $styleId . '">' . $css . '</style>';

		$html .= '<script>'
			. '(function(){'

			// Modale jQuery UI
			. 'var $m=$("#md_modal_jt_' . $eid . '");'
			. '$m.dialog({'
			.   'modal:true,autoOpen:false,title:"' . addslashes($name) . '",'
			.   'width:'.(int)round(280*$ratio+40).',resizable:false,'
			.   'position:{my:"center",at:"center",of:window},'
			.   'open:function(){'
			// Appliquer le scale zoom sur #jt-remote uniquement a l ouverture de la modale
			.     'document.getElementById("jt-remote-' . $eid . '").style.zoom="' . $scale . '%";'
			// Forcer overflow:visible + fond transparent sur la chaine jQuery UI
			.     '$(this).css({overflow:"visible",padding:"0",background:"transparent",border:"none"});'
			.     '$(this).closest(".ui-dialog").find("*").css("overflow","visible");'
			.     '$(this).closest(".ui-dialog").css({background:"transparent",border:"none",boxShadow:"none"});'
			.     'jtPad' . $eid . '();'
			.   '}'
			. '});'

			// Clic image → ouvre modale
			. '$(".jt-tv-img-' . $eid . '").off("click.jt' . $eid . '").on("click.jt' . $eid . '",function(){'
			.   '$m.dialog("open");'
			. '});'

			// Clics sur <button> standards
			. '$(document).off("click.jtb' . $eid . '").on("click.jtb' . $eid . '",'
			.   '"#jt-remote-' . $eid . ' button[data-id]",function(e){'
			.   'e.stopPropagation();var cid=$(this).data("id");'
			.   'if(cid&&cid!="")jeedom.cmd.execute({id:cid});'
			. '});'

			// Clics sur <div role=button> (ok, btn-power, btn-mute)
			. '$(document).off("click.jtd' . $eid . '").on("click.jtd' . $eid . '",'
			.   '"#jt-remote-' . $eid . ' div[role=button][data-id]",function(e){'
			.   'e.stopPropagation();var cid=$(this).data("id");'
			.   'if(cid&&cid!="")jeedom.cmd.execute({id:cid});'
			. '});'

			// Init canvas pad
			. 'function jtPad' . $eid . '(){'
			.   'var wrap=document.querySelector("#jt-remote-' . $eid . ' .pad-wrap");'
			.   'if(!wrap||wrap._ji)return;wrap._ji=true;'
			.   'var cv=wrap.querySelector(".pad-canvas");'
			.   'var ctx=cv.getContext("2d");'
			.   'cv.addEventListener("click",function(e){'
			.     'var r=cv.getBoundingClientRect();'
			.     'var sx=cv.width/r.width,sy=cv.height/r.height;'
			.     'var x=(e.clientX-r.left)*sx-105;'
			.     'var y=(e.clientY-r.top)*sy-105;'
			.     'if(Math.sqrt(x*x+y*y)<45)return;'
			.     'var a=Math.atan2(y,x)*180/Math.PI;'
			.     'var cid,a1,a2;'
			.     'if(a>=-45&&a<45)  {cid=wrap.dataset.right;a1=-45; a2=45;}'
			.     'else if(a>=45&&a<135) {cid=wrap.dataset.down; a1=45;  a2=135;}'
			.     'else if(a>=-135&&a<-45){cid=wrap.dataset.up;   a1=-135;a2=-45;}'
			.     'else                   {cid=wrap.dataset.left; a1=135; a2=225;}'
			.     'ctx.clearRect(0,0,210,210);'
			.     'ctx.beginPath();ctx.moveTo(105,105);'
			.     'ctx.arc(105,105,105,a1*Math.PI/180,a2*Math.PI/180);'
			.     'ctx.closePath();ctx.fillStyle="rgba(255,255,255,0.15)";ctx.fill();'
			.     'setTimeout(function(){ctx.clearRect(0,0,210,210);},200);'
			.     'if(cid&&cid!="")jeedom.cmd.execute({id:cid});'
			.   '});'
			. '}'
			. '})();</script>';

		$html .= '</div>';
		return $html;
	}

	/**
	 * Récupération des paramètres de configuration TV
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
