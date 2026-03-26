<?php
if (defined('WEB')) {
    include_file('core', 'Telecommande/Connectors/LegacyTv/TvDomSamsung.MessageTv', 'class', 'JeeTizen');
    include_file('core', 'Logger',  'class', 'JeeTizen');
} else {
    require_once __DIR__ . '/TvDomSamsung.MessageTv.class.php';
    require_once __DIR__ . '/../../../Logger.class.php';
}

class MessageBuilder {

    private $_app_name;

    public function __construct($_app_name) // , $_ip_remote, $_materiel)
    {
        Logger::debug('MessageBuilder  app_name:' . $_app_name  , '');
        
        $this->_app_name  = $_app_name;
//         $this->_ip_remote = network::getNetworkAccess('internal', 'ip', '', false); 
//         $this->_materiel  = 'jeedomBox';
    }

    /**
     *
     * Construction enveloppe pour fonction sendKey
     *
     */

    public function createSendKeyMessage($_key)
    {
        
        $_msg =   pack('C', 0x0)
                . pack('C', 0x0)
                . pack('C', 0x0)
                . $this->prepareMsg(base64_encode($_key));

        //return $this->completeMsg($msg);
        return new MessageTv(0, $this->completeMsg($_msg));
    }

    /**
     *
     * Construction enveloppe pour fonction authentification
     *
     */
    public function createAuthentificationMessage()
    {
        $_ip_jeedom = network::getNetworkAccess('internal', 'ip', '', false);
        Logger::debug('MessageBuilder  ip_jeedom:' . $_ip_jeedom  , '');
        
        $_msg =   pack('C', 0x64)
                . pack('C', 0x00)
                . $this->prepareMsg(base64_encode($_ip_jeedom))
                . $this->prepareMsg(base64_encode('id_00'))
                . $this->prepareMsg(base64_encode('jeedomBox'));

        //return $this->completeMsg( $_msg);
        return new MessageTV(1, $this->completeMsg($_msg));
    }

    function prepareMsg($_str)
    {
        return pack('v', strlen($_str)) . $_str;
    }

    function completeMsg($msg_in)
    {

        return pack('C', 0x00)
        . $this->prepareMsg($this->_app_name)
        . $this->prepareMsg($msg_in );
    }
}
?>
