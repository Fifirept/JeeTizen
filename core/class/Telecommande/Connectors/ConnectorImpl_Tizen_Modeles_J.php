<?php

if (defined('WEB')) {
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../JeeTizen.TvParametres.class.php';
    require_once __DIR__ . '/../Sockets/JeeTizen.SocketWrapper.class.php';
    // config.class.php déjà chargé via core.inc.php
    require_once __DIR__ . '/../../Logger.class.php';
    require_once __DIR__ . '/LegacyTv/JeeTizen.MessageTv.class.php';
} else {
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../Sockets/JeeTizen.SocketWrapper.class.php';
    require_once __DIR__ . '/../JeeTizen.CommunicationStatus.class.php';
    require_once __DIR__ . '/LegacyTv/JeeTizen.MessageTv.class.php';
}

class ConnectorImpl_Tizen_Modeles_J implements IConnector
{

    public static function showPinPageOnTv()
    {
        return self::send('{ "cmd": "showPinPage"}');
    }

    public static function pairTv($pinCode)
    {
        return self::send('{ "cmd": "pairTv", "pin" : "' . $pinCode . '" }');
    }
    
    public static function setTv($ip_tv, $port_tv)
    {
        return self::send('{ "cmd": "setTv", "ipTv" : "' . $ip_tv. '", "portTv" : ' . $port_tv . ' }');
    }
    
    public function sendKey($keyCode)
    {
        return self::send('{ "cmd": "send", "key" : "' . $keyCode . '" }');
    }

    private static function send($msg)
    {
        Logger::debug('send static ', config::byKey('samsung_daemon_ip', 'JeeTizen') . ' ' . config::byKey('samsung_daemon_port','JeeTizen'));
        $socket       = new SocketWrapper(config::byKey('samsung_daemon_ip', 'JeeTizen'), config::byKey('samsung_daemon_port','JeeTizen'), true);
        $message = new MessageTv('daemon', $msg);
        $communicationStatus = $socket->send($message);
        $socket->close();
        Logger::debug('$communicationStatus after send i ModelJ : ' . $communicationStatus->toString());
        return $communicationStatus;
    }
    
    public function authenticate()
    {
        Logger::debug('authenticate', 'not implemented for tizen models');
    }

    public function getTvModele()
    {
        return 'Tizen J';
    }
    
    public function getClassName()
    {
        return 'ConnectorImpl_Tizen_Modeles_J';
    }
    
}

// test 
if(!defined('WEB')) {
    class config
    {
        public static function byKey($key, $class)
        {
            if($key == 'samsung_daemon_ip')     return '192.168.1.7';
            if($key == 'samsung_daemon_port')   return 9100;
            
        }
    }
    //while(true) {
        echo '---------------------------------------------------------   call to daemon ------------------------------------------------------------' . PHP_EOL;
//         $comm = ConnectorImpl_J::showPinPageOnTv();
//          echo $comm->toString() . PHP_EOL;
//          echo 'json -> ' . json_encode($comm, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
//         // ok avec 7346
//          $comm = ConnectorImpl_J::pairTv(7346);
//          echo $comm->toString() . PHP_EOL;
        
        $comm = (new ConnectorImpl_Tizen_Modeles_J())->sendkey('KEY_MUTE');
        
        echo $comm->toString() . PHP_EOL;
        
        sleep(3);
    //}
    

}
?>