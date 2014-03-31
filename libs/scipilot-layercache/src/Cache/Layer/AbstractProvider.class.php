<?php
if(!defined('SP_CLASSPATH')) define('SP_CLASSPATH', dirname(__FILE__).'/..');

/**
* Provides an abstract base class for Provider implementations.
* These are the classes which generate data to be stored in the layer cache.
*/
abstract class SP_Cache_Layer_AbstractProvider {
    abstract protected function read($key);
    abstract protected function write($key , $value);
}