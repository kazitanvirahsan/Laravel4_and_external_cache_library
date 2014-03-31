<?php
/**
 * Provides simple trace logging for debugging and profiling.
 * It uses a static mechanism to avoid having to pass a trace object around the inspected code.
 * It provides an optional tag mechanism to allow multiple paths to be traced simultaneously, and separately.
 * To utilise this call path() first with your chosen unique tag, then call log() each time with the same tag.
 * Otherwise the log will appear in logs/trace.log
 * 
 * @package Scipilot
 * @subpackage Log
 */
class SP_Log_Trace {
  /**
   * Tag-Indexed array of paths to where we're tracing 
   */
  static $aPaths = array();
  
  /**
   * Clears a particular log file.
   * @param string  $sTag  (Optional) Your debug thread tag, e.g. "SHOPPINGCART" OR "FOO"
   */
  public static function clear($sTag=''){
    self::checkPath($sTag);
    
    unlink(self::$aPaths[$sTag]);
  }
  
  /**
   * @param string  $sMessage Your log entry
   * @param string  $sTag  (Optional) Your debug thread tag, e.g. "SHOPPINGCART" OR "FOO"
   */
  public static function log($sMessage, $sTag=''){
    self::checkPath($sTag);
    
    $sMessage = date('Y/m/d h:i:s ').$sMessage."\n";
    
    if(!error_log($sMessage, 3, self::$aPaths[$sTag])){
      trigger_error("Can't log to file ".self::$aPaths[$sTag], E_USER_WARNING);
    }
  }
  
  /**
   * Like log() but automatically prepends the class+method too.
   */
  public static function log_method($sMessage, $sTag=''){
    $class = '?';
    $function = '?';
    $bt = debug_backtrace();
    
    // get class, function called by caller of caller of caller
    if(!empty($bt[1])){
      $class = empty($bt[1]['class']) ? $bt[1]['file'] : $bt[1]['class'];
      $function = $bt[1]['function'];
    }
      
    $sMessage = $class."::".$function." ".$sMessage;
    
    self::log($sMessage, $sTag);
  }
  
  /**
   * Sets the logging path for a particular trace tag.
   * @param string  $sTag  Your debug thread tag, e.g. "SHOPPINGCART" OR "FOO"
   * @param string  $sPath  Path to your log file (make sure it's writable by the web server user).
   */
  public static function path($sTag, $sPath) {
    self::$aPaths[$sTag] = $sPath;
  }
  
  /**
   * Checks the path for this tag exists, and defaults if not (ie. incorrect call sequence).
   */
  private function checkPath($sTag){

    if(empty(self::$aPaths[$sTag])){
      // Someone is using a tag which hasn't been setup!
      
      // first check the default is setup. Can't call functions in the static definition.
      if(empty(self::$aPaths[''])){
         self::$aPaths[''] = dirname(__FILE__).'/../../logs/trace.log';
      }
      
      // Reset to default log path
      self::$aPaths[$sTag] = self::$aPaths[''];
    }
  }
}
