<?php
/**
 * Wraps the various compression libraries, to reduce dependence on zlib/zip etc.
 * Falls back to 0% compression if no compression libraries are available!
 * 
 * @todo add more handlers... in order, lz, zip etc.
 * @todo add preference?
 * @todo add library specific switches/parameters.
 * @todo add singleton mode wrapped by the below static interface for no-options (ie. all defaults)
 *  
 * @author pip.jones
 */
class SP_Wrap_Compression {

	/**
	 * Compresses the data with the best installed compression library, or returns the data if none are installed.
	 * @param mixed $mData
	 */
	public static function compress($mData){
		
		if(function_exists('gzcompress')){
			$mData = gzcompress($mData);
		}
		return $mData;
	}
	
	/**
	 * 
	 * Decompresses the data with the same library that compress would use.
	 * NB: this could break if you change the library support i.e. by installing a higher-preference library, and then decompress existing data.
	 * @param mixed $mData
	 */  
	public static function uncompress($mData){
    
    if(function_exists('gzuncompress')){
      $mData = gzuncompress($mData);
    }
    return $mData;
  }
}