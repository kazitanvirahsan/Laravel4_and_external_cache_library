<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');

require_once(SP_CLASSPATH.'/Result.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/AbstractProvider.class.php');
require_once(SP_CLASSPATH.'/Cache/Layer/Stack.class.php');

/**
* An example concrete base class for Provider implementations.
* Use primarily by the Unit Tests
* These are the classes which generate data to be stored in the layer cache.
* 
* Note that in real life the APPLICATION would setup the cache layers, 
* but in the Unit tests, the test class sets up the environment.
* We don't know the caching environment here (how many layers etc) - which is correct!
* The cache is a black box to me.
*/
class SP_Cache_Layer_HelloWorldProvider extends SP_Cache_Layer_AbstractProvider {
	const CACHE_KEY1 = 'key1';

	/**
	* This method returns some data, either computed or from the cache.
	* @ return string data
	*/
	public function getComputationallyExpensiveData(){
		$data = null;
		$cache = SP_Cache_Layer_Stack::singleton();
		$key = __CLASS__.CACHE_KEY1;

		// Step 1, check the cache for prefab data
		$res = $cache->read($key);
		if($res->bResult){
			// extract it
			$data = $res->mData;
		}
		else {
			// Step 2, make the data 
			sleep(1);
			$data = date('Y/m/d H:i:s').' LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ';

			// Step 3, cache the data
			$res = $cache->write($key, $data);
			
			// whatever error handling strategy you choose here:
			if(!$res->bResult){
				trigger_error($res->sMessage);
			}
		}

		return $data;
	}
}