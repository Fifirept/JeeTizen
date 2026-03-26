<?php 
if (defined('WEB')) {
    
    require_once __DIR__ . '/IConnector.php';
    
    require_once __DIR__ . '/../../Logger.class.php';
    require_once __DIR__ . '/../JeeTizen.TvParametres.class.php';
    require_once __DIR__ . '/../JeeTizen.CommunicationStatus.class.php';
    require_once __DIR__ . '/../Sockets/JeeTizen.SocketWrapper.class.php';
    require_once __DIR__ . '/LegacyTv/JeeTizen.MessageBuilder.class.php';
    
} else {
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../../Logger.class.php';
    require_once __DIR__ . '/../JeeTizen.TvParametres.class.php';
    require_once __DIR__ . '/../JeeTizen.CommunicationStatus.class.php';
    require_once __DIR__ . '/../Sockets/JeeTizen.SocketWrapper.class.php';
    require_once __DIR__ . '/LegacyTv/JeeTizen.MessageBuilder.class.php';
}


class ConnectorImpl_Legacy implements IConnector {
    
    private $messageBuilder;
    private $socket;
    private $tvParametres;
    
    public function __construct($tvParametres)
    {
        Logger::debug('creation connector modele -> Connectorlegacy ', '');
        
        $this->tvParametres   = $tvParametres;
        $this->socket 	      = new SocketWrapper($tvParametres->getRemoteIp(), $tvParametres->getRemotePort());
        $this->messageBuilder = new MessageBuilder($tvParametres->getTvApp()); //,$_ip_jeedom,'jeedomBox');
        
    }
    
    public function authenticate()
    {
        Logger::debug('send msg authenticate, modele Legacy modele ES', '');
        $communicationStatus = $this->socket->send($this->messageBuilder->createAuthentificationMessage());
        Logger::debug('tvConnectorLegacy, authenticate response ->' . $communicationStatus->toString());
        
        return $communicationStatus;
    }

    public function getTvModele()
    {
        return 'LEGACY';
    }
    
    public function getClassName()
    {
        return 'ConnectorImpl_Legacy';
    }
    

    public function sendKey($teleCommandeKey)
    {
        
//         if(!defined('WEB'))
//         {
//             Utils::decode('commandeMsg', $this->messageBuilder->createSendKeyMessage($teleCommandeKey));
//         }
        
        $communicationStatus = '';
        if($this->tvParametres->getSubModeleTv() == 'ES') {
            $communicationStatus = $this->authenticate();
            if($communicationStatus->getStatus() === 0) {
                Logger::debug('tvConnectorLegacy, send msg key, modele Legacy modele ES', $teleCommandeKey);
                $communicationStatus = $this->socket->send($this->messageBuilder->createSendKeyMessage($teleCommandeKey));
            }
        } else {
            Logger::debug('tvConnectorLegacy, send msg key,  modele Legacy modele standard', $teleCommandeKey);
            $communicationStatus =$this->socket->send($this->messageBuilder->createSendKeyMessage($teleCommandeKey));
        }
        
        $this->socket->close();
        
        Logger::debug('tvConnectorLegacy, sendkey response ->' . $communicationStatus->toString());
        return $communicationStatus;
        
    }
}
// $_tv = new TvParametres(10, '192.168.1.11', 55000, 'app', 'Legacy', '0', 0, 0, 0, '');
// $_conn = new ConnectorLegacy($_tv);
// $_conn->authenticate();

?>