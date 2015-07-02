<?php

require_once dirname(__FILE__).'/../config.php';
require_once ROOT_IDES_DATA.'/lib/newGuid.php';

class GuidManager {

var $guidPrepd;
var $guidCount;

function __construct($N=100) {
	// prepare guids to use
	$this->guidPrepd=array();
	for($i=0;$i<$N;$i++) array_push($this->guidPrepd,newGuid());
	$this->guidCount=0;
}

function get() {
	if($this->guidCount>=count($this->guidPrepd)) throw new Exception("Ran out of GUID");

	$o=$this->guidPrepd[$this->guidCount];
	$this->guidCount++;
	return $o;
}
} // end class
