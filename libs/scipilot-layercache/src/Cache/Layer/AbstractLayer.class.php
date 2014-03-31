<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');
require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Log/Trace.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Layer.interface.php');

/**
* Provides an abstract base class for Layer implementations to extend from .
*/
abstract class SP_Cache_Layer_AbstractLayer implements SP_Cache_Layer {
	const EXPIRY_DEFAULT = 900; //15 mins
	
	const ERROR_NO_KEY = ' Key not provided!';

	protected $iExpirySecs;

	function __construct (){
		$this->iExpirySecs = self::EXPIRY_DEFAULT;
	}

	/**
	* (non-phpdoc)
	*/
	public function setExpiry($iSeconds){
		$this->iExpirySecs = $iSeconds;
	}

}