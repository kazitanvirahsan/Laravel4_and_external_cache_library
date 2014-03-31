<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');
require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Log/Trace.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Layer.interface.php');
require_once(SP_CLASSPATH.'/Cache/Layer/AbstractLayer.class.php');

/**
* The File implementation of the CacheLayer stores data in a local or remote filesystem.
* This is typically level 2, below Memory.
* Local files can be shared between (potentially concurrent) processes on the same server.
* Therefore if one page-load generates some data, it's available to all subsequent processes.
* Remote files (e.g. NFS, SAN) would also be available to an entire cluster of servers.
* Of course a remote filesystem could become a bottleneck in a large farm (see DB instead).
* 
* @see SP_Cache_Layer for more general information about CacheLayers
* @see SP_Cache_Layer_AbstractLayer for common functionality shared between all device-specific implementations.
*/
class SP_Cache_Layer_FileCache extends SP_Cache_Layer_AbstractLayer {
	/**
	* Path to the temp folder, e.g. system or your own personal httpd-writable area.
	* $var string
	*/
	static $TEMP_PATH = '';
	const RELATIVE_PATH = 'fc/cache/clients';
	//const CACHE_PERIOD = 900; //15 MINS

	const ERROR_NO_PATH = ' Path not configured! Call configure().';
	const ERROR_CACHE_FILE_RO = ' Cache file not writable: ';

	/**
	* Path to cache, inc trailing/
	* @var string
	*/
	private $sPath = '';

	/**
	* Data compression option - could speed up disk IO as long as it's easy to compress.
	* @var boolean
	*/
	private $bCompress = false;

	private $sClientTag = 'default_client';
	private $sDeployment = 'default_deploy';

	function __construct(){
		parent::__construct();
	}

	/*  
	* Call this during app startup, before singleton() to configure the folders used.
	* @param string  $sClientTag optional; tagname of client, used to separate client's caches in multi-client install
	* @param string  $sDeployment optional; e.g. dev/staging/production, or d/s/p whatever you want
	*/
	public function configure($sClientTag, $sDeployment, $bCompress=false){
		$this->sClientTag = $sClientTag;
		$this->sDeployment = $sDeployment;
		$this->bCompress = $bCompress;
	    $this->setupPath();
	}

	/* 
	* Check folder structure exists, and set $this->sPath
	*/
	private function setupPath(){
		//SP_Log_Trace::log_method(" (sClientTag={$this->sClientTag}, sDeployment={$this->sDeployment})", 'CACHE');
		if(self::$TEMP_PATH == ''){
			self::$TEMP_PATH = sys_get_temp_dir();
		}

		$relPath = join('/', array(self::RELATIVE_PATH, $this->sClientTag, $this->sDeployment));
		$this->sPath = join('/', array(self::$TEMP_PATH, $relPath));
		//SP_Log_Trace::log_method(" \$this->sPath={$this->sPath}", 'CACHE');

		// Check each folder in the path from temp downwards, for creation and writeability
		$aPath = explode('/', $relPath);
		$sPath = self::$TEMP_PATH.'/';
		foreach($aPath as $folder){
			//SP_Log_Trace::log_method(" checking $folder...", 'CACHE');
			$sPath .= '/'.$folder;
			if(!file_exists($sPath)){	
				//SP_Log_Trace::log_method(" mkdir'ing $folder", 'CACHE');
				if(!mkdir($sPath)){
					trigger_error(__METHOD__.' Could not create cache folder ($sPath). Is the parent folder not writable? TEMP_PATH='.self::$TEMP_PATH, 
					              E_USER_WARNING);
				}
			}
		}
	}

	/**
	* (non-phpdoc)
	*/
	public function read($sKey){
		//SP_Log_Trace::log_method("($sKey)", 'CACHE');
		$result = new Result();

		// VALIDATE config
		if($this->sPath != ''){

			// validate iNput
			if(isset($sKey)){
				$sFilePath = $this->path($sKey);

				if(file_exists($sFilePath)){
					//SP_Log_Trace::log_method(" using cache file $sFilePath", 'CACHE');
					$data = file_get_contents($sFilePath);
					//SP_Log_Trace::log_method(" got data: $data", 'CACHE');
					if($this->bCompress) $data = SP_Wrap_Compression::uncompress();
					//SP_Log_Trace::log_method(" uncompressed ({$this->bCompress}): $data", 'CACHE');
					$data = unserialize($data);
					//SP_Log_Trace::log_method(" unserialized: {$result->mData} ", 'CACHE');
					$expiry = $data[0]; // extract meta data
					$data = $data[1];
					//SP_Log_Trace::log_method(" unpacked: $expiry, $data ", 'CACHE');
					if(time() < $expiry){
						$result->mData = $data;
					}
					else {
						// CACHE MISS(EXPIRED)
						//SP_Log_Trace::log_method("cache MISS(EXPIRED)", 'CACHE');
						$result->bResult = false;
						$result->sMessage = 'expired';//debug
						// CLEAR THE cache
						// @todo
					}
				}
				else {
					// CACHE MISS
					//SP_Log_Trace::log_method("cache MISS", 'CACHE');
					$result->bResult = false;
				}
			}
			else {
				$result->bResult = false;
				$result->sMessage = __METHOD__.self::ERROR_NO_KEY;
			}    
		}
		else {
			$result->bResult = false;
			$result->sMessage = __METHOD__.self::ERROR_NO_PATH;
		}    
	
		return $result;
	}

	/**
	* (non-phpdoc)
	*/
	public function write($sKey, $mData, $iExpiresIn=null){
		//SP_Log_Trace::log_method("($sKey, ". var_export($mData, true).", $iExpiresIn)", 'CACHE');
		$result = new Result();

		// VALIDATE config
		if($this->sPath != ''){

			//sanitise keys?
			//$sKey = self::key($sKey);
			// validate iNput
			if(isset($sKey)){
				$sFilePath = $this->path($sKey);
				// fallback to default expiry
				if($iExpiresIn == null) $iExpiresIn = $this->iExpirySecs;

				//SP_Log_Trace::log("writing $mData to ".$sFilePath, 'CACHE');
				$data = array(time()+$iExpiresIn, $mData); //wrap in metadata
				//SP_Log_Trace::log_method(" packed: ".var_export($data, true), 'CACHE');
				$data = serialize($data);
				if($this->bCompress) $data = SP_Wrap_Compression::compress($data);
				if(file_put_contents($sFilePath, $data, LOCK_EX) === false){
					$result->bResult = false;
					$result->sMessage = __METHOD__.self::ERROR_CACHE_FILE_RO.$sFilePath;
				}
			}
			else {
				$result->bResult = false;
				$result->sMessage = __METHOD__.self::ERROR_NO_KEY;
			}    
		}
		else {
			$result->bResult = false;
			$result->sMessage = __METHOD__.self::ERROR_NO_PATH;
		}    
		return $result;
	}

  
	/**
	* Returns a path to access the cache.
	* @param string $sKey Cache Key from key()
	*/
	private function path($sKey){
		return $sFilePath = "{$this->sPath}{$sKey}.cache";
	}	


}