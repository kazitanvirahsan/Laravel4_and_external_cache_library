<?php

/**
 * A cache layer writes and reads data under the Layer Cache Stack model.
 * e.g. memory layer, file layer, DB layer.
 * The app creates these layers, and registeres them into the stack before use.
 * After they're in the stack, you probably won't touch them again.
 * 
 * 
 * CONCURRENCY RULES:
 * The CacheLayer implementations must performs normally when multiple processes are writing and reading.
 * These processes could be on the same host, or different hosts for shared/distributed layers.
 * a) two or more processes should be allowed to (try to) write to the same key simultaneously, 
 * 	without corrupting the data
 * 	i.e. one of them will always come second and overwrite the other's data. 
 * b) two or more processes must be able to read while a write is happening without corrupted data.
 * 	i.e. the read process will either get the first value intact, or the second value intact.
 * c) two or more processes should be able to read from the same key simultaneously
 *  i.e. it would be preferable that the access was completely parallel, not locked, for speed reasons.
 * 
 * Concurrency management will be acheived via semaphore, locking, queueing, or buffering etc.
 * 
*/
interface SP_Cache_Layer {

	/**
	* Reads a value from this cache layer, if it exists and has not expired.
	*
	*  Result 		HIT 		MISS 		ERROR
	*  ------------------------------------------
	*  ->bResult	true		false		false
	*  ->sMessage	null		null		<error message>
	*  ->mData		<data>		null		null
	*
	* @param string $sKey cache key to fetch data for
	* @return Result 
	* @return bool bResult - success if the data was returned (cache hit)
	* @return string sMessage - error message, null for hit or miss
	* @return mixed mData - the cache data hit, null for miss or error
	*/
	public function read($sKey);

	/**
	* Writes data into this cache layer, replacing anything for that key.
	* @param string $sKey cache key to index data under
	* @param mixed mData - the data to cache
	* @param integer $iExpiry Optional; seconds until datum will become stale and get purged. Defaults to the layer's general configured setting.
	* @return Result 
	* @return bool bResult - success if the data was written
	* @return string sMessage - error message, or null
	*/
	public function write($sKey, $mData, $iExpiresIn=null);
	
	/**
	* Sets the expiry time for all subsequent data writes.
	* See concrete implementations for notes on what the default of this default is.
	* @param integer $iSeconds - the default lifespan of new data written into the cache.
	*/
	public function setExpiry($iSeconds);
}