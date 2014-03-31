<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');
require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Log/Trace.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Layer.interface.php');
require_once(SP_CLASSPATH.'/Cache/Layer/AbstractLayer.class.php');

/**
* Implements a Cache Layer, in a MySQL database, using the old 'mysql' driver.
* This is typically level 3, below File.
* If there is no remote filesystem, this is the main shared cache in a cluster of web servers.
* This class can create its own table in its own database, or you can provide one.
* It can manage its own connections, or you can provide one.
* 
* @see SP_Cache_Layer for more general information about CacheLayers
* @see SP_Cache_Layer_AbstractLayer for common functionality shared between all device-specific implementations.
*/
class SP_Cache_Layer_MySQLICache extends SP_Cache_Layer_AbstractLayer {
	const DEFAULT_TABLE_NAME = 'layercache';
	
	const ERROR_NO_CONNECTION = 'Database connection not configured!'; 

	/**
	* @var string
	*/
	private $sHostname;
	/**
	* @var string
	*/
	private $sUsername;
	/**
	* @var string
	*/
	private $sPassword;
	/**
	* @var string
	*/
	private $sDatabase;
	/**
	* @var string
	*/
	private $sTable = self::DEFAULT_TABLE_NAME;
	/**
	* @var mysqli
	*/
	private $mysqli;

	/**
	* Data compression option - could speed up IO as long as it's easy to compress.
	* @var boolean
	*/
	private $bCompress = false;

	//? private $sClientTag = 'default_client';
	//? private $sDeployment = 'default_deploy';

	function __construct(){
		parent::__construct();
	}

	/*  
	* Call this during app startup, before singleton() to configure the cache.
	* Use this method if you already have a database connection you wish the cache layer to use.
	* 
	* @see configureWithCredentials if you want the cache manager to connect to the db itself;
	*
	* @param resource $mysqli A pre-established MySQL Database connection. Otherwise this library will connect itself.
	* @param string $sTable Optional, defaults to internally managed table
	* @param boolen $sCompress Optional, default false. Whether to compress the data in the cache. Might speed up IO as long as it's easy to compress.
	*/
	public function configureWithConnection($mysqli, $sTable=null, $bCompress=false){
	    $this->mysqli = $mysqli;
		if($sTable != null) $this->sTable = $sTable;
		$this->bCompress = $bCompress;
	}

	/*  
	* Call this during app startup, before singleton() to configure the cache.
	* Use this method if you want the cache manager to connect to the db itself;
	*
	* @see configureWithConnection if you already have a database connection you wish the cache layer to use.	
	*
	* @param string $sHostname Optional, defaults to localhost; MySQL server.
	* @param string $sUser name of DB user to connect with.
	* @param string $sPassword 
	* @param string $sDatabase name of DB to connect to.
	* @param string $sTable Optional; defaults to internally managed table
	* @param boolen $sCompress Optional; default false, whether to compress the data in the cache. Might speed up IO as long as it's easy to compress.
	*/
	public function configureWithCredentials($sHostname, $sUsername, $sPassword, $sDatabase, $sTable=null, $bCompress=false){
		if($sHostname != null) $this->sHostname = $sHostname;
		$this->sUsername = $sUsername;
		$this->sPassword = $sPassword;
		$this->sDatabase = $sDatabase;
		if($sTable != null) $this->sTable = $sTable;
		$this->bCompress = $bCompress;
		
		$this->connect();
	}

	/* 
	*/
	private function connect(){
		//SP_Log_Trace::log_method(" (sDatabase={$this->sDatabase}, sTable={$this->sTable})", 'CACHE');
		
		// connect to DB?
		$this->mysqli = mysqli_connect($this->sHostname, $this->sUsername, $this->sPassword, $this->sDatabase);
		if ($this->mysqli->connect_error) {
			trigger_error(__METHOD__. $mysqli->connect_errno . $mysqli->connect_error);
		}

	}

	private function createTable(){
		if($this->mysqli){
			//TODO SHOULD BE BINARY BUT IT DIDN'T SAVE THE DATA?
			$sql = 'CREATE TABLE IF NOT EXISTS '.$this->sTable.' (
				lc_key varchar(100) NOT NULL PRIMARY KEY,
	  			lc_data TEXT,
	  			lc_expiry INT COMMENT \'When this record expires in UTC\')';
			
			if ($this->mysqli->query($sql) !== TRUE) {
				trigger_error(__METHOD__. $this->mysqli->error);
			}
		}
		else {
			trigger_error(__METHOD__.self::ERROR_NO_CONNECTION	);
		}    
	}


	/** Creates the self-managed table.
	*/
	public function install(){
		$this->createTable();
	}

	/**
	* (non-phpdoc)
	*/
	public function read($sKey){
		//SP_Log_Trace::log_method("($sKey)", 'CACHE');
		$result = new Result();

		// VALIDATE config
		if($this->mysqli != null){

			// validate iNput
			if(isset($sKey)){

				// Fetch the data
				$sql = 'SELECT lc_data, lc_expiry FROM `'.$this->sTable.'` WHERE lc_key = ?';
				if($stmt = $this->mysqli->prepare($sql)){
					$stmt->bind_param('s', $sKey);
					if (!$stmt->execute()) {
						trigger_error(__METHOD__.$stmt->errno.$stmt->error);
					}
					if(!$stmt->bind_result($data, $expiry)){
						trigger_error(__METHOD__.$stmt->errno.$stmt->error);
					}
					$stmt->fetch();
					//SP_Log_Trace::log_method(" fetched \$data=$data, \$expiry=$expiry", 'CACHE');
					
					$stmt->close();
					
					if($data){
						//SP_Log_Trace::log_method(" unpacked: $expiry, $data ", 'CACHE');
						if(time() < $expiry){

							if($this->bCompress) $data = SP_Wrap_Compression::uncompress();
							//SP_Log_Trace::log_method(" uncompressed ({$this->bCompress}): $data", 'CACHE');
							$data = unserialize($data);
							//SP_Log_Trace::log_method(" unserialized: {$result->mData} ", 'CACHE');

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
					// failed to prepare statement
					$result->bResult = false;
					$result->sMessage = __METHOD__.$this->mysqli->error;
				}
			}
			else {
				$result->bResult = false;
				$result->sMessage = __METHOD__.self::ERROR_NO_KEY;
			}    
		}
		else {
			$result->bResult = false;
			$result->sMessage = __METHOD__.self::ERROR_NO_CONNECTION;
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
		if($this->mysqli != null){

			// validate input
			if(isset($sKey)){

				// fallback to default expiry
				if($iExpiresIn == null) $iExpiresIn = $this->iExpirySecs;
				$expiry = time()+$iExpiresIn;

				// pre-process the data
				$data = serialize($mData);
				if($this->bCompress) $data = FC_Wrap_Compression::compress($data);

				// write it
				//SP_Log_Trace::log('writing '.$data.' to db '.$this->sTable, 'CACHE');
				$sql = 'INSERT INTO `'.$this->sTable.'` (lc_key, lc_data, lc_expiry)'
					.' VALUES (?,?,?)'
					.' ON DUPLICATE KEY UPDATE lc_data = ?, lc_expiry = ?';
				$stmt = $this->mysqli->prepare($sql);
				if($stmt){
					//TODO SHOULD BE BINARY BUT IT DIDN'T SAVE THE DATA?
					$stmt->bind_param('ssisi', $sKey, $data, $expiry, $data, $expiry);
					if (!$stmt->execute()){
						$result->bResult = false;
						$result->sMessage = __METHOD__.' Error executing write:'.$stmt->errno.$stmt->error;
					}
					$stmt->close();
				}
				else {
					// failed to prepare statement
					$result->bResult = false;
					$result->sMessage = __METHOD__.$this->mysqli->error;
				}
			}
			else {
				$result->bResult = false;
				$result->sMessage = __METHOD__.self::ERROR_NO_KEY;
			}    
		}
		else {
			$result->bResult = false;
			$result->sMessage = __METHOD__.self::ERROR_NO_CONNECTION;
		}    
		return $result;
	}

}