<?php

require_once( $_SERVER['DOCUMENT_ROOT'] .'/layercache/libs/scipilot-layercache/SP_Cache_Service_Provider_Slow_Model.php');
require_once( $_SERVER['DOCUMENT_ROOT'] .'/layercache/libs/scipilot-layercache/SP_Cache_Service_Provider_Fast_Model.php');

/*
	|--------------------------------------------------------------------------
	| MultiLayerCacheController Controller class
	|--------------------------------------------------------------------------
	| This controller is using multi-layer cache facility through  
	| accessing SP_Cache_Service_Provider_Slow_Model and SP_Cache_Service_Provider_Fast_Model
	|
*/

class MultiLayerCacheController extends BaseController {
	
    /*
    * This method uses the multilayer slow model to get the data from cache
    * As it does not take any advantage from layer cache, the response time
    * is apparantly slow 
    */
	public function getSlowRespWithoutCache()
	{
	    

        // create an instance of SP_Cache_Service_Provider_Slow_Model
        $ScipilotSlowObj = new SP_Cache_Service_Provider_Slow_Model();
        // get data wihtout using multilayer cache
        $data = $ScipilotSlowObj->getComputationallyExpensiveData();


        // call the view belong to this controller method and pass this data into it. 
		return View::make('multilayercache/slowResponse')->with('data', $data);
	}

    
    /*
     * This method is using multi-layer cache.
     * As it takes the advantage from layer cache, the response time
     * is apparantly fast 
    */
	public function getFastResWithCache()
	{
		// create an associative key for data
        $key = 'mykey';
        // create a dymmy data
        $value  = date('Y/m/d H:i:s').' LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ LOREM IPSUM FOO BAR BAZ OMGFOFLBBQ';
        

		// create an instance of SP_Cache_Service_Provider_Fast_Model
        $ScipilotObj = new SP_Cache_Service_Provider_Fast_Model();
        // write data to multi layer cache
        $ScipilotObj->write($key , $value);
        // read data from cache by using the relevant key
        $data = $ScipilotObj->read($key);
        
        // call the view belong to this controller method and pass this data into it.   
     	return View::make('multilayercache/fastResponse')->with('data', $data);
	}

}