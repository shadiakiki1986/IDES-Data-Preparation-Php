<?php
if (isset($_FILES['myFile'])) {
	header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
	header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
	header( "Cache-Control: no-cache, must-revalidate" );
	header( "Pragma: no-cache" );
	header('Content-type: text/xml');

  if(!defined("ROOT_IDES_DATA")) define("ROOT_IDES_DATA",__DIR__."/../..");
  $config=yaml_parse_file(ROOT_IDES_DATA.'/config.yml');
	$rx=Receiver::shortcut($config,$_FILES['myFile']['tmp_name']);

	//	echo "<ol> Received zip:\n";
	//	echo "<li>Name: ".$_FILES['myFile']['name']."</li>\n";
	//	echo "<li>Size: ",$_FILES['myFile']['size']." bytes</li>\n";
	//      echo "<li>From: ".$rx->from."</li>\n";
	//	echo "<li>To: ".$rx->to."</li>\n";
	//	echo "<li>Verified: ".$rx->verifyAesKeyFileDecrypted()."</li>\n";
	//	echo "</ol>";

	echo $rx->dataXmlSigned;

} else {
	print "This php file is used from the IDES file upload section in /var/www/index.html. Please do not access it directly as such";
}
