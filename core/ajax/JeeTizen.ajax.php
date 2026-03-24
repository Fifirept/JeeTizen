<?php
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect()) {
        throw new Exception('401 - Accès non autorisé');
    }
    require_once __DIR__ . '/../../3rdparty/vendor/textalk/autoload.php';
    include_file('core', 'class', 'class', 'JeeTizen');

    ajax::init();

    if (init('action') === 'sendKey') {
        $eqLogic = JeeTizen::byId(init('eqLogicId'));
        if (!is_object($eqLogic)) {
            ajax::error('Equipement introuvable');
        }
        $eqLogic->sendSequence(init('sequence'));
        ajax::success();
    }

    throw new Exception('Action inconnue : ' . init('action'));

} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
