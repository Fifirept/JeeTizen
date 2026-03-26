
<?php

if(defined('WEB')) {
    include_file ( 'core', 'Logger', 'class', 'JeeTizen' );
    // phpwol (tomnomnom) chargé en lazy dans WakeOnlan() uniquement
    
}else {
//    require_once __DIR__. '/../Logger.class.php';
}

class TvParametres
{
    // properties

    const   MODELE_LEGACY         = 0;
    const   TIZEN_MODELE_STANDARD = 0;
    const   TIZEN_MODELE_K        = 1;
    const   TIZEN_MODELE_J        = 2;
    const   TIZEN_MODELE_DEFAUT   = 3;

    
    const   LEGACY = 'legacy';
    const   TIZEN  = 'tizen';
    
    const   WOL_MODE_DIRECT       = 'direct';
    const   WOL_MODE_BROADCAST    = 'broadcast';
    
    private  $eqLogicId;               // eqLogicId
    
	private  $remoteIP 			= "192.168.1.11";
	private  $remotePort 		= 55000;
	private  $ssl;        		// flag ssl_on | ssl_off
	
	private  $tvApp 			= "iphone.UE558000.iapp.samsung";
	
	private  $modele_tv;  		// tizen | legacy
	private  $sub_modele_tv;  	// tizen | legacy
	private  $modele_k_delai;  	// 0 or delai in ms
	
	private  $wol;        		// wol enabled on | off
	private  $wol_mode;        	// wol on { 'direct', 'broadcast'}
	private  $wol_subnet;       // wol on 'broadcast' -> subnet
	private  $wol_direct;       // wol on 'broadcast' -> subnet
	
	
	private  $macAdresse;       // adresse mac TV
	
	private  $tokenAuth;        // token Smart Tv Tizen
	
	public static function getInstanceFromConfig($eqLogic) {
	
	    $wol_mode = '';
	    if ($eqLogic->getConfiguration('wol_tv', 0)) {
	        $wol_mode = $eqLogic->getConfiguration('wol_tv_direct', 0)  ? TvParametres::WOL_MODE_DIRECT : TvParametres::WOL_MODE_BROADCAST;
	    }
	    
	    $pars =   new TvParametres(
	        $eqLogic->getId(),                         // id du eqLogic
	        $eqLogic->getConfiguration('ip_tv'),
	        $eqLogic->getConfiguration('port_tv'),
	        $eqLogic->getConfiguration('app_tv'),
	        $eqLogic->getConfiguration('modele_tv'),
	        $eqLogic->getConfiguration('sub_modele_tv'),
	        $eqLogic->getConfiguration('sub_modele_tv_delay', 0),
	        $eqLogic->getConfiguration('ssl_tv', 0),
	        $eqLogic->getConfiguration('wol_tv', 0),
	        $wol_mode,
	        $eqLogic->getConfiguration('wol_broadcast_ip_direct', ''),
	        $eqLogic->getConfiguration('wol_broadcast_subnet', ''),
	        $eqLogic->getConfiguration('adresse_mac_tv'),
	        $eqLogic->getConfiguration('tokenAuth')
	        );

	    Logger::debug ('TvParametres->getInstanceFromConfig parametres ->', $pars->toString() );
	    
	    return $pars;
	}
	
	public function __construct($eqLogicId, $remoteIp, $remotePort, $tvApp, 
	                            $modele_tv, $sub_modele_tv, $modele_k_delai, $ssl, 
	                            $wol, $wol_mode, $wol_direct,$wol_subnet,  $macAdresse, 
	                            $tokenAuth = '') {

        $this->eqLogicId      = $eqLogicId;
	                                
		$this->remoteIP       = $remoteIp;
		$this->remotePort     = $remotePort;
		
		if($tvApp == '')
		    $tvApp            = 'jeedom.tizen.app.samsung';

		$this->tvApp          = $tvApp;
		$this->modele_tv      = $modele_tv;
		$this->sub_modele_tv  = $sub_modele_tv;
		$this->modele_k_delai = $modele_k_delai;
		
		$this->ssl            = $ssl;
		
		$this->wol            = $wol;
		$this->wol_mode       = $wol_mode;
		$this->wol_direct     = $wol_direct;
		$this->wol_subnet     = $wol_subnet;
		
		$this->macAdresse     = $macAdresse;
		
		$this->tokenAuth      = $tokenAuth;
	}
	
	public function getEqLogicId()
	{
	    return $this->eqLogicId;
	}
	public  function getRemoteIP() {
		return $this->remoteIP;
	}
	
	public  function getRemotePort() {
		return $this->remotePort;
	}
	
	public  function getTvApp() {
		return $this->tvApp;
	}	
	
	public function getModeleTv()
	{
	    return $this->modele_tv;
	}
	
	public function getSubModeleTv()
	{
	    return $this->sub_modele_tv;
	}
	
	public function getTokenAuth()
	{
	    return $this->tokenAuth;
	}
	
	public function setTokenAuth($_tokenAuth)
	{
	    $this->tokenAuth = $_tokenAuth;
	}
	
	public  function isLegacyTv() {
		return $this->modele_tv == TvParametres::LEGACY;
	}
	
	public  function isTizenTv() {
        return $this->modele_tv === TvParametres::TIZEN;
	}
	
	public  function isTizenTvModeleAutre() {
	    return $this->isTizenTv() && $this->sub_modele_tv == TvParametres::TIZEN_MODELE_STANDARD;
	}
	
	public  function isTizenTvModeleK() {
	    return $this->isTizenTv() && $this->sub_modele_tv == TvParametres::TIZEN_MODELE_K;
	}
	
	public  function isTizenTvModeleJ() {
	    return $this->isTizenTv() && $this->sub_modele_tv == TvParametres::TIZEN_MODELE_J;
	}
	
	public  function isSSLEncoded() {
		return $this->ssl == true;
	}
	
	public  function getProtocol() {
	    
	    return $this->isTizenTv() ? $this->isSSLEncoded() ? 'wss://' : "ws://"  : '';
	}
	
	public function getMacAdresse()
	{
	    return $this->macAdresse;
	}
	
	public function isWOL()
	{
	    return $this->wol;
	}
	
	public function setWolOff()
	{
	    $this->wol = 0;
	}

	public function canWakeOnlan()
	{
	    Logger::debug('canWake - wol :' , $this->toString());
	    return $this->wol && $this->macAdresse != null && $this->macAdresse != '';
	}
	
	public function getModeleKDelai()
	{
	    return $this->modele_k_delai;
	}
	
	public function getTizenConnexionUrl()
	{
	    return  $this->getProtocol()
		       . $this->remoteIP
		       . ':'
		       . $this->remotePort
			   . '/api/v2/channels/samsung.remote.control'
			   . '?name='
			   . base64_encode($this->tvApp)
	           . (($this->tokenAuth !== null && $this->tokenAuth != '') ? '&token=' . $this->tokenAuth : '');
	}
	
	public function getClassName()
	{
	    if($this->isTizenTv() === false) {
	        return 'ConnectorImpl_Legacy';
	    }
        /*
         * ConnectorImpl_Tizen_Modeles_Standard : textalk/websocket (PHP 8 compatible, SSL)
         * ConnectorImpl_Tizen_Modeles_K : ratchet/reactphp (incompatible PHP 8.1+)
         * ConnectorImpl_Tizen_Modeles_J : daemon encrypted
         *
         * Par défaut, utiliser Standard qui supporte WSS + token + appairage
         */
	    switch($this->sub_modele_tv) {

	        case TvParametres::TIZEN_MODELE_STANDARD:
	            return 'ConnectorImpl_Tizen_Modeles_Standard';
	        
	        case TvParametres::TIZEN_MODELE_K:
	            return 'ConnectorImpl_Tizen_Modeles_Standard'; // Ratchet incompatible PHP 8.1+, Standard gère SSL
	        
	        case TvParametres::TIZEN_MODELE_J:
	            return 'ConnectorImpl_Tizen_Modeles_J';
	    
	        default:
	            return 'ConnectorImpl_Tizen_Modeles_Standard';
	    }
	}

    public function getShortUrl()
    {
        return $this->remoteIP
		       . ':'
		       . $this->remotePort;
    }

    public function getWolSubnet() {
        return ($this->wol_mode == TvParametres::WOL_MODE_BROADCAST && $this->wol_subnet !== '' ? $this->wol_subnet : null);
        //return $this->wol_subnet;
    }
    
    public function getWolIpDirect() {
        return $this->wol_direct;
    }
    
    public function getWolMode() {
        return $this->wol_mode;
    }
    
 	public  function toString() {
	    return    '[eqLogicId : '       . $this->eqLogicId        . ']'
		        . '[remote : "'         . $this->remoteIP         . '":' . $this->remotePort . '], ' 
				. '[tvApp : "'          . $this->tvApp            . '"], '
				. '[modele_tv : "'      . $this->modele_tv        . '"], '
				. '[sub_modele_tv : '   . $this->sub_modele_tv    . '], '
			    . '[modele_k_delai : '  . $this->modele_k_delai   . '], '
		        . '[ssl : '             . $this->ssl              . '], '
		        . '[wol : '             . $this->wol              . '], '
		        . '[wol_mode : "'       . $this->wol_mode         . '"], '
	            . '[wol_direct : "'     . $this->wol_direct       . '"], '
                . '[wol_subnet : "'     . $this->wol_subnet       . '"], '
                . '[mac tv : "'         . $this->macAdresse       . '"], '
				. '[tokenAuth : '       . $this->tokenAuth        . ']'
				;
	}
	
	public function WakeOnlan()
	{
        switch($this->getWolMode())
        {
            case TvParametres::WOL_MODE_DIRECT:
                Logger::debug('TvParametres->WakeOnlan() : try WOL Direct IP Broadcast ' . $this->getWolIpDirect());
                $communicationStatus = self::WakeUp($this->getWolIpDirect());
                break;
            case TvParametres::WOL_MODE_BROADCAST:
                Logger::debug('TvParametres->WakeOnlan() try WOL Broadcast I�/SUBNET ' . $this->getRemoteIP() . ' ' .  $this->getWolSubnet() );
                $communicationStatus = self::WakeUp($this->getRemoteIP(), $this->getWolSubnet());
                break;
            default:
                Logger::debug('No WOL Broadcast !!!');
                $communicationStatus = new CommunicationStatus(true, null, '', $_error);
                break;
        }
        return $communicationStatus;
	}
	
	private function WakeUp($_Ip, $_SubNet = null) 
	{
        // Lazy loading phpwol
        require_once __DIR__ . '/../../../3rdparty/vendor/tomnomnom/autoload.php';
        
        Logger::debug('WOL  IP ', '\''      . $_Ip . '\'');
        Logger::debug('WOL  MAC ', '\''     . $this->getMacAdresse()  . '\'');
        Logger::debug('WOL  SubNet ', '\''  . ($_SubNet == null ? 'null' : $_SubNet ) . '\'');
        
	    
	    $_factory     = new \Phpwol\Factory();
	    $_magicPacket = $_factory->magicPacket();
	    $_result      = $_magicPacket->send($this->getMacAdresse(), $_Ip, $_SubNet);
	    
	    if (!$_result) {
	        Logger::debug('WOL error result/ ', $_result . ' ' . $_magicPacket->getLastError());
	        $_error = '';
	        switch ($_magicPacket->getLastError()) {
	            case 1:
	                $_error = __('adresse IP invalide', __FILE__);
	                break;
	            case 2:
	                $_error = __('adresse MAC invalide', __FILE__);
	                break;
	            case 4:
	                $_error = __('SUBNET invalide', __FILE__);
	                break;
	            default:
	                $_error = $_magicPacket->getLastError();
	                break;
	        }
	        $communicationStatus = new CommunicationStatus(false, null, 'error WOL', $_error);
	    } else {
	        Logger::debug('retour WOL  ', 'success');
	        $communicationStatus = new CommunicationStatus(true, null, 'wol', 'success');
	    }
	    return $communicationStatus;
	}
}
?>