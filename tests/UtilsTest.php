<?php

require_once dirname(__FILE__).'/../config.php';
require_once ROOT_IDES_DATA.'/lib/Utils.php';

class UtilsTest extends PHPUnit_Framework_TestCase {

  public function testNewGuid() {
    $ga = array_map(function() { return newGuid(); },range(1,100));
    $gu = array_unique($ga);
    $this->assertTrue(count($ga)==count($gu));
    $ge=array_filter($ga,function($x) { return strlen($x)<5; });
    $this->assertTrue(count($ge)==0);
  }

}

