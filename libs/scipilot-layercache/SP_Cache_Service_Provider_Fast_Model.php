<?php

if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__). '/src');

//require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/AbstractProvider.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Stack.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/MemoryCache.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/FileCache.class.php');

/**
*  This is a class that works as a co-ordinator between the "Scipilot / Layercache" 
*  and the client application.The class gets a singleton object from 
*  SP_Cache_Layer_Stack (which is the single entry point of Scipilot) and
*  adds the layers from faster(memory cache) to slower(file cache) in SP_Cache_Layer_Stack object.
*  
*  The class also helps us to build an abstraction from the client application
*  by implementing the method like read and write. The client app can call these
*  method to read the data from the cache or write the data to the cache.
*
*  This class handover the resposniblity to 'Scipilot / Layercache' for cache
*  layers management while reading and writing the data.      
*/
class SP_Cache_Service_Provider_Fast_Model extends SP_Cache_Layer_AbstractProvider {

    /**
	* The singleton instance.
	* @private SP_Cache_Service_Provider
	*/
	private static $instance;


   /**
	* A private constructor; prevents direct creation of object.
	*/
	public  function __construct(){
	}


	/**
	*  Prevent users clone the instance
	*/
	public function __clone(){
		trigger_error('Clone is not allowed for a singleton.', E_USER_ERROR);
	}


    
    /**
	* gets an SP_Cache_Layer_Stack object and adds cache layers into it 
	*from faster(MemoryCache) to slower(FileCache) 
	* @return a singelton object of SP_Cache_Layer_Stack class 
	*/
	public static function initialize(){
	    if (!isset(self::$instance)) {
		    // get a  SP_Cache_Layer_Stack object
		    $cache = SP_Cache_Layer_Stack::singleton();
			// store SP_Cache_Layer_Stack object   
			self::$instance = $cache;


			// creates objects for SP_Cache_Layer_MemoryCache
			$clInstance1 = new SP_Cache_Layer_MemoryCache();
		    // adds MemoryCache to SP_Cache_Layer_Stack  
		    $res = $cache->registerCacheLayer($clInstance1);

		    
		    // creates objects for SP_Cache_Layer_FileCache
		    $clInstance2 = new SP_Cache_Layer_FileCache();
		    // configuring FileCache for deployment
		    $clInstance2->configure('CLIENT_TAG', 'DEPLOYMENT');
		    // adds FileCache to SP_Cache_Layer_Stack  
		    $res = $cache->registerCacheLayer($clInstance2);
        }
	
		return self::$instance;
	}

    
    /*
	* This method will read data from Cache
	* @ return string data
	*/
	public function read($key){
	
	    // get Cache Objet
	 	$cache = self::initialize();

        
        $res = $cache->read($key);
		if($res->bResult){
		    // extract it
			$data = $res->mData;
		} else {
            $data = NULL;
		}
		
		return $data;

	}

    /*
	* This method will write data to cache
	* @param String $key  
	* @param String $value 
	* @ return object
	*/
	public function write($key , $value){
        // get Cache Objet
	 	$cache = self::initialize();

	 	// Step 3, cache the data
		$res = $cache->write($key, $value);
		
		// return with error if any	
		if(!$res->bResult){
				trigger_error($res->sMessage);
		}

		return $res;
	}
}