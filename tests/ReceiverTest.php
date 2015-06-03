<?php

require_once dirname(__FILE__).'/../config.php'; // copy the provided sample in repository/config-sample.php
require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
require_once ROOT_IDES_DATA.'/lib/Receiver.php';

$fn="/home/shadi/840FHqBTplZx26bXltl2LWoRab4RugMX.zip";
$rx=new Receiver("/home/shadi/tempIdes");
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
