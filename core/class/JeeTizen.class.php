<?php

/* ============================================================
 * JeeTizen - Contrôle TV Samsung Tizen via WebSocket
 * Classe principale eqLogic + classe commande
 * ============================================================ */

class JeeTizen extends eqLogic {

    // ----------------------------------------------------------------
    // COMMANDES PAR DÉFAUT créées à la sauvegarde d'un équipement
    // ----------------------------------------------------------------
    const DEFAULT_COMMANDS = [
        // logicalId       , nom               , type    , subType  , isVisible
        ['power'         , 'Marche/Arrêt'     , 'action', 'other'  , 1],
        ['mute'          , 'Mute'             , 'action', 'other'  , 1],
        ['vol_up'        , 'Volume +'         , 'action', 'other'  , 1],
        ['vol_down'      , 'Volume -'         , 'action', 'other'  , 1],
        ['ch_up'         , 'Chaîne +'         , 'action', 'other'  , 1],
        ['ch_down'       , 'Chaîne -'         , 'action', 'other'  , 1],
        ['source'        , 'Source'           , 'action', 'other'  , 1],
        ['zap'           , 'Zap'              , 'action', 'slider' , 1],
        ['sendkey'       , 'Touche'           , 'action', 'message', 1],
        ['state'         , 'Etat'             , 'info'  , 'binary' , 1],
    ];

    // ----------------------------------------------------------------
    // Après sauvegarde : création automatique des commandes manquantes
    // ----------------------------------------------------------------
    public function postSave() {
        foreach (self::DEFAULT_COMMANDS as [$logicalId, $name, $type, $subType, $isVisible]) {
            $cmd = $this->getCmd($type, $logicalId);
            if (!is_object($cmd)) {
                $cmd = new JeeTizenCmd();
                $cmd->setEqLogic_id($this->getId());
                $cmd->setLogicalId($logicalId);
                $cmd->setName($name);
                $cmd->setType($type);
                $cmd->setSubType($subType);
                $cmd->setIsVisible($isVisible);
                $cmd->save();
            }
        }
    }

    // ----------------------------------------------------------------
    // Connexion WebSocket et envoi d'une séquence de touches
    // Séquence : "KEY_MUTE" ou "KEY_TV | KEY_2 | KEY_ENTER"
    // ----------------------------------------------------------------
    public function sendSequence(string $sequence): bool {
        $ip      = $this->getConfiguration('ip', '');
        $port    = (int)$this->getConfiguration('port', 8002);
        $ssl     = (bool)$this->getConfiguration('ssl', true);
        $token   = $this->getConfiguration('tokenAuth', '');
        $appName = 'jeedom.jeetizen';

        if (empty($ip)) {
            log::add('JeeTizen', 'error', '[' . $this->getName() . '] IP non configurée');
            return false;
        }

        $protocol = $ssl ? 'wss' : 'ws';
        $url = $protocol . '://' . $ip . ':' . $port
             . '/api/v2/channels/samsung.remote.control'
             . '?name=' . base64_encode($appName)
             . ((!empty($token)) ? '&token=' . $token : '');

        $timeout = empty($token) ? 20 : 5;

        $ctxOptions = [];
        if ($ssl) {
            $ctxOptions['ssl'] = ['verify_peer' => false, 'verify_peer_name' => false];
        }
        $options = [
            'timeout' => $timeout,
            'context' => stream_context_create($ctxOptions),
        ];

        $keys = array_filter(array_map('trim', explode('|', strtoupper($sequence))));
        if (empty($keys)) {
            log::add('JeeTizen', 'warning', '[' . $this->getName() . '] Séquence vide');
            return false;
        }

        log::add('JeeTizen', 'debug', '[' . $this->getName() . '] connexion -> ' . $url);

        try {
            $client = new WebSocket\Client($url, $options);

            // Attendre ms.channel.connect
            $raw   = $client->receive();
            $event = json_decode($raw);
            log::add('JeeTizen', 'debug', '[' . $this->getName() . '] msg reçu -> ' . $raw);

            if (!is_object($event) || $event->event !== 'ms.channel.connect') {
                log::add('JeeTizen', 'error', '[' . $this->getName() . '] événement inattendu : ' . $raw);
                $client->close();
                return false;
            }

            // Stocker le tokenAuth si nouveau
            if (property_exists($event->data, 'token')) {
                $newToken = $event->data->token;
                if ($newToken !== $token) {
                    log::add('JeeTizen', 'info', '[' . $this->getName() . '] nouveau token : ' . $newToken);
                    $this->setConfiguration('tokenAuth', $newToken);
                    $this->save();
                }
            }

            // Délai inter-touches (ms → µs)
            $delayUs = max(100000, (int)$this->getConfiguration('keyDelay', 300) * 1000);

            // Envoyer toutes les touches dans la même connexion
            foreach (array_values($keys) as $i => $key) {
                $payload = json_encode([
                    'method' => 'ms.remote.control',
                    'params' => [
                        'Cmd'          => 'Click',
                        'DataOfCmd'    => $key,
                        'Option'       => 'false',
                        'TypeOfRemote' => 'SendRemoteKey',
                    ]
                ]);
                log::add('JeeTizen', 'debug', '[' . $this->getName() . '] send -> ' . $payload);
                $client->send($payload);
                if ($i < count($keys) - 1) {
                    usleep($delayUs);
                }
            }

            usleep(200000);
            $client->close();
            log::add('JeeTizen', 'debug', '[' . $this->getName() . '] connexion fermée');
            return true;

        } catch (WebSocket\ConnectionException $e) {
            log::add('JeeTizen', 'error', '[' . $this->getName() . '] erreur WebSocket : ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            log::add('JeeTizen', 'error', '[' . $this->getName() . '] erreur : ' . $e->getMessage());
            return false;
        }
    }

    // ----------------------------------------------------------------
    // Construit la séquence de zap avec KEY_TV en tête
    // et optionnellement un retour sur la source HDMI configurée
    // ----------------------------------------------------------------
    public function zapTo(int $channel): void {
        $digits   = str_split((string)$channel);
        $keySeq   = implode(' | ', array_map(fn($d) => 'KEY_' . $d, $digits));
        $sequence = 'KEY_TV | ' . $keySeq . ' | KEY_ENTER';

        $sourceRetour = strtoupper(trim($this->getConfiguration('sourceRetour', '')));
        $delayRetour  = max(500, (int)$this->getConfiguration('delayRetour', 3000));

        log::add('JeeTizen', 'info', '[' . $this->getName() . '] zap -> chaîne ' . $channel
            . ($sourceRetour ? ' puis retour ' . $sourceRetour . ' après ' . $delayRetour . 'ms' : ''));

        $this->sendSequence($sequence);

        if (!empty($sourceRetour)) {
            usleep($delayRetour * 1000);
            $this->sendSequence('KEY_' . $sourceRetour);
        }
    }
}


/* ============================================================
 * Classe commande JeeTizenCmd
 * ============================================================ */
class JeeTizenCmd extends cmd {

    // Map logicalId -> touche(s) Samsung
    const KEY_MAP = [
        'power'    => 'KEY_POWER',
        'mute'     => 'KEY_MUTE',
        'vol_up'   => 'KEY_VOLUP',
        'vol_down' => 'KEY_VOLDOWN',
        'ch_up'    => 'KEY_CHUP',
        'ch_down'  => 'KEY_CHDOWN',
        'source'   => 'KEY_SOURCE',
    ];

    public function execute($_options = []) {
        $eqLogic = $this->getEqLogic();
        $logId   = $this->getLogicalId();

        // Commandes simples mappées vers une touche
        if (isset(self::KEY_MAP[$logId])) {
            $eqLogic->sendSequence(self::KEY_MAP[$logId]);
            return;
        }

        switch ($logId) {

            case 'zap':
                $channel = isset($_options['slider']) ? (int)$_options['slider'] : 0;
                if ($channel <= 0) {
                    log::add('JeeTizen', 'error', 'Zap : numéro de chaîne invalide');
                    return;
                }
                $eqLogic->zapTo($channel);
                break;

            case 'sendkey':
                // Envoie une séquence libre ex: "KEY_HOME" ou "KEY_UP | KEY_ENTER"
                $keys = isset($_options['message']) ? trim($_options['message']) : '';
                if (empty($keys)) {
                    log::add('JeeTizen', 'error', 'SendKey : aucune touche fournie');
                    return;
                }
                $eqLogic->sendSequence($keys);
                break;

            default:
                log::add('JeeTizen', 'warning', 'Commande inconnue : ' . $logId);
        }
    }
}
