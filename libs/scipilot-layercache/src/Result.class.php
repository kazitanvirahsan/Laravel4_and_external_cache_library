<?php 
/**
 * Result structure helper class - for returning responses.
 *
 * @package Scipilot
 */
class Result {
  /**
   * End-user consumable. Error message, usually '' on success.
   * @var string
   */
  var $sMessage = '';

  /**
   * Success flag, if false - see sMessage
   * @var Boolean
   */
  var $bResult = true;

  /**
   * Optional; data structure dependent on API call.
   * @var mixed
   */
  var $mData = null;

  /**
   * Developer-consumable debugging message, usually '' on success.
   * @var string
   */
  var $sDebug = '';

  /**
   * Creates a result object, suitable for returning in responses.
   * See internal variable descriptions for parameter usage.
   * Set sDebug manually if you want it.
   */
  function Result($bResult=true, $sMessage='', $mData=null){
    $this->bResult = $bResult;
    $this->sMessage = $sMessage;
    $this->mData = $mData;
  }
  
  /**
   * Logically merges the supplied result into this one (AND and concat).
   * This is handy during a chain of actions which all return results, 
   * and you want to keep the "running" result.
   * 
   * e.g.
   * <code>
   *  $res = doSomethingUnimportant();
   *  $res->AndWith(doSomethingCritical());
   *  if($res->bResult()){
   * 	  $res->AndWith(doSomethingElse());
   * }
   * </code>
   * 
   * @todo could this be a Result method/operator? e.g. $res && $res2 - can PHP do that?! C++ can :)
   * @param Result $res2
   */
  public function AndWith($res2){
      $this->bResult = $this->bResult && $res2->bResult;
      $this->sDebug .= $res2->sDebug;
      $this->sMessage .= $res2->sMessage;
      $this->mData .= $res2->mData;
  }

  /**
   * Utility function to update two ore more fields at once.
   */
  public function Update($bResult, $sMessage, $mData=null){
  	$this->bResult = $bResult;
  	$this->sMessage = $sMessage;
  	if($mData != null) $this->mData = $mData;
  }
}
