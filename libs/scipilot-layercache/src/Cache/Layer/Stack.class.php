<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');

require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Log/Trace.class.php');

/**
 * The Layer Stack is a singleton used by an application as the entry point to the cache.
 * It contains as many cache layers as required and writes to them for you 
 * via a simple unified interface: just read and write.
 * Usage:
 *  a) on app init, you create your cache layers, register them with the stack
 *  b) then you write into the cache. 
 *  c) then you read from the cache.
 * 
 * @see HelloWorldProvider class for example of an app using the cache.
 */
class SP_Cache_Layer_Stack {
	const ERROR_NO_LAYERS = ' No CacheLayers registered!';

	/**
	* The singleton instance.
	* @private SP_Cache_Layer_Stack
	*/
	private static $instance;

	/**
	 * The stack of cache providers
	 * @private array
	 */
	private $aCaches = array();

	/**
	 * cache of count($aCaches) for speed
	 * @private array
	 */
	private $iNoLayers = 0;

	/**
	* The singleton method returns the single class instance.
	*/
	public static function singleton(){
		if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
	
		return self::$instance;
	}

	/**
	* A private constructor; prevents direct creation of object.
	*/
	protected function __construct(){
		//FC_Log_Trace::log_method("Cache Path={$this->sPath}", "CONFIGCACHE");
		//$this->aCaches = array();
	}

	/**
	*	Prevent users clone the instance
	*/public function __clone(){
		trigger_error('Clone is not allowed for a singleton.', E_USER_ERROR);
	}


	/** 
	* Cleans up the singleton.
	* (Does not destroy the instance, else callers may have dangling referencess)
	*/
	public function reset(){
		$this->aCaches = array();
		$this->iNoLayers = 0;
	}

	/**
	* At startup, the application should register each cache layer in turn, 
	* from first (quickest) so last (slowest)
	* e.g. 1 = memory (local), 2 = file (local), 3 = SAN (shared), 4 = DB (shared)
	* 
	* @param CacheLayer $clInstance
	* @return Result (errors on type checking)
	*/
	public function registerCacheLayer($clInstance){
		$result = new Result();

		// todo check type/interface
		if(in_array('SP_Cache_Layer', class_implements($clInstance))){
			array_push($this->aCaches, $clInstance);
			$this->iNoLayers++;
		}
		else {
			$result->Update(false, __method__.' requires SP_Cache_Layer interface');
		}

		return $result;
	}
	
	/**
	* Reads data from the highest (fastest) cache layer possible.
	* If data is missing, or expired, it checks lower levels.
	* If data is founnd in lower levels, it is automatically written-up into the higher levels.
	* Call registerCacheLayer() first.
	* 
	* @param string $sKey cache key to fetch data for
	* @return Result 
	* @return bool bResult - success if the data was returned (cache hit)
	* @return string sMessage - error message (null for hit or miss)
	* @return mixed mData - the cache data (if hit)
	*/
	public function read($sKey){
	    //SP_Log_Trace::log_method(" sKey=$sKey, {$this->iNoLayers} layers", 'CACHE');

		// presume pessimism
		$result = new Result(false);

		// validate config
		if($this->iNoLayers != 0){
			// validate input
			if(isset($sKey)){

				// Get the value from the first layer to respond
				for($i = 0; $i < $this->iNoLayers; $i++){
					//SP_Log_Trace::log_method(" Reading from cache layer #$i...", 'CACHE');

					$cache = $this->aCaches[$i];
					$res = $cache->read($sKey);
					if($res->bResult){
					    //SP_Log_Trace::log_method(" Cache layer #$i HIT with data '{$res->mData}.'", 'CACHE');
						// we hit this layer, return this data
						$result->bResult = true;
						$result->mData = $res->mData;
						break;
					}
				}

				// If it came from lower layers (e.g. shared DB in load balanced env),
				// write up the results into the higher layers.
				if($result->bResult && $i > 0){
				    //SP_Log_Trace::log_method(" Writing up data, as it came from layer #$i...", 'CACHE');
					for($j = $i-1; $j >= 0; $j--){
					    //SP_Log_Trace::log_method(" writing to layer #$j...", 'CACHE');
						$cache = $this->aCaches[$j];
						$res = $cache->write($sKey, $result->mData);
						if(!$res->bResult){
							// error writing to this cache
							trigger_error($res->sMessage);
							//break; //continue to other layers?
						}
					}
				}
				else if(!$result->bResult){
				    //SP_Log_Trace::log_method(' Cache STACK MISS.', 'CACHE');
				}
			}
			else {
				$result->bResult = false;
				$result->sMessage = __method__.' No Key provided!';
			}
		}
		else {
			$result->bResult = false;
			$result->sMessage = __method__.self::ERROR_NO_LAYERS;
		}

	    //SP_Log_Trace::log_method(' Returning '.var_export($result, true), 'CACHE');
		return $result;
	}

	/**
	*  Writes data into all cache stack layers.
	* Call registerCacheLayer() first.
	* 
	* @param string $sKey cache key to index data under
	* @return mixed $mData - the data to cache
	* @return Result 
	* @return bool bResult - success if the data was written in at least one layer
	* @return string sMessage - error message, or null.
	*/
	public function write($sKey, $mData){
	    //SP_Log_Trace::log_method(" $sKey ".var_export($mData, true), 'CACHE');
		// presume optimism
		$result = new Result();

		// validate config
		if($this->iNoLayers != 0){

			// Write through the results into the lower layers.
			// todo could this algorithm be shared with the write-up in read()?
			for($i = 0; $i < $this->iNoLayers; $i++){
				$cache = $this->aCaches[$i];
				$res = $cache->write($sKey, $mData);
				if(!$res->bResult){
					// error writing to this cache
					trigger_error($res->sMessage);
					//break; //continue to other layers?
				}
				$result->AndWith($res);
			}
		}
		else {
			$result->bResult = false;
			$result->sMessage = __method__.self::ERROR_NO_LAYERS;
		}

		return $result;
	}	
}