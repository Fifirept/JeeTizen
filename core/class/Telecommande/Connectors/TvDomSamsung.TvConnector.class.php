<?php

if(!defined('WEB')) define('WEB','WEB');

if(defined('WEB')) {
    
    require_once __DIR__ . '/../TvDomSamsung.TvParametres.class.php';
    require_once __DIR__ . '/ConnectorImpl_Tizen_Modeles_J.php';
    require_once __DIR__ . '/ConnectorImpl_Tizen_Modeles_Standard.php';
    require_once __DIR__ . '/ConnectorImpl_Tizen_Modeles_K.php';
    require_once __DIR__ . '/ConnectorImpl_Legacy.php';
    require_once __DIR__ . '/IConnector.php';
} else {
    require_once __DIR__ . '/../TvDomSamsung.TvParametres.class.php';
    require_once __DIR__ . './ConnectorImpl_J.php';
    require_once __DIR__ . './ConnectorImpl_M.php';
    require_once __DIR__ . './ConnectorImpl.php';
    require_once __DIR__ . './ConnectorLegacy.php';
    require_once __DIR__ . './IConnector.php';
}

class TvConnector
{
   
    public static function authenticateByTv($_tvParametres)
    {
        $_tv = self::getInstance($_tvParametres);
        return $_tv->authenticate();
    }
    
    public static function sendToTv($_tvParametres, $_key)
    {
        
        $_tv = self::getInstance($_tvParametres);
        
        Logger::debug('before send  ', $_tvParametres->toString());
        $communicationStatus = $_tv->sendKey($_key);
        Logger::debug('commStatus after send  ', $communicationStatus->toString());
        //
        // ne concerne pas le modèle LEGACY qui n'envoie jamais KEY_POWER, mais KEY_POWEROFF
        //
        if ($_key == 'KEY_POWER') {
            
            Logger::debug('test avec KEY_POWER Comm.status = ', "'" . $communicationStatus->getStatus() . "' canWakeOnLan = '" . ($_tvParametres->canWakeOnlan() == 0 ? 'false' : 'true') . "'");
            
            switch ($communicationStatus->getStatus()) {
                
                case 0: // pas d'erreur Tv est donc allumée -> l'action est donc demande extinction ...
                    $communicationStatus = $_tv->sendKey('KEY_POWEROFF');
                    break;
                    
                case 1: // erreur , donc Tv éteinte ==> allumage de la tv si WOL enabled
                    $communicationStatus = $_tvParametres->WakeOnlan();
                    break;
            }
        }
        
        return $communicationStatus;
    }
    
    public static function getInstance($tvParametres)
    {
        $_class = $tvParametres->getClassName();
        Logger::debug('TvConnector::getInstance() -> class' , $_class);
        return new $_class($tvParametres);

    }
}
?>