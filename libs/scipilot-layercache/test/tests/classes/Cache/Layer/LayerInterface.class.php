<?php

if(!defined('TEST_PATH')) define ('TEST_PATH',dirname(__FILE__).'/../../../..');// set this per file!
if(!defined('SP_CLASSPATH')) define ('SP_CLASSPATH',TEST_PATH.'/../src');// leave this.

require_once TEST_PATH.'/fixtures/GeneralFixtures.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/Layer.interface.php';

/**
* This test class is inherited by all CacheLayer test classes.
* It provides common Layer tests - as they all share the same interface.
* They just need to do any setup, e.g. db or file-specific.
* NB: they must call all template functions on super: setup/teardown, class and per test.
*/
class SP_Cache_LayerInterfaceTest extends PHPUnit_Framework_TestCase {
	
	const ERROR_DIFFERENT_DATA_ = 'Cache returned different data for key ';
	const ERROR_UNKNOWN_DATA_ = 'Cache returned non-known data for key ';

	/*
	* If you add any tests to this class, you must increment this number.
	* It's used to check that our children are all updated to call every inherited test.
	*/
	const CHECK_NO_TESTS = 11;

	/**
	* How many processes to run concurrently to test asynchronous cache writes.
	* @var integer
	*/
	const CONCURRENT_STRESS_LEVEL = 50;
	private static $iProcessesRunning = 0;

	/**
	* @var SP_Cache_Layer
	*/
	protected $instance;

	/**
	* @var integer
	*/
	private static $iTestsRun = 0;

	function __construct(){
	}
	
	/*
	* Override this to provide test specimens to the parent class.
	* @return SP_Cache_Layer
	*/
	protected function createInstance(){}

	/**
	* Internal DRY function to write to the cache then read it back and verify it's the same with Asserts.
	*/
	private function writeReadVerify($sKey, $mData){				
		//SP_Log_Trace::log_method(microtime().'  ', 'CACHE');//debug
		$result = $this->instance->write($sKey, $mData);
		$this->assertTrue($result->bResult, $result->sMessage);

		$result = $this->instance->read($sKey);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertEquals($mData, $result->mData, self::ERROR_DIFFERENT_DATA_.$sKey);
	}
	
	/**
	* Concurrent-safe function, which write some known fixture into the cache, 
	 * and then tests read returns a known fixture too,
	 * but not necessarily the same one! (in the case of concurrent overwrites)
	*/
	private function writeReadVerifyKnownStructure($sKey){
		//SP_Log_Trace::log_method(microtime().'  ', 'CACHE');//debug
		$data = TestFixtures::getKnownStructure();
		$this->assertNotNull($data);
		$result = $this->instance->write($sKey, TestFixtures::getKnownStructure());
		$this->assertTrue($result->bResult, $result->sMessage);

		$result = $this->instance->read($sKey);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertTrue(TestFixtures::isKnownStructure($result->mData), self::ERROR_UNKNOWN_DATA_.$sKey.' 1: '.var_export($result->mData, TRUE));
	}

	/***************************************************************************/
	/**************** TEMPLATE FUNCTIONS****************************************/
	/***************************************************************************/
	
	public static  function setUpBeforeClass(){
		date_default_timezone_set('Australia/NSW');		// to avoid warnings.

		//
		/* (debug, only needed when test run alone)
		// todo Should the logger do this?!
		$sPath = sys_get_temp_dir().'/fc/logs';
	    if(!file_exists($sPath)){
			mkdir($sPath);
		}
		//SP_Log_Trace::path('CACHE', $sPath.'/cache.log');
		//*/	

		self::$iTestsRun = 0;
	}
	
	/** Child classes must call this!
	 */
	protected function setUp(){
	}
	
	protected function tearDown(){		
	}
		
	/** 
	* The SP_Cache_LayerInterfaceTest checks children have called all tests, (including new ones added later)
	* so you must include a call to parent::tearDownAfterClass() in your extension class.
	*/
	public static function tearDownAfterClass(){
		if(self::CHECK_NO_TESTS != self::$iTestsRun){
			// Either a child class didn't run every test, or someone added a test here without updating CHECK_NO_TESTS
			$trace = debug_backtrace();
			trigger_error('Child test class '.$trace[1]['class'].' only ran '.self::$iTestsRun.' of '.self::CHECK_NO_TESTS.' tests. Maybe new shared tests were added?');
		}
	}	

	/***************************************************************************/
	/**************** API TESTS ************************************************/
	/***************************************************************************/
	
	/**
	* Note testWrite == testRead as we're black boxing.
	*/
	public function testWrite(){
		self::$iTestsRun++;

		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA1);
		$this->assertTrue($result->bResult, $result->sMessage);

		$result = $this->instance->read(TestFixtures::KEY1);
		$this->assertEquals(TestFixtures::DATA1, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);
	}
	
	/**
	 * See SP_Cache_Layer CONCURRENCY RULES
	 * 
	 * The aim of concurrent testing is to prove that the cache performs normally when multiple processes are writing and reading.
	 * 
	 * So we have to try to read and write data from multiple processs (not threads).
	 * As it would be difficult to acheive proper atomic-set-and-test in this test environment, 
	 * we can only test for known typical values. ie. we cannot put a random value in the cache and 
	 * then try to get it out again, because another process might have changed it in the meantime.
	 * So we use a fixed set of fixtures, and only check the cache contains one of the valid values.
	 * This only tests for data corruption, not data validity or correctness.
	 * However the correctness tests should be covered by other tests. 
	 * 
	* @todo PHPUNIT PATH CONFIG
	* @param - provide the filename of the test you are calling from 
	* (ie. the Layer implementation test) as this is called again from the Layer Interface test.
	*/
	public function testWrite_Concurrent($testFile){
		//SP_Log_Trace::log_method(microtime().' ', 'CACHE');//debug
		self::$iTestsRun++;

		// start other processes which read and write to the cache
		$numUsers = self::CONCURRENT_STRESS_LEVEL;
		//$this->instance->write('iProcessesEnded', 0);
		for($i = 0; $i < $numUsers; $i++) {
			//$aRet = array();
			//$iRet = 0;
			//self::$iProcessesRunning++;
			//$this->instance->write('iProcessesRunning', self::$iProcessesRunning);//lol

			// note you have to redirect output somewhere to avoid php hanging on to the call, and making this synchronous (sequential!)
			//SP_Log_Trace::log_method(microtime().' parent1 ', 'CACHE');//debug

			exec( 'nohup ./phpunit.phar --filter testWrite_ConcurrentChildren '.$testFile.' > /dev/null 2> /dev/null < /dev/null &'); //, $aRet, $iRet);
			//SP_Log_Trace::log_method(microtime().' parent2 ', 'CACHE');//debug
			//if($iRet != 0) trigger_error("Error running conncurrent processes: exec returned '$s', exit=$iRet, ".var_export($aRet, true));
			//SP_Log_Trace::log_method("exec returned '$s', $iRet, ".var_export($aRet, true), 'CACHE');//debug

			// Now we also write, hopefully concurrently!
			$this->_testWrite_ConcurrentParent();
		}

		// We now need to wait until they've all finished writing, else later tests will fail.
		/*$iProcessesRunning = self::$iProcessesRunning;
		do {
			SP_Log_Trace::log_method("waiting cleanup pr=".$iProcessesRunning, 'CACHE');//debug
			usleep(100000);

			$r = $this->instance->read('iProcessesEnded');//atomic set and test?! LOCK?
			$iProcessesRunning  = self::$iProcessesRunning - $r->mData;

		} while($iProcessesRunning > 0);*/
		
		usleep(2000000);
		
	}

	private function _testWrite_ConcurrentParent(){
		//SP_Log_Trace::log_method(microtime().' parent1 ', 'CACHE');//debug
		//SP_Log_Trace::log_method("($sKey) pr=".self::$iProcessesRunning, 'CACHE');//debug

		// apply some jitter to the processes
		//usleep(mt_rand(0,1000));

		// we randomise the data to detect overwriting
		$this->writeReadVerifyKnownStructure(TestFixtures::KEY1);
		
		// TODO NEED SOME KIND OF IPC?
		//self::$iProcessesRunning--;
		//$r = $this->instance->read('iProcessesEnded');//atomic set and test?! LOCK?
		//$this->instance->write('iProcessesEnded', $r->mData+1);//lol

		//SP_Log_Trace::log_method(microtime().' parent2 ', 'CACHE');//debug
	}
	
	/** 
	* This is the function all the children run to try to mess up the parent.	
	* Note: it is not really one of the tests (doesn't need the test counter)
	* It will get run many times by the filter in the above function.
	* Of course it will also get run once by the main parent test, but that's not important.
	* 
	* You may override this to provide any setup (and then call parent::testWrite_ConcurrentChildren() of course)
	*/
	public function testWrite_ConcurrentChildren(){
		//SP_Log_Trace::log_method(microtime().' child1 ', 'CACHE');//debug
		//SP_Log_Trace::log_method("($sKey) pr=".self::$iProcessesRunning, 'CACHE');//debug

		// apply some jitter to the processes
		//usleep(mt_rand(0,1000));

		// we CANT randomise the data to detect overwriting BECAUSE WE DON'T HAVE ATOMIC SET AND TEST!
		//$data = TestFixtures::generateRandomStructure(10);
		//$this->writeReadVerify(TestFixtures::KEY1, $data);
		// Use known structure testing - it might change between write and read, but we just test for integrity.
		$this->writeReadVerifyKnownStructure(TestFixtures::KEY1);
		
		// TODO NEED SOME KIND OF IPC?
		//self::$iProcessesRunning--;

		//SP_Log_Trace::log_method(microtime().' child2 ', 'CACHE');//debug
	}

	/**
	* test binary data 
	*/
	public function testWrite_Binary(){
		self::$iTestsRun++;

		$data = TestFixtures::generateRandomStructure(10);
		//var_export($data);//debug

		$result = $this->instance->write(TestFixtures::KEY1, $data);
		$this->assertTrue($result->bResult, $result->sMessage);
		//$data[0]->k0  = '';//debug test the equals test is deep!
		//array_pop($data);//debug test the equals test is deep!
		$result = $this->instance->read(TestFixtures::KEY1);
		$this->assertEquals($data, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);
	}

	public function testWrite_Large(){
		self::$iTestsRun++;
				
		$this->writeReadVerify(TestFixtures::KEY1, TestFixtures::DATA1_LARGE);
	}
	
	public function testWrite_Huge(){
		self::$iTestsRun++;

		$this->writeReadVerify(TestFixtures::KEY1, TestFixtures::DATA1_HUGE);
	}
	
	/**
	* Test expiry 
	*/
	public function testWrite_Expiry(){
		self::$iTestsRun++;

		$iExpiry = 1;
		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA1, $iExpiry);
		$this->assertTrue($result->bResult, $result->sMessage);
		// wait for it to expire
		sleep($iExpiry);
		$result = $this->instance->read(TestFixtures::KEY1);
		$this->assertFalse($result->bResult, 'Cache should MISS expired data. Got '.var_export($result->mData, true));
	}

	/**
	* Test persistence - memory wont persist after cache destruct
	*/
	public function testWrite_Persists(){
		self::$iTestsRun++;

		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA1);
		$this->assertTrue($result->bResult, $result->sMessage);
		// destroy it
		unset($this->instance);
		$instance2 = $this->createInstance();

		$result = $instance2->read(TestFixtures::KEY1);
		$this->assertFalse($result->bResult);
		$this->assertEquals(null, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);
	}

	/** Test we can persist a complex structure.
	*/
	public function testWrite_Structure(){
		self::$iTestsRun++;

		// @todo does equals mean object identity or structure equivalence?!
		$this->writeReadVerify(TestFixtures::KEY1, TestFixtures::$DATA_STRUCT);
	}
	
	/**
	* Test data over-write ok? Silly but you never know...
	*/
	public function testWrite_Overwrite(){
		self::$iTestsRun++;

		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA1);
		$this->assertTrue($result->bResult, $result->sMessage);
		// overwrite with different data
		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA2);
		$this->assertTrue($result->bResult, $result->sMessage);

		$result = $this->instance->read(TestFixtures::KEY1);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertEquals(TestFixtures::DATA2, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);
	}

	/**
	* Note testWrite == testRead as we're black boxing.
	*/
	public function testRead(){
		self::$iTestsRun++;

		$this->writeReadVerify(TestFixtures::KEY1, TestFixtures::DATA1);
	}
		
	/**
	* 
	*/
	public function testReadErrorsNoKey(){		
		self::$iTestsRun++;

		// test errors
		$result = $this->instance->read(null);
		$this->assertFalse($result->bResult, 'Did not get error from null key passed to read()');
	}
	
}
