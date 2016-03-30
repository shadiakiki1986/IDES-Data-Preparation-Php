<?php

require_once dirname(__FILE__).'/../config.php'; // copy the provided sample in repository/config-sample.php
require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
require_once ROOT_IDES_DATA.'/lib/Receiver.php';

class ReceiverTest extends PHPUnit_Framework_TestCase {

  public function testDir() {
    // http://stackoverflow.com/a/21473475/4126114
    $user = posix_getpwuid(posix_getuid());
    $rx1=new Receiver($user['dir']); // should pass since the user home directory is existant
    try {
      $rx2=new Receiver("/random/folder/inexistant/"); // should not pass since the directory is inexistant
      $this->assertTrue(false); // shouldnt get here
    } catch(Exception $e) {
      $this->assertTrue(true); // should get here
    }
  }

  public function testWorkflow() {
    $fn="/home/shadi/840FHqBTplZx26bXltl2LWoRab4RugMX.zip";
    if(!file_exists($fn)) {
      $this->markTestSkipped("Zip file '%s' not available for testing",$fn);
      return;
    }
    $rx=new Receiver();
    $rx->fromZip($fn);
    $rx->decryptAesKey();
    $rx->fromEncrypted();
    $rx->fromCompressed();

    echo "From: ".$rx->from."\n";
    echo "To: ".$rx->to."\n";
    //echo "Key: ".$rx->aeskey."\n";
    //echo "Payload encrypted: ".$rx->dataEncrypted."\n";
    //echo "Payload decrypted: ".$rx->dataCompressed."\n";
    echo "Payload uncompressed: ".$rx->dataXmlSigned."\n";
  }
}
