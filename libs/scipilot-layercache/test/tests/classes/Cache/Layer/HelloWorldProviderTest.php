<?php

if(!defined('TEST_PATH')) define ('TEST_PATH',dirname(__FILE__).'/../../../..');// set this per file!
if(!defined('SP_CLASSPATH')) define ('SP_CLASSPATH',TEST_PATH.'/../src');// leave this.

require_once(SP_CLASSPATH.'/Result.class.php');
require_once SP_CLASSPATH.'/Cache/Layer/Stack.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/MemoryCache.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/HelloWorldProvider.class.php';

class SP_Cache_Layer_HelloWorldProvider_Test extends PHPUnit_Framework_TestCase {

	/***************************************************************************/
	/**************** TEMPLATE FUNCTIONS****************************************/
	/***************************************************************************/
	
	public static function setUpBeforeClass(){
		date_default_timezone_set('Australia/NSW');	// to avoid warnings.
		//
		/* (debug, only needed when test run alone)
		// todo: Should the logger do this?!
		$sPath = sys_get_temp_dir().'/fc/logs';
	    if(!file_exists($sPath)){
			mkdir($sPath);
		}
		//SP_Log_Trace::path('CACHE', $sPath.'/cache.log');
		//*/
	}
	
	/**
	 */
	protected function setUp(){
	    //SP_Log_Trace::log_method('', "CACHE");

		$this->instance = new SP_Cache_Layer_HelloWorldProvider();

		$this->cache = SP_Cache_Layer_Stack::singleton();
		$result = $this->cache->registerCacheLayer(new SP_Cache_Layer_MemoryCache());
	}
	
	protected function tearDown(){
		
	}
	
	
	
	/***************************************************************************/
	/**************** API TESTS ************************************************/
	/***************************************************************************/

	
	public function testGetComputationallyExpensiveData(){
	    //SP_Log_Trace::log_method('', "CACHE");
		$ts = array();

		// first one = slow
		$ts[] = microtime(true);
		$data1 = $this->instance->getComputationallyExpensiveData();
		$ts[] = microtime(true);
		$missTime = $ts[1] - $ts[0];
		// this means the test was wrongly configured, not really a bug in the target code...
		$this->assertGreaterThan(1, $missTime, "Cache miss should be slow! Cache miss took $missTime. The test needs adjusting.");

		// second one = faster and same data
		$ts[] = microtime(true);
		$data2 = $this->instance->getComputationallyExpensiveData();
		$ts[] = microtime(true);

		$this->assertEquals ($data1, $data2);
		$hitTime = $ts[3] - $ts[2];
		$this->assertLessThan(0.05, $hitTime, "Cache hit should be quicker than this! Cache hit took $hitTime");
		$this->assertLessThan($missTime, $hitTime, "Cache hit should be quicker than miss!");
	}
	
}
