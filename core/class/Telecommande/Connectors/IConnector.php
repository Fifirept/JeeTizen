<?php 

Interface IConnector {
    
    public function sendKey($_data);
    public function getTvModele();
    public function authenticate();
    public function getClassName();
}


?>