<?php

class MessageTv
{
    private $_message;
    private $_message_type;  // 0 sendkey, else authenticate

    public function __construct($_message_type, $_message)
    {
        $this->_message      = $_message;
        $this->_message_type = $_message_type;
    }

    public function getMessageType()
    {
        return $this->_message_type;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function isSendKeyMessage()
    {
        return $this->_message_type == 0;
    }

    public function isAuthentificationMessage()
    {
        return $this->_message_type == 1;
    }
}
?>
