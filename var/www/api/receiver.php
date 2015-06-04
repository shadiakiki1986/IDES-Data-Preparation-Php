<?php
if (isset($_FILES['myFile'])) {
	header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
	header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
	header( "Cache-Control: no-cache, must-revalidate" );
	header( "Pragma: no-cache" );
	header('Content-type: text/xml');

	require_once dirname(__FILE__).'/../../../config.php'; // copy the provided sample in repository/config-sample.php
	require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
	require_once ROOT_IDES_DATA.'/lib/Receiver.php';

	$rx=new Receiver();
	$rx->fromZip($_FILES['myFile']['tmp_name']);
	$rx->decryptAesKey();
	$rx->fromEncrypted();
	$rx->fromCompressed();

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
