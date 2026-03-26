<?php 

if (defined('WEB')) {
    require_once __DIR__ . '/../../../../3rdparty/vendor/ratchetphp/autoload.php';
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../TvDomSamsung.TvParametres.class.php';
    // core.inc.php déjà chargé via la classe principale
    
    
} else {
    require_once __DIR__ . '/../../../../3rdparty/vendor/ratchetphp/autoload.php';
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../TvDomSamsung.TvParametres.class.php';
}

	
class ConnectorImpl_Tizen_Modeles_K implements IConnector {
	    
	    private $tvParametres;
	    private $eqLogic;
	    
	    public function __construct($tvParametres)
	    {
	        Logger::debug('creation connector modele -> ConnectorImpl ', '');
	        $this->tvParametres     = $tvParametres;
	        // refresh tokenAuth !
	        $this->eqLogic = eqLogic::byId($this->tvParametres->getEqLogicId());
	        if(is_object($this->eqLogic)) {
	            $this->tvParametres->setTokenAuth($this->eqLogic->getConfiguration('tokenAuth'));
	        }
	    }
	    
	    public function sendKey($_key)
        {

            $tv_logger = function ($_msg) {
                Logger::debug( 'websocket ', $_msg);
            };
            
            $_status = new CommunicationStatus(true, null, 'reception', '');
            /*
             * Détermination time out
             */
            $_timeout = 5;
            $_options = array(
                'timeout' => $_timeout,
            );
            
            if($this->tvParametres->isSSLEncoded()) {
                if($this->tvParametres->getTokenAuth() === null || $this->tvParametres->getTokenAuth() === '') {
                    $_timeout = 20;
                }
                $_options = array(
                    'timeout' => $_timeout,
                    'tls'     => [ 'verify_peer' => false,'verify_peer_name' => false]
                );
            } 
            
            $loop           = \React\EventLoop\Factory::create();
            $reactConnector = new \React\Socket\Connector($loop, $_options);
            $connector      = new \Ratchet\Client\Connector($loop, $reactConnector);

            $_delai         = $this->tvParametres->getModeleKDelai();
            $_delai         = $_delai == 0 ? 500000 : $_delai * 1000;
            Logger::debug('websocket', 'delai ' . $_delai);
            
            $connector($this->tvParametres->getTizenConnexionUrl())->then(function(Ratchet\Client\WebSocket $conn) use($_key, $tv_logger, $_delai) {
                
                $conn->on('error', function ($_error) use ($tv_logger) {
                    $tv_logger("event error -> " . $_error);
                });
                    
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $message) use ($conn, $_key, $tv_logger, $_delai) {
                    
                    $tv_logger("msg reçu -> " . $message);
                    $_event = json_decode($message);
                    $tv_logger("event -> " . $_event->event);
                    
                    /*
                     * Event de connexion ?
                     */
                    if ($_event->event === 'ms.channel.connect') {              // trt connexion !

                        // tokenAuth fourni en retour ?
                        if(property_exists($_event->data, 'token')) {
                            // message si tokenAuth #
                            if($this->tvParametres->getTokenAuth() != $_event->data->token && $this->tvParametres->getTokenAuth() != null && $this->tvParametres->getTokenAuth() != '')
                            {
                                $tv_logger('tokenAuth TV != tokenAuth in cached -> "' . $this->tvParametres->getTokenAuth() . '" , sent bvy Tv -> "' . $_event->data->token . '"');
                            }
                            // stocker dans config du plugin le tokenAuth retourné
                            //$eqLogic = eqLogic::byId($this->tvParametres->getEqLogicId());
                            if(is_object($this->eqLogic)) {
                                $tv_logger('store tokenAuth ' . $_event->data->token . ' in Config for eqLogicId: ' . $this->eqLogic->getId());
                                $this->eqLogic->setConfiguration('tokenAuth', $_event->data->token);
                                $this->eqLogic->save();
                            } else {
                                $tv_logger("eqLogic not found to set tokenAuth in Config for : " . $this->tvParametres->getEqLogicId());
                            }
                        }
                        
                        $_data = '{ "method": "ms.remote.control",' . '"params": { "Cmd": "Click", "DataOfCmd": "' . $_key . '", "Option": "false", "TypeOfRemote": "SendRemoteKey"}}';
                        $tv_logger('send data to Tv -> ' . $_data);
                        /*
                         * send data
                         */
                        $conn->send($_data);

                        // sleep 
                        usleep($_delai);
                        
                        // close connexion
                        $tv_logger('exec sleep + client->close()');
                        $conn->close();
                        
                    } else {
                        $tv_logger('received msg with unknown event, skip -> ' . $_event->event . ' msg ' . $message);
                        $conn->close();
                    }
                    
                });
                        
                $conn->on('close', function($code = null, $reason = null) use($tv_logger){
                    $tv_logger("Connection closed (code:{$code} - raison:{$reason})");
                });
                // avec token                            
                //$conn->send('{"data":{"clients":[{"attributes":{"name":"amVlZG9tLnRpemVuLmFwcC5zYW1zdW5n","token":"16609345"},"connectTime":1544737272465,"deviceName":"amVlZG9tLnRpemVuLmFwcC5zYW1zdW5n","id":"79e7402f-72c8-4cff-8c60-402d7cfed6e1","isHost":false}],"id":"79e7402f-72c8-4cff-8c60-402d7cfed6e1","token":"16616177"},"event":"ms.channel.connect"}');
                // sans token
                //$conn->send('{"data":{"clients":[{"attributes":{"name":"amVlZG9tLnRpemVuLmFwcC5zYW1zdW5n","token":"16609345"},"connectTime":1544737272465,"deviceName":"amVlZG9tLnRpemVuLmFwcC5zYW1zdW5n","id":"79e7402f-72c8-4cff-8c60-402d7cfed6e1","isHost":false}],"id":"79e7402f-72c8-4cff-8c60-402d7cfed6e1"},"event":"ms.channel.connect"}');
                    
            }, function(\Exception $e) use ( $loop, $tv_logger, $_status) {
                Logger::debug( '$_status avant affect erreur', $_status->toString());
                $tv_logger("Could not connect to : " . $this->tvParametres->getTizenConnexionUrl() . ' error: ' . $e->getMessage());
                //$_status =  new CommunicationStatus(false, null, 'WebSocket error', $e->getMessage());
                $_status->status = 1;
                $_status->commande_message = $e->getMessage();
                
                Logger::debug( '$_status apres affect erreur', $_status->toString());
                $loop->stop();
            });
                
            $loop->run();
            
            Logger::debug( 'retour $_status ', $_status->toString());
            return $_status;
        }
        
        
        public function authenticate()
        {
            Logger::debug('authenticate', 'not implemented for tizen models');
        }
        
        public function getTvModele()
        {
            return 'Tizen';
        }
        
        public function getClassName()
        {
            return 'ConnectorImpl_Tizen_Modeles_K';
        }
	}
	
?>