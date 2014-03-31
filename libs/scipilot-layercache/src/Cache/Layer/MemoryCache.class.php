<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');

require_once(SP_CLASSPATH.'/Result.class.php');
//require_once(SP_CLASSPATH.'/Log/Trace.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Layer.interface.php');
require_once(SP_CLASSPATH.'/Cache/Layer/AbstractLayer.class.php');

/**
* The Memory implementation of the Layer Cache is used to cache data for a process.
* It's not shared memory (like memcached), so it's only useful for speeding up access
* to computed data during a page-load cycle (or whatever the process execution cycle is).
* 
* @see SP_Cache_Layer for more general information about CacheLayers
* @see SP_Cache_Layer_AbstractLayer for common functionality shared between all device-specific implementations.
*/
class SP_Cache_Layer_MemoryCache extends SP_Cache_Layer_AbstractLayer {

	/**
	* Memory cache of disk cache items
	* Indexed by key, created by this->key()
	* @var array 
	*/
	private static $aMemCache = array();

	function __construct(){
		parent::__construct();
	}

	/**
	* (non-phpdoc)
	*/
	public function read($sKey){
		//SP_Log_Trace::log_method("($sKey)", 'CACHE');//debug
		$result = new Result();

		// validate imput
		if(isset($sKey)){

			// First check memory cache.
			if(isset(self::$aMemCache[$sKey])){
				//SP_Log_Trace::log_method(' Memory cache HIT ', 'CACHE');
				$data = self::$aMemCache[$sKey];

				// check expiry
				$expiry = $data[0]; // extract meta data
				$data = $data[1];
				//SP_Log_Trace::log_method(" unpacked: $expiry, $data ", 'CACHE');
				if(time() < $expiry){
					$result->mData = $data;
				}
				else {
					// CACHE MISS(EXPIRED)
					//SP_Log_Trace::log_method("cache MISS(EXPIRED) ".time(), 'CACHE');
					$result->bResult = false;
					$result->sMessage = 'expired';//debug only
					// CLEAR THE cache
					// @todo
				}

			}
			else{
				$result->bResult = false;
				//SP_Log_Trace::log_method(' Memory cache MISS ', 'CACHE'); //debug
			}

		}
		else {
			$result->bResult = false;
			$result->sMessage = __method__.ERROR_NO_KEY;
			//SP_Log_Trace::log_method(' Memory cache error '.$result->sMessage, 'CACHE'); //debug
		}    

		return $result;
	}

	/**
	* (non-phpdoc)
	*/
	public function write($sKey, $mData, $iExpiresIn=null){
		//SP_Log_Trace::log_method("($sKey, ". var_export($mData, true).", $iExpiry)", 'CACHE');
		$result = new Result();

		//sanitise keys?
		//$sKey = self::key($sKey);

		// validate input
		if(isset($sKey)){

			// fallback to default expiry
			if($iExpiresIn == null) $iExpiresIn = $this->iExpirySecs;
			//SP_Log_Trace::log_method(" iExpiresIn=$iExpiresIn parent={$this->iExpirySecs}", 'CACHE');

			// Wrap in metadata, add expiry
			$data = array(time()+$iExpiresIn, $mData); 
			//SP_Log_Trace::log_method(" packed: ".var_export($data, true), 'CACHE');

			// Memory cache it.
			self::$aMemCache[$sKey] = $data;

		}
		else {
			$result->bResult = false;
			$result->sMessage = __METHOD__.self::ERROR_NO_KEY;
		}    

		return $result;
	}

	/**
	* (non-phpdoc)
	*/
	public function setExpiry($iSeconds){
		$this->iExpirySecs = $iSeconds;
	}
	
}