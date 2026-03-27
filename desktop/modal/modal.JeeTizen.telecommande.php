<?php
if (!isConnect()) {
    throw new Exception('401 - {{Accès non autorisé}}');
}

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

$eqLogic = eqLogic::byId(init('id'));
if (!is_object($eqLogic)) {
    throw new Exception('{{Équipement introuvable}}');
}

$eqId = $eqLogic->getId();
$template = $eqLogic->getConfiguration('widget_template', 'dark');

// Charger le fichier template
$tplFile = ($template == 'light') ? 'light' : 'dark';
$tplPath = dirname(__FILE__) . '/../../core/template/widget/remote_' . $tplFile . '.html';
if (!file_exists($tplPath)) {
    echo '<div style="padding:20px;text-align:center;color:#999;">Template introuvable</div>';
    return;
}
$tplHtml = file_get_contents($tplPath);

// Map logicalId -> cmd_id
$cmdMap = array();
foreach ($eqLogic->getCmd() as $cmd) {
    $lid = $cmd->getLogicalId();
    if (!empty($lid)) {
        $cmdMap[$lid] = $cmd->getId();
    }
}

// État
$stateCmd = $eqLogic->getCmd('info', 'state');
$stateOn = (is_object($stateCmd) && $stateCmd->execCmd());
$ledColor = $stateOn ? 'rgb(0,200,100)' : 'rgb(80,80,80)';

// Remplacements
$replace = array(
    '#eqId#'         => $eqId,
    '#ledColor#'     => $ledColor,
    '#cmd_on_off#'   => isset($cmdMap['on_off']) ? $cmdMap['on_off'] : '',
    '#cmd_source#'   => isset($cmdMap['source']) ? $cmdMap['source'] : '',
    '#cmd_up#'       => isset($cmdMap['up']) ? $cmdMap['up'] : '',
    '#cmd_down#'     => isset($cmdMap['down']) ? $cmdMap['down'] : '',
    '#cmd_left#'     => isset($cmdMap['left']) ? $cmdMap['left'] : '',
    '#cmd_right#'    => isset($cmdMap['right']) ? $cmdMap['right'] : '',
    '#cmd_enter#'    => isset($cmdMap['enter']) ? $cmdMap['enter'] : '',
    '#cmd_vol_up#'   => isset($cmdMap['vol_up']) ? $cmdMap['vol_up'] : '',
    '#cmd_vol_down#' => isset($cmdMap['vol_down']) ? $cmdMap['vol_down'] : '',
    '#cmd_mute#'     => isset($cmdMap['mute']) ? $cmdMap['mute'] : '',
    '#cmd_ch_up#'    => isset($cmdMap['ch_up']) ? $cmdMap['ch_up'] : '',
    '#cmd_ch_down#'  => isset($cmdMap['ch_down']) ? $cmdMap['ch_down'] : '',
    '#cmd_return#'   => isset($cmdMap['return']) ? $cmdMap['return'] : '',
    '#cmd_home#'     => isset($cmdMap['home']) ? $cmdMap['home'] : '',
    '#cmd_tv#'       => isset($cmdMap['tv']) ? $cmdMap['tv'] : '',
    '#cmd_hdmi1#'    => isset($cmdMap['hdmi1']) ? $cmdMap['hdmi1'] : '',
    '#cmd_hdmi2#'    => isset($cmdMap['hdmi2']) ? $cmdMap['hdmi2'] : '',
);

echo str_replace(array_keys($replace), array_values($replace), $tplHtml);
