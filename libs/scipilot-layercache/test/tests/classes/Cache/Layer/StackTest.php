<?php

if(!defined('TEST_PATH')) define ('TEST_PATH',dirname(__FILE__).'/../../../..');// set this per file!
if(!defined('SP_CLASSPATH')) define ('SP_CLASSPATH',TEST_PATH.'/../src');// leave this.

require_once(SP_CLASSPATH.'/Result.class.php');
require_once SP_CLASSPATH.'/Cache/Layer/Stack.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/MemoryCache.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/FileCache.class.php';

class SP_CacheLayerStackTest extends PHPUnit_Framework_TestCase {

	const KEY1 = 'test1';
	CONST DATA1 = 'TEST DATA 1';

	const ERROR_DATA_DIFFERENT_ = 'Cache stack returned different data for key ';

	/**
	* @var SP_Cache_Layer_Stack
	*/
	private $instance;

	function __construct(){
	}
	
	
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
	
	/** just makes target instance
	 */
	protected function setUp(){
		$this->instance = SP_Cache_Layer_Stack::singleton();
	}
	protected function setUp_MemoryLayer(){
		
	}
	
	protected function tearDown(){
		// have to call destruct to explicitly clean up the singleton, 
		$this->instance->reset();
	}
	
	
	
	/***************************************************************************/
	/**************** API TESTS ************************************************/
	/***************************************************************************/

	public function testSingleton(){
		// check it instantiates
		$this->instance = SP_Cache_Layer_Stack::singleton();
		$this->assertInstanceOf('SP_Cache_Layer_Stack', $this->instance);
		
		// check it singletons
		$instance2 = SP_Cache_Layer_Stack::singleton();
		$this->assertEquals($instance2, $this->instance, 'Singleton created different instances');
	}

	/**
	* @expectedException PHPUnit_Framework_Error 
	*/
	public function testClone(){
		$instance2 = clone $this->instance;
	}
	
	/**
	* @todo test ordering?
	*/
	public function testRegisterCacheLayer(){

		$clInstance = new SP_Cache_Layer_MemoryCache();
		$result = $this->instance->registerCacheLayer($clInstance);
		
		// check read doesn't error "no layers"
		$result = $this->instance->write(self::KEY1, 'nothing');
		$result = $this->instance->read(self::KEY1);
		$this->assertTrue($result->bResult, $result->sMessage);
	}

	/**
	* Test adding a non-CacheLayer, (protection)
	*/
	public function testRegisterCacheLayerError(){

		$clInstance = new stdClass();
		$result = $this->instance->registerCacheLayer($clInstance);
		$this->assertFalse($result->bResult, $result->sMessage);		
	}

	/**
	* @todo test write-through to lower layers.
	* @todo test data over-write?
	*/
	public function testWrite(){

		$clInstance = new SP_Cache_Layer_MemoryCache();
		$this->instance->registerCacheLayer($clInstance);

		$res = $this->instance->write(self::KEY1, self::DATA1);
		$this->assertTrue($res->bResult, $res->sMessage);
		// NOTE this test is pretty similar to the read test!
		$res = $this->instance->read(self::KEY1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$this->assertEquals(self::DATA1, $res->mData, ERROR_DATA_DIFFERENT_.self::KEY1);
			
	}

	/**
	* Test write-through caching
	*/	
	public function testWrite_Through(){
		//SP_Log_Trace::log_method('>>', 'CACHE');

		// add 2 layers
		$clInstance1 = new SP_Cache_Layer_MemoryCache();
		$res = $this->instance->registerCacheLayer($clInstance1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$clInstance2 = new SP_Cache_Layer_FileCache();
		$clInstance2->configure('CLIENT_TAG', 'DEPLOYMENT');
		$res = $this->instance->registerCacheLayer($clInstance2);
		$this->assertTrue($res->bResult, $res->sMessage);
		// write to the cache
		$res = $this->instance->write(self::KEY1, self::DATA1);
		$this->assertTrue($res->bResult, $res->sMessage);
		// read directly from layer 2
		$res = $clInstance2->read(self::KEY1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$this->assertEquals(self::DATA1, $res->mData, ERROR_DATA_DIFFERENT_.self::KEY1);

		//SP_Log_Trace::log_method('<<', 'CACHE');
	}	

	/**
	* Test error, write with no layers registered.
	*/
	public function testWriteError_NoLayers(){

		$res = $this->instance->write(self::KEY1, self::DATA1);
		$this->assertFalse($res->bResult, $res->sMessage);
		
	}
	
	/**
	*
	* @see HelloWorldProviderTest - tests that the memory lookup is faster than the disk. (see HelloWorldProviderTest)- 
	*/	
	public function testRead(){

		$clInstance = new SP_Cache_Layer_MemoryCache();
		$res = $this->instance->registerCacheLayer($clInstance);
		$this->assertTrue($res->bResult, $res->sMessage);

		$res = $this->instance->write(self::KEY1, self::DATA1);
		$this->assertTrue($res->bResult, $res->sMessage);

		$res = $this->instance->read(self::KEY1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$this->assertEquals(self::DATA1, $res->mData, ERROR_DATA_DIFFERENT_.self::KEY1);		
	}
		
	/**
	* Test write-up caching
	*/	
	public function testRead_WriteUp(){
		//SP_Log_Trace::log_method('>>', 'CACHE');

		// add 2 layers
		$clInstance1 = new SP_Cache_Layer_MemoryCache();
		$res = $this->instance->registerCacheLayer($clInstance1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$clInstance2 = new SP_Cache_Layer_FileCache();
		$clInstance2->configure('CLIENT_TAG', 'DEPLOYMENT');
		$res = $this->instance->registerCacheLayer($clInstance2);
		$this->assertTrue($res->bResult, $res->sMessage);
		// write directly to the 2nd layer cache
		$res = $clInstance2->write(self::KEY1, self::DATA1);
		$this->assertTrue($res->bResult, $res->sMessage);
		// read from the stack, triggers the write-up
		$res = $this->instance->read(self::KEY1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$this->assertEquals(self::DATA1, $res->mData, ERROR_DATA_DIFFERENT_.self::KEY1);
		// read directly from the 1st layer cache - it should now be populated!
		$res = $clInstance1->read(self::KEY1);
		$this->assertTrue($res->bResult, $res->sMessage);
		$this->assertEquals(self::DATA1, $res->mData, ERROR_DATA_DIFFERENT_.self::KEY1);

		//SP_Log_Trace::log_method('<<', 'CACHE');
	}
		
	/**
	*/
	public function testRead_ErrorNoKey(){
		//SP_Log_Trace::log_method('>>', 'CACHE');

		// test errors
		$res = $this->instance->read(null);
		$this->assertFalse($res->bResult, 'Did not get error from null key passed to read()');
		//SP_Log_Trace::log_method('<<', 'CACHE');
	}
		/**
	*/
	public function testRead_ErrorNoLayer(){

		$result = $this->instance->read(self::KEY1);
		$this->assertFalse($result->bResult, 'stack->read should error "no layers"');
	}
	

}
