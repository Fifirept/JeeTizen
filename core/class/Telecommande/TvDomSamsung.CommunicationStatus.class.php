<?php 

class CommunicationStatus  {

	public $status;
	public $commande_type;
	public $commande_message;
//
	public $socket_err_code;
	public $socket_err_message;
	
	public $internal_err_message;
	public $auth_code;
	
	public $transport_layer_err_code;
	public $transport_layer_err_message;
	
	public $service_layer_err_code;
	public $service_layer_err_message;
		
	function __construct ($status,
						  $socket, 
						  $commande_type,     // CNX, CMD
						  $commande_message = '',
	                      $auth_code = 0)      // '' OU KEY = '') 
	{
		if($status === false)
		{
			$this->status 				= 1;
			
			if($socket != null) {
				$err_code  					= socket_last_error($socket);
				$this->socket_err_code  	= $err_code;
				$this->socket_err_message 	= socket_strerror($err_code);
				if($err_code == 10056)
				{
				    $this->status 				= 0;
				}
				if($err_code == 10061)
				{
				    $this->socket_err_message 	= 'Connexion refusée';
				}
			}

		} else {
			$this->socket_err_code  	= 0;
			$this->socket_err_message 	= '';
			$this->status 				= 0;
		}

		$this->commande_type 			= $commande_type;
		$this->commande_message			= $commande_message;
		$this->auth_code                = $auth_code;
		
	}

	public function getStatus() {
		return $this->status;
	}

	public function getCommande_Type() {
		return $this->commande_type;
	}
	
	public function getCommande_Message() {
		return $this->commande_message;
	}
	
	public function getSocket_err_code() {
		return $this->socket_err_code;
	}
	
	public function getSocket_err_message() {
		return $this->socket_err_message;
	}
	
	public function getInternal_err_message() {
		return $this->internal_err_message;
	}
	
	public function setInternal_err_message($internal_err_message) {
		$this->internal_err_message = $internal_err_message;
	}
	public function getAuthCode()
	{
	    return $this->auth_code;
	}
	public function toString()
	{
		return '['
		        . 'status : ' . $this->getStatus() . ' '
				. $this->getCommande_Type()
   				. '-'
   				. $this->getCommande_Message()
   				. '] code_err socket : '
   				. $this->getSocket_err_code()
   				. ' - ' . $this->getSocket_err_message()
				. ' - ' . $this->getInternal_err_message()
   				. ' - ' . $this->auth_code	
		. ']';
	}
	
	
	
}
    
?>