<?php

if (defined('WEB')) {
    require_once __DIR__ . '/../../../../3rdparty/vendor/textalk/autoload.php';
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../JeeTizen.TvParametres.class.php';
} else {
    require_once __DIR__ . '/../../../../3rdparty/vendor/textalk/autoload.php';
    require_once __DIR__ . '/IConnector.php';
    require_once __DIR__ . '/../JeeTizen.TvParametres.class.php';
}

class ConnectorImpl_Tizen_Modeles_Standard implements IConnector
{
    private $tvParametres;
    private $eqLogic;

    public function __construct($tvParametres)
    {
        Logger::debug('creation connector modele -> ConnectorImpl_Standard_SSL', '');
        $this->tvParametres = $tvParametres;
        // Récupérer l'eqLogic pour sauvegarder le token
        $this->eqLogic = eqLogic::byId($this->tvParametres->getEqLogicId());
        // Rafraîchir le token depuis la config
        if (is_object($this->eqLogic)) {
            $this->tvParametres->setTokenAuth($this->eqLogic->getConfiguration('tokenAuth'));
        }
    }

    public function sendKey($_key)
    {
        $_timeout = 5;
        if ($this->tvParametres->getTokenAuth() === null || $this->tvParametres->getTokenAuth() === '') {
            $_timeout = 20; // Plus de temps pour l'appairage initial
        }

        // Options WebSocket
        $_options = array(
            'timeout' => $_timeout,
        );

        // Support SSL (wss://)
        if ($this->tvParametres->isSSLEncoded()) {
            $_options['context'] = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
            ));
        }

        try {
            $url = $this->tvParametres->getTizenConnexionUrl();
            Logger::debug('WebSocket cnx to', $url);
            Logger::debug('WebSocket timeout', $_timeout . 's');

            $client = new \WebSocket\Client($url, $_options);

            // Lire le message de bienvenue (contient le token + event ms.channel.connect)
            $welcome = $client->receive();
            Logger::debug('WebSocket welcome', $welcome);

            $welcomeData = json_decode($welcome, true);

            // Vérifier event de connexion
            if (isset($welcomeData['event']) && $welcomeData['event'] === 'ms.channel.connect') {
                // Token fourni en retour ?
                if (isset($welcomeData['data']['token'])) {
                    $newToken = $welcomeData['data']['token'];
                    Logger::debug('Token reçu de la TV', $newToken);
                    // Sauvegarder le token
                    if (is_object($this->eqLogic)) {
                        $this->eqLogic->setConfiguration('tokenAuth', $newToken);
                        $this->eqLogic->save();
                        Logger::info('Token sauvegardé pour eqLogic', $this->eqLogic->getId());
                    }
                }

                // Envoyer la touche
                $tizen_data = json_encode(array(
                    'method' => 'ms.remote.control',
                    'params' => array(
                        'Cmd' => 'Click',
                        'DataOfCmd' => $_key,
                        'Option' => 'false',
                        'TypeOfRemote' => 'SendRemoteKey',
                    ),
                ));
                Logger::debug('send data to Tv ->', $tizen_data);
                $client->send($tizen_data);

                // Petite pause pour laisser la TV traiter
                usleep(300000);

                $client->close();
                Logger::debug('WebSocket connexion fermée', '');

                return new CommunicationStatus(true, null, 'reception', $welcome);

            } else {
                Logger::error('WebSocket event inattendu', isset($welcomeData['event']) ? $welcomeData['event'] : 'inconnu');
                $client->close();
                return new CommunicationStatus(false, null, 'WebSocket error', 'Event inattendu: ' . $welcome);
            }

        } catch (\WebSocket\ConnectionException $e) {
            Logger::error('WebSocket ConnectionException', $e->getMessage());
            return new CommunicationStatus(false, null, 'WebSocket error', $e->getMessage());
        } catch (\Exception $e) {
            Logger::error('WebSocket Exception', $e->getMessage());
            return new CommunicationStatus(false, null, 'WebSocket error', $e->getMessage());
        }
    }

    public function authenticate()
    {
        Logger::debug('authenticate', 'Envoi KEY_POWER pour déclencher appairage');
        return $this->sendKey('KEY_ENTER');
    }

    public function getTvModele()
    {
        return 'Tizen';
    }

    public function getClassName()
    {
        return 'ConnectorImpl_Tizen_Modeles_Standard';
    }
}
