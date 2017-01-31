<?php
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );
header('Content-type: text/xml');

require_once __DIR__.'/../bootstrap.php';

$config=yaml_parse_file(__DIR__.'/../etc/config.yml');
$cm = new \FatcaIdesPhp\ConfigManager($config);
$cm->prefixIfNeeded(__DIR__."/..");
$cm->checkExist();
if(count($cm->msgs)>0) throw new \Exception(implode("\n",$cm->msgs));
$config = $cm->config;

$zipfile = null;
//-----------------------------------
if (isset($_FILES['myFile'])) {
  $zipfile = $_FILES['myFile']['tmp_name'];
  $rx=FatcaIdesPhp\Receiver::shortcut($config,$zipfile);

  //	echo "<ol> Received zip:\n";
  //	echo "<li>Name: ".$_FILES['myFile']['name']."</li>\n";
  //	echo "<li>Size: ",$_FILES['myFile']['size']." bytes</li>\n";
  //      echo "<li>From: ".$rx->from."</li>\n";
  //	echo "<li>To: ".$rx->to."</li>\n";
  //	echo "<li>Verified: ".$rx->verifyAesKeyFileDecrypted()."</li>\n";
  //	echo "</ol>";

  echo $rx->dataXmlSigned;
  return;
}
//-----------------------------------

$LOG_LEVEL=Monolog\Logger::WARNING;
if(isset($argc)) {
  $_GET=array();
  $options = getopt("hdu:p:s", array("help","debug","idesUsername:","idesPassword:","shuffleSkip"));
  foreach($options as $k=>$v) {
    switch($k) {
      case "h":
      case "help":
        echo "Usage: \n";
        echo "       php ".basename(__FILE__)." --help\n";
        echo "       php ".basename(__FILE__)." [--debug] --idesUsername=username --idesPassword=password [--shuffleSkip]\n";
        exit;
        break;
      case "d":
      case "debug":
        $LOG_LEVEL=Monolog\Logger::DEBUG;
        break;
      case "u":
      case "idesUsername":
        $_GET["idesUsername"]=$v;
        break;
      case "p":
      case "idesPassword":
        $_GET["idesPassword"]=$v;
        break;
      case "s":
      case "shuffleSkip":
        $_GET["shuffle"]="false";
        break;
    }
  }
}

if(!array_key_exists("idesUsername",$_GET) || !array_key_exists("idesPassword",$_GET)) die("Please pass --idesUsername=... --idesPassword=...\n");

if(!array_key_exists("shuffle",$_GET)) $_GET['shuffle']="true"; # default
if(!in_array($_GET['shuffle'],array("true","false"))) throw new Exception("Unsupported shuffle. Please use true or false");
$_GET['shuffle']=($_GET['shuffle']=="true");

$rx=FatcaIdesPhp\Receiver::shortcut(
  $config,
  null,
  array("username"=>$_GET["idesUsername"],"password"=>$_GET["idesPassword"]),
  $_GET["shuffle"]?"test":"live",
  $LOG_LEVEL
);
echo $rx->dataXmlSigned;


