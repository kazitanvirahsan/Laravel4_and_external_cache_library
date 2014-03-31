<?php

if(!defined('TEST_PATH')) define ('TEST_PATH',dirname(__FILE__).'/../../../..');// set this per file!
if(!defined('SP_CLASSPATH')) define ('SP_CLASSPATH',TEST_PATH.'/../src');// leave this.

require_once TEST_PATH.'/fixtures/GeneralFixtures.class.php';
require_once TEST_PATH.'/tests/classes/Cache/Layer/LayerInterface.class.php';
require_once SP_CLASSPATH.'/Cache/Layer/MemoryCache.class.php';

class SP_Cache_Layer_MemoryCacheTest extends SP_Cache_LayerInterfaceTest {
	
	/* Test Class-specific methods */

	function __construct(){
	}
	
	/*
	* Used by parent class to re/create daughter test specimen.
	* @return SP_Cache_Layer (SP_Cache_Layer_MemoryCache)
	*/
	protected function createInstance(){
		return new SP_Cache_Layer_MemoryCache();

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
		parent::testWrite();
	}
	
	/**
	* Test concurrency
	*/
	public function testWrite_Concurrent(){
		parent::testWrite_Concurrent(__FILE__);
	}
	
	/**
	* @todo test binary data 
	*/
	public function testWrite_Binary(){
		parent::testWrite_Binary();
	}

	public function testWrite_Large(){
		parent::testWrite_Large();

	}
	
	public function testWrite_Huge(){
		parent::testWrite_Huge();
	}
	
	public function testWrite_Expiry(){
		parent::testWrite_Expiry();
	}

	/**
	* Test persistence - memory wont persist after cache destruct
	*/
	public function testWrite_Persists(){
		parent::testWrite_Persists();
	}

	/** Test we can persist a complex structure.
	*/
	public function testWrite_Structure(){
		parent::testWrite_Structure();
	}
	
	/**
	* Test data over-write ok? Silly but you never know...
	*/
	public function testWrite_Overwrite(){
		parent::testWrite_Overwrite();
	}

	/**
	*/	
	public function testRead(){
		parent::testRead();
	}
		
	/**
	* 
	*/
	public function testReadErrorsNoKey(){		
		parent::testReadErrorsNoKey();
	}
	
}
