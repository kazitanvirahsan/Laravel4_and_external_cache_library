<?php

if(!defined('TEST_PATH')) define ('TEST_PATH',dirname(__FILE__).'/../../../..');// set this per file!
if(!defined('SP_CLASSPATH')) define ('SP_CLASSPATH',TEST_PATH.'/../src');// leave this.

require_once TEST_PATH.'/fixtures/GeneralFixtures.class.php';
require_once TEST_PATH.'/tests/classes/Cache/Layer/LayerInterface.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/FileCache.class.php';

class SP_Cache_Layer_FileCacheTest extends SP_Cache_LayerInterfaceTest {

	const CLIENT_TAG = 'unittest';
	const DEPLOYMENT = 'test';

	/* Test Class-specific methods */

	function __construct(){
	}
	
	/*
	* Used by parent class to re/create daughter test specimen.
	* @return SP_Cache_Layer (SP_Cache_Layer_FileCache)
	*/
	protected function createInstance(){
		return new SP_Cache_Layer_FileCache();
	}
	

	
	/***************************************************************************/
	/**************** TEMPLATE FUNCTIONS****************************************/
	/***************************************************************************/
	
	public static function setUpBeforeClass(){
		parent::setUpBeforeClass();
	
	}
	
	/**
	 */
	protected function setUp(){
		parent::setup();

		$this->instance = $this->createInstance();
	}
	
	protected function tearDown(){
		parent::tearDown();		
	}
	
	/** 
	* The SP_Cache_LayerInterfaceTest checks children have called all tests, (including new ones added later)
	* so you must include a call to parent::tearDownAfterClass()
	*/
	public static function tearDownAfterClass(){
		parent::tearDownAfterClass();
	}	
	
	/***************************************************************************/
	/**************** API TESTS ************************************************/
	/***************************************************************************/
	
	/**
	*/
	public function testWrite(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite();
	}
	
	/**
	* Test concurrency
	*/
	public function testWrite_Concurrent(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Concurrent(__FILE__);
	}
	
	/**
	* Test concurrency - this is the concurrent child process
	*/
	public function testWrite_ConcurrentChildren(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_ConcurrentChildren();
	}
	
	/**
	* Test storing binary data 
	*/
	public function testWrite_Binary(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Binary();
	}

	public function testWrite_Large(){				
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Large();
	}
	
	public function testWrite_Huge(){				
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Huge();
	}
	
	/**
	* Test expiry 
	*/
	public function testWrite_Expiry(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Expiry();				
	}

	/**
	* Test persistence - file should persist after cache destruct
	*/
	public function testWrite_Persists(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Persists();
	}

	/** Test we can persist a complex structure.
	*/
	public function testWrite_Structure(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Structure();
	}
	
	/**
	* Test data over-write ok? Silly but you never know...
	*/
	public function testWrite_Overwrite(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testWrite_Overwrite();				
	}

	/**
	* Specific to file driver: test configuration separation
	*/
	public function testWrite_SeparateConfig(){

		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
				
		$result = $this->instance->write(TestFixtures::KEY1, TestFixtures::DATA1);
		$this->assertTrue($result->bResult, $result->sMessage);
		
		// Write different data to same key in different CLIENT
		$instance2 = new SP_Cache_Layer_FileCache();
		$instance2->configure(self::CLIENT_TAG.'2', self::DEPLOYMENT);
		$result = $instance2->write(TestFixtures::KEY1, TestFixtures::DATA2);
		$this->assertTrue($result->bResult, $result->sMessage);
		
		// Write different data to same key in different DEPLOYMENT
		$instance3 = new SP_Cache_Layer_FileCache();
		$instance3->configure(self::CLIENT_TAG, self::DEPLOYMENT.'3');
		$result = $instance3->write(TestFixtures::KEY1, TestFixtures::DATA3);
		$this->assertTrue($result->bResult, $result->sMessage);

		// Check there's no collisions and they're all correct
		$result = $this->instance->read(TestFixtures::KEY1);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertEquals(TestFixtures::DATA1, $result->mData,  self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);

		$result = $instance2->read(TestFixtures::KEY1);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertEquals(TestFixtures::DATA2, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);

		$result = $instance3->read(TestFixtures::KEY1);
		$this->assertTrue($result->bResult, $result->sMessage);
		$this->assertEquals(TestFixtures::DATA3, $result->mData, self::ERROR_DIFFERENT_DATA_.TestFixtures::KEY1);
	}

	/**
	*/	
	public function testRead(){
		$this->instance->configure(self::CLIENT_TAG, self::DEPLOYMENT);
		parent::testRead();
	}
		
	/**
	*/
	public function testReadErrorsNoKey(){		
		parent::testReadErrorsNoKey();
	}
	
}
