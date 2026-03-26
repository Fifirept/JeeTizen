<?php
if (defined ( 'WEB' )) {
    include_file ( 'core', 'Telecommande/JeeTizen.CommunicationStatus', 'class', 'JeeTizen' );
    include_file ( 'core', 'Logger',  'class', 'JeeTizen');
} else {
     require_once __DIR__ . '/../JeeTizen.CommunicationStatus.class.php';
     require_once __DIR__ . '/../../Logger.class.php';
}

 class SocketWrapper {

    private        $bDeviceConnected;
    private        $bReadSocketResponse;
    private        $socket;
    //
    private static $_access_granted                   = 65636;    // 0x64,0x00,x01,0x00
    private static $_access_denied                    = 100;      // 0x64,0x00,0x00,0x00
    private static $_waiting_user_response            = 131082;   // 0x0a,0x00,0x02,0x00
    private static $_timeout_or_response_cancelled    = 101;      // 0x65,x000,0x00,0x00
    //
    private static $_auth_messages = array ();
    //
    private static $CNX_TIME_OUT = 3000;
    //
    private $_remote_ip;
    private $_remote_port;
    private $_need_response;
    
    public function isbDeviceConnected() {
        return $this->bDeviceConnected;
    }

    public function __construct($_remote_ip, $_remote_port, $_need_response=false) {

        Logger::debug('ctor socket', 'params ' . $_remote_ip . ' ' . $_remote_port );
        /* @var  $tvParametres TvParametres */
        $this->_remote_ip       = $_remote_ip;
        $this->_remote_port     = $_remote_port;
        $this->_need_response   = $_need_response;
        
        self::$_auth_messages[self::$_access_granted]                 = 'access granted';
        self::$_auth_messages[self::$_access_denied]                  = 'access denied';
        self::$_auth_messages[self::$_waiting_user_response]          = 'waiting user response';
        self::$_auth_messages[self::$_timeout_or_response_cancelled]  = 'request cancelled or time out';

    }

    /* @var  $tvParametres TvParametres */
    private function connect() {
         
        $this->bDeviceConnected = false;
        /*
         *
         * Create socket
         *
         */
        $this->socket = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );

        if($this->socket === false ) {

            $communicationStatus = new CommunicationStatus ( false, $this->socket, 'create' );
            Logger::error('socket.php ->create ',  $communicationStatus->toString());
            return $communicationStatus;
        }

        /*
         *
         * Try to connect, time_out 3s
         *
         */
        socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO, array (
            'sec'   => 3,
            'usec'  => 3000
        ) );
        
        $status = socket_connect ( $this->socket, $this->_remote_ip, $this->_remote_port );
        Logger::debug('socket.php ->connect socket.status  :  ',  $status);
        
        /* @var  $communicationStatus CommunicationStatus */
        $communicationStatus = new CommunicationStatus ( $status, $this->socket, 'Connect : ' . $this->_remote_ip  . ':' . $this->_remote_port );
        if ($communicationStatus->getStatus () == 0) {
            $this->bDeviceConnected = true;
        } 
//         else {
//             $communicationStatus->setInternal_err_message(socket_strerror(socket_last_error($this->socket)));
//             Logger::error('socket.php ->connect ',  $communicationStatus->toString());
//         }

        Logger::debug('socket.php ->connect commstatus is :  ',  $communicationStatus->toString());
        return $communicationStatus;
    }

    public function close() {
        $this->bDeviceConnected = false;
        socket_close ( $this->socket );
    }

    public function send($message) {
         
        if (! $this->isbDeviceConnected ()) {
            $communicationStatus = $this->connect ();
            if ($communicationStatus->getStatus () != 0){
                return $communicationStatus;
            }
        }

        //$communicationStatus = '';

        $status = socket_write ( $this->socket, $message->getMessage(), strlen ( $message->getMessage() ) );
        $communicationStatus = new CommunicationStatus ( $status, $this->socket, 'send' );

        // write error ?
        if($status === false )                      return $communicationStatus;
        // must return Authentification ?
        if($message->isAuthentificationMessage())   return $this->checkAuthentificationReturnCode();
        
        // besoin réponse de la socket ?
        if(!$this->_need_response)                  return $communicationStatus;
        // read response !
        $buf = '';
        try {
            $reponse = socket_recv ( $this->socket, $buf, 1024, MSG_WAITALL );
            if($reponse === false ) return new CommunicationStatus ( false, $this->socket, 'daemon_cmd', $reponse );
            Logger::debug('buf:', $buf);
            return new CommunicationStatus ( true, $this->socket, 'daemon_cmd', $buf );
        } catch (Exception $e)
        {
            return new CommunicationStatus ( false, $this->socket, 'daemon_cmd', $e->getMessage() );
        }
    }

    private function checkAuthentificationReturnCode()
    {
        while(true) {
            try {
                $status = $this->readResponse('C1returnCode/v1AppNameLength', 3);
                if($status['returnCode'] !== 0&& $status['returnCode'] !== 2) {
                    return new CommunicationStatus ( false, $this->socket, 'decodeAuthentification', 'Tv retturned unknown protocole ( # 0 or 2 )' );
                }
                // lir nom app
                $status     = $this->readResponse('C*AppName', $status['AppNameLength']);
                $status     = $this->readResponse('v1AuthCodeLength', 2);
                $_format    = ($status['AuthCodeLength'] == 2 ? 'v1' : 'L1') . 'AuthCode';
                $status     = $this->readResponse($_format, $status['AuthCodeLength']);
                if($status['AuthCode'] !== self::$_waiting_user_response){
                    $_message   = isset(self::$_auth_messages[$status['AuthCode']]) ? self::$_auth_messages[$status['AuthCode']] : 'Tv send unknown authentification response !' ;
                    Logger::debug('Transmetteur, checkAuth result ->' . $_message);
                    $comm =  new CommunicationStatus ( true, $this->socket, 'checkAuth', $_message, $status['AuthCode'] );
                    Logger::debug('Transmetteur, checkAuth commStatus ->' . $comm->toString());
                    return $comm;
                }
            } catch (Exception $e)
            {
                return new CommunicationStatus ( $status, $this->socket, 'decodeAuthentification', $e->getMessage() );
            }
        }
    }

    private function readResponse($_format, $_size)
    {
        $buf = 'This is my buffer.';
         
        $status = socket_recv ( $this->socket, $buf, $_size, MSG_WAITALL );
        Logger::debug('Transmetteur, readresponse status ->' . $status);
        
        if($status === false) {
            throw new Exception('socket read error');
        }

        return unpack($_format , $buf);
    }
}
?>