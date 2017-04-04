<?php

/*
 Submits a XML file serving as a correction

 Usage:
 	CLI
 		php correction.php --help
 		php correction.php --file=path/to/file --format=xml
 		php correction.php --file=path/to/file --format=email --emailTo=s.akiki@ffaprivatebank.com
 		php correction.php --file=path/to/file --emailTo=s.akiki@ffaprivatebank.com --idesUsername=username --idesPassword=password --format=emailAndUpload

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
require_once ROOT_IDES_DATA.'/src/getConfigFn.php';
use FatcaIdesPhp\Factory;
use FatcaIdesPhp\FatcaDataXml;
use Monolog\Logger;
$LOG_LEVEL=Logger::WARNING;

// 
if(isset($argc)) {
  $_GET=array();
  $options = getopt("hf::l:e:u:p:", array("help","format::","file:","emailTo:","idesUsername:","idesPassword:"));
  foreach($options as $k=>$v) {
    switch($k) {
      case "h":
      case "help":
        echo "Usage: \n";
        echo "       php ".basename(__FILE__)." --help\n";
        echo "       php ".basename(__FILE__)." --file=path/to/file [--format=html*|xml|zip|metadata|email|upload|emailAndUpload]\n";
        echo "       php ".basename(__FILE__)." --file=path/to/file [--emailTo=s.akiki@ffaprivatebank.com --idesUsername=username --idesPassword=password]\n";
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
      case "l":
      case "file":
        $_GET["file"]=$v;
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
  if(!array_key_exists("file",$_GET)) die("Please pass --file=path/to/file for example\n");
}

// config preprocess
$configFn = getConfigFn();
$config=yaml_parse_file($configFn);
$cm = new \FatcaIdesPhp\ConfigManager($config);
$cm->prefixIfNeeded(ROOT_IDES_DATA);
$cm->checkExist();
if(count($cm->msgs)>0) throw new \Exception(implode("\n",$cm->msgs));
$config = $cm->config;

// check that email configuration available
if(!array_key_exists("format",$_GET)) throw new Exception("Missing format");
if(in_array($_GET["format"],array("email","emailAndUpload")) && !array_key_exists("swiftmailer",$config)) {
  throw new Exception("Emailing requested but not configured on server in etc/config.yml. Aborting");
}
if(in_array($_GET["format"],array("email","emailAndUpload"))) \FatcaIdesPhp\Transmitter::verifySwiftmailerConfig($config["swiftmailer"]);

// argument checking
if(!array_key_exists("format",$_GET)) $_GET['format']="html"; # default
if(!in_array($_GET['format'],array("html","xml","zip","metadata","upload","emailAndUpload","email"))) throw new Exception("Unsupported format. Please use one of: html, xml, zip, metadata");

if(in_array($_GET["format"],array("email","emailAndUpload")) && !array_key_exists("emailTo",$_GET)) $_GET["emailTo"]="s.akiki@ffaprivatebank.com"; // default

if(!array_key_exists("file",$_GET)) throw new \Exception("Missing file=path/to/file");

if(in_array($_GET["format"],array("upload","emailAndUpload")) &&
  !(array_key_exists("idesUsername",$_GET) && array_key_exists("idesPassword",$_GET))) throw new Exception("Missing upload username/password");

if($_GET["format"]=="upload" && !$_GET["shuffle"]) throw new Exception("Not allowed to upload without emailing for live data");

// retrieval from mf db table
$sxe = simplexml_load_file($_GET["file"]);
$fdx = new FatcaDataXml($sxe);
$factory = new Factory();
$tmtr=$factory->transmitter(
  $fdx,
  $_GET['format'],
  !array_key_exists("emailTo",$_GET)?null:$_GET['emailTo'],
  $config,
  $LOG_LEVEL
);

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
        $config["swiftmailer"]);
    }

    if(in_array($_GET["format"],array("upload","emailAndUpload"))) {
      $upload = array("username"=>$_GET["idesUsername"],"password"=>$_GET["idesPassword"]);
      $csm = null;
      if($_GET["format"]=="emailAndUpload") $csm = $config["swiftmailer"];
      $tmtr->toUpload($upload,$_GET["emailTo"],$csm);
    }

    break;
  default: throw new Exception("Unsupported format ".$_GET['format']);
}
