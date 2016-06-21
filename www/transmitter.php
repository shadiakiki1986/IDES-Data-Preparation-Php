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
  function warning_handler($errno, $errstr) { 
    print sprintf("<div style='color:red'>%s: %s</div>",$errno,$errstr);
  }
  set_error_handler("warning_handler", E_WARNING);
}

if(!defined("ROOT_IDES_DATA")) define("ROOT_IDES_DATA",__DIR__."/..");
require_once ROOT_IDES_DATA.'/bootstrap.php';
require_once ROOT_IDES_DATA.'/src/getFatcaData.php';
use FatcaIdesPhp\Transmitter;
use Monolog\Logger;
$LOG_LEVEL=Logger::WARNING;

// 
if(isset($argc)) {
  $_GET=array();
  $options = getopt("hdf::st:e:u:p:", array("help","debug","format::","shuffleSkip","taxYear:","emailTo:","idesUsername:","idesPassword:"));
  foreach($options as $k=>$v) {
    switch($k) {
      case "h":
      case "help":
        echo "Usage: \n";
        echo "       php ".basename(__FILE__)." --help\n";
        echo "       php ".basename(__FILE__)." --taxYear=2014 [--shuffleSkip] [--debug] [--format=html*|xml|zip]\n";
        echo "       php ".basename(__FILE__)." --taxYear=2014 [--shuffleSkip] [--debug] [--emailTo=s.akiki@ffaprivatebank.com --idesUsername=username --idesPassword=password]\n";
        exit;
        break;
      case "d":
      case "debug":
        $LOG_LEVEL=Logger::DEBUG;
        break;
      case "f":
      case "format":
        $_GET["format"]=$v;
        break;
      case "s":
      case "shuffleSkip":
        $_GET["shuffle"]="false";
        break;
      case "t":
      case "taxYear":
        $_GET["taxYear"]=$v;
        break;
      case "e":
      case "emailTo":
        $_GET["emailTo"]=$v;
        break;
      case "u":
      case "idesUsername":
        $_GET["idesUsername"]=$v;
        break;
      case "p":
      case "idesPassword":
        $_GET["idesPassword"]=$v;
        break;
    }
  }
  if(!array_key_exists("taxYear",$_GET)) die("Please pass --taxYear=2014 for example\n");
}

// config preprocess
$config=yaml_parse_file(ROOT_IDES_DATA.'/etc/config.yml');

// check that email configuration available
if(array_key_exists("emailTo",$_GET) && !array_key_exists("swiftmailer",$config)) {
  throw new Exception("Emailing requested but not configured on server in etc/config.yml. Aborting");
}

// if path strings do not start with "/", then prefix with ROOT_IDES_DATA/
$keysToPrefix=array("FatcaCrt","FatcaKeyPrivate","FatcaKeyPublic","downloadFolder","ZipBackupFolder");
$keysToPrefix=array_intersect(array_keys($config),$keysToPrefix);
$keysToPrefix=array_filter($keysToPrefix,function($x) use($config) {
  return !preg_match("/^\//",$config[$x]);
});
foreach($keysToPrefix as $ktp) {
  $config[$ktp]=ROOT_IDES_DATA."/".$config[$ktp];
}

// check backup folder existance
if(array_key_exists("ZipBackupFolder",$config)) {
  if(!file_exists($config["ZipBackupFolder"]) || !is_dir($config["ZipBackupFolder"])) {
    throw new Exception("Defined ZipBackupFolder '".$config["ZipBackupFolder"]."' does not exist or is not a folder");
  }
}

// argument checking
if(!array_key_exists("format",$_GET)) $_GET['format']="html"; # default
if(!in_array($_GET['format'],array("html","xml","zip","metadata","upload","emailAndUpload","email"))) throw new Exception("Unsupported format. Please use one of: html, xml, zip, metadata");

if(in_array($_GET["format"],array("email","emailAndUpload")) && !array_key_exists("emailTo",$_GET)) $_GET["emailTo"]="s.akiki@ffaprivatebank.com"; // default

if(!array_key_exists("shuffle",$_GET)) {
  $_GET['shuffle']=true; # default
} else {
  if(!in_array($_GET['shuffle'],array("true","false"))) throw new Exception("Unsupported shuffle. Please use true or false");
  $_GET['shuffle']=($_GET['shuffle']=="true");
}

if(!array_key_exists("CorrDocRefId",$_GET)) $_GET['CorrDocRefId']=false;

if(!array_key_exists("taxYear",$_GET)) $_GET['taxYear']=2014; else $_GET['taxYear']=(int)$_GET['taxYear'];

if(in_array($_GET["format"],array("upload","emailAndUpload")) &&
  !(array_key_exists("idesUsername",$_GET) && array_key_exists("idesPassword",$_GET))) throw new Exception("Missing upload username/password");

if($_GET["format"]=="upload" && !$_GET["shuffle"]) throw new Exception("Not allowed to upload without emailing for live data");

// retrieval from mf db table
$fdi=getFatcaData($_GET['shuffle'],$_GET['CorrDocRefId'],$_GET['taxYear'],$config);
$tmtr=Transmitter::shortcut(
  $fdi,$_GET['format'],
  !array_key_exists("emailTo",$_GET)?null:$_GET['emailTo'],
  $config,$LOG_LEVEL);

switch($_GET['format']) {
  case "html":
    echo($tmtr->fdi->toHtml());
    break;
  case "xml":
    Header('Content-type: text/xml');
    echo($tmtr->dataXmlSigned);
    break;
  case "zip":
    $tmtr->getZip();
    break;
  case "metadata":
    Header('Content-type: text/xml');
    echo($tmtr->getMetadata());
    break;
  case "email":
  case "upload":
  case "emailAndUpload":
    if(in_array($_GET["format"],array("email","emailAndUpload"))) {
      $tmtr->toEmail(
        $_GET["emailTo"],
        "s.akiki@ffaprivatebank.com","Shadi Akiki","s.akiki@ffaprivatebank.com",
        $config["swiftmailer"]);
    }

    if(in_array($_GET["format"],array("upload","emailAndUpload"))) {
      $upload = array("username"=>$_GET["idesUsername"],"password"=>$_GET["idesPassword"]);
      $csm = null;
      if($_GET["format"]=="emailAndUpload") $csm = $config["swiftmailer"];
      $tmtr->toUpload($upload,$csm);
    }

    break;
  default: throw new Exception("Unsupported format ".$_GET['format']);
}
