<?php

/*
 Returns a US client data in different formats from Marketflow.
 The HTML format allows to see the data easily.
 The XML format is an intermediate step showing what data will go into the IDES zip file.
 The ZIP format is what is to be submitted to the IDES gateway.

 Note: This requires the xsd from IDES to be downloaded to $FatcaXsdFolder
       URL: http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966
            http://www.irs.gov/file_source/pub/fatca/FATCAXMLSchemav1.zip
 Usage:
 	CLI
 		php transmitter.php --help

 	Ajax with jquery
		$.ajax({
		    url:"http://{{server}}/IDES-Data-Preparation-Php/transmitter.php?format=html&shuffle=true",
		    type: 'GET',
		    success: function (data) {
		        console.log(data);
		    },
		    error: function (jqXHR, ts, et) {
		        console.log("error", ts, et);
		    }
		 });
*/

error_reporting(E_ALL);

if(!isset($argc)) {
  // http://stackoverflow.com/a/11206244/4126114
  set_error_handler("warning_handler", E_WARNING);
  function warning_handler($errno, $errstr) { 
    print sprintf("<div style='color:red'>%s: %s</div>",$errno,$errstr);
  }
}

require_once dirname(__FILE__).'/../../../config.php'; // copy the provided sample in repository/config-sample.php

require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
require_once ROOT_IDES_DATA.'/lib/Transmitter.php';
require_once ROOT_IDES_DATA.'/lib/array2shuffledLetters.php';
require_once ROOT_IDES_DATA.'/lib/mail_attachment.php';

// if installation instructions were not followed by copying the file getFatcaData-SAMPLE to getFatcaData, then just use the sample file
if(!file_exists(ROOT_IDES_DATA.'/lib/getFatcaData.php')) {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData-SAMPLE.php'; // use sample file
} else {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData.php';
}

// check existence
if(defined(ZipBackupFolder)) if(!file_exists(ZipBackupFolder) || !is_dir(ZipBackupFolder)) throw new Exception("Defined ZipBackupFolder does not exist or is not a folder");

// 
if(isset($argc)) {
  $_GET=array();
  $options = getopt("hf::sy:e:", array("help","format::","shuffleSkip","year:","emailTo:"));
  foreach($options as $k=>$v) {
    switch($k) {
      case "h":
      case "help":
        echo "Usage: php ".basename(__FILE__)." --year=2014 [--format=html*|xml|zip] [--shuffleSkip] [--emailTo=s.akiki@ffaprivatebank.com]\n";
        echo "       php ".basename(__FILE__)." --help\n";
        exit;
        break;
      case "f":
      case "format":
        $_GET["format"]=$v;
        break;
      case "s":
      case "shuffleSkip":
        $_GET["shuffle"]="false";
        break;
      case "y":
      case "year":
        $_GET["taxYear"]=$v;
        break;
      case "e":
      case "emailTo":
        $_GET["emailTo"]=$v;
        break;
    }
  }
  if(!array_key_exists("taxYear",$_GET)) die("Please pass --year=2014 for example\n");
}

if(!array_key_exists("format",$_GET)) $_GET['format']="html"; # default
if(!in_array($_GET['format'],array("html","xml","zip","metadata"))) throw new Exception("Unsupported format. Please use one of: html, xml, zip, metadata");

if(!array_key_exists("shuffle",$_GET)) $_GET['shuffle']="true"; # default
if(!in_array($_GET['shuffle'],array("true","false"))) throw new Exception("Unsupported shuffle. Please use true or false");
$_GET['shuffle']=($_GET['shuffle']=="true");

if(!array_key_exists("CorrDocRefId",$_GET)) $_GET['CorrDocRefId']=false;

if(!array_key_exists("taxYear",$_GET)) $_GET['taxYear']=2014; else $_GET['taxYear']=(int)$_GET['taxYear'];

// retrieval from mf db table
$di=getFatcaData($_GET['taxYear']);
if(count($di)==0) throw new Exception("No data");
if($_GET['shuffle']) $di=array2shuffledLetters($di,array("ResidenceCountry","posCur","cur")); // shuffle all fields except these... ,"Compte"

$fca=new Transmitter($di,$_GET['shuffle'],$_GET['CorrDocRefId'],$_GET['taxYear']);
$fca->toXml(); # convert to xml 

if(!$fca->validateXml("payload")) {# validate
  print 'Payload xml did not pass its xsd validation';
  libxml_display_errors();
  $exitCond=in_array($_GET['format'],array("xml","zip"));
  $exitCond=$exitCond||array_key_exists("emailTo",$_GET);
  if($exitCond) exit;
}

if(!$fca->validateXml("metadata")) {# validate
    print 'Metadata xml did not pass its xsd validation';
    libxml_display_errors();
    exit;
}

$diXml2=$fca->toXmlSigned();
if(!$fca->verifyXmlSigned()) die("Verification of signature failed");

$fca->toCompressed();
$fca->toEncrypted();
$fca->encryptAesKeyFile();
//	if(!$fca->verifyAesKeyFileEncrypted()) die("Verification of aes key encryption failed");
$fca->toZip(true);
if(defined(ZipBackupFolder)) copy($fca->tf4, ZipBackupFolder."/includeUnencrypted_".$fca->file_name);
$fca->toZip(false);
if(defined(ZipBackupFolder)) copy($fca->tf4,ZipBackupFolder."/submitted_".$fca->file_name);

if(!array_key_exists("emailTo",$_GET)) {
  switch($_GET['format']) {
    case "html":
      echo($fca->toHtml());
      break;
    case "xml":
      Header('Content-type: text/xml');
      echo($diXml2);
      break;
    case "zip":
      $fca->getZip();
      break;
    case "metadata":
      Header('Content-type: text/xml');
      echo($fca->getMetadata());
      break;
    default: throw new Exception("Unsupported format ".$_GET['format']);
  }
} else {

  // http://stackoverflow.com/a/32772796/4126114
  function myTempnam($suf) {
    $fnH = tempnam("/tmp","");
    rename($fnH, $fnH .= '.'.$suf);
    return $fnH;
  }

  // save to files
  $fnH = myTempnam('html');
  file_put_contents($fnH,$fca->toHtml());
  $fnX = myTempnam('xml');
  file_put_contents($fnX,$diXml2);
  $fnM = myTempnam('xml');
  file_put_contents($fnM,$fca->getMetadata());
  $fnZ = myTempnam('zip');
  copy($fca->tf4,$fnZ);

  // zip to avoid getting blocked on server
  $z = new ZipArchive();
  $fnZ2 = myTempnam('zip');
  $z->open($fnZ2, ZIPARCHIVE::CREATE);
  $z->addEmptyDir("IDES data");
  $z->addFile($fnH, "IDES data/data.html");
  $z->addFile($fnX, "IDES data/data.xml");
  $z->addFile($fnM, "IDES data/metadata.xml");
  $z->addFile($fnZ, "IDES data/data.zip");
  $z->close(); 

  // send email
  $subj=sprintf("IDES data: %s",date("Y-m-d H:i:s"));

  if(!mail_attachment(
    array($fnZ2),
    $_GET["emailTo"],
    "s.akiki@ffaprivatebank.com", // from email
    "Shadi Akiki", // from name
    "s.akiki@ffaprivatebank.com", // reply to
    $subj, 
    "Attached: html, xml, metadata, zip formats"
  )) throw new Exception("Failed to send email");

  echo "Done emailing\n";
}
