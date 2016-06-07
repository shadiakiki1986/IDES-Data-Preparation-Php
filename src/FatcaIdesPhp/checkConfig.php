<?php

namespace FatcaIdesPhp;

require_once dirname(__FILE__).'/../config.php';

function checkConfig() {
  foreach(array("FatcaKeyPrivate","FatcaXsd","MetadataXsd","ffaid","ffaidReceiver","FatcaCrt") as $x) {
    if(!defined($x)) throw new Exception(sprintf("Missing variable in config: '%s'",$x));
  }

  foreach(array(FatcaXsd,MetadataXsd,FatcaCrt) as $x) {
    if(!file_exists($x)) throw new Exception(sprintf("Missing file defined in config: '%s'",$x));
  }

}
