<?php

require_once dirname(__FILE__).'/../config.php';
require_once ROOT_IDES_DATA.'/lib/AesManager.php';

class AesManagerTest extends PHPUnit_Framework_TestCase {

    public function testNoDuplicates() {
	$gm=new AesManager();
	$x="something";
	$y=$gm->decrypt($gm->encrypt($x));
	$this->assertTrue($y==$x);
    }

}

