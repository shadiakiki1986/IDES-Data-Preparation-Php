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

if(!defined("ROOT_IDES_DATA")) define("ROOT_IDES_DATA",__DIR__."/../..");
require_once ROOT_IDES_DATA.'/bootstrap.php';
require_once ROOT_IDES_DATA.'/src/getFatcaData.php';
use FatcaIdesPhp\Transmitter;

// check existence
$config=yaml_parse_file(ROOT_IDES_DATA.'/config.yml');
if(property_exists($config,"ZipBackupFolder")) {
  if(!file_exists($config["ZipBackupFolder"]) || !is_dir($config["ZipBackupFolder"])) {
    throw new Exception("Defined ZipBackupFolder does not exist or is not a folder");
  }
}

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
$tmtr=Transmitter::shortcut($di,$_GET['shuffle'],$_GET['CorrDocRefId'],$_GET['taxYear'],$_GET['format'],$config);
$fca=$tmtr["fca"];
$diXml2=$tmtr["diXml2"];

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
  Transmitter::toEmail($fca,$_GET["emailTo"],"s.akiki@ffaprivatebank.com","Shadi Akiki","s.akiki@ffaprivatebank.com");

  echo "Done emailing\n";
}
