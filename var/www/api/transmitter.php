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
 		php getFatcaClients.php [format=html(default)|xml|zip] [shuffle=true(default)|false]

 	Ajax
		$.ajax({
		    url:"http://{{server}}/IDES-Data-Preparation-Php/getFatcaClients.php",
		    type: 'GET',
		    success: function (data) {
		        console.log(data);
		    },
		    error: function (jqXHR, ts, et) {
		        console.log("error", ts, et);
		    }
		 });
*/

require_once dirname(__FILE__).'/../../../config.php'; // copy the provided sample in repository/config-sample.php

require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
require_once ROOT_IDES_DATA.'/lib/Transmitter.php';
require_once ROOT_IDES_DATA.'/lib/array2shuffledLetters.php';

// if installation instructions were not followed by copying the file getFatcaData-SAMPLE to getFatcaData, then just use the sample file
if(!file_exists(ROOT_IDES_DATA.'/lib/getFatcaData.php')) {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData-SAMPLE.php'; // use sample file
} else {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData.php';
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
    exit;
}

if(!$fca->validateXml("metadata")) {# validate
    print 'Metadata xml did not pass its xsd validation';
    libxml_display_errors();
    exit;
}


$diXml2=$fca->toXmlSigned();
//$diXml2=$fca->dataXml;
if(!$fca->verifyXmlSigned()) die("Verification of signature failed");

$fca->toCompressed();
$fca->toEncrypted();
$fca->encryptAesKeyFile();
//	if(!$fca->verifyAesKeyFileEncrypted()) die("Verification of aes key encryption failed");
$fca->toZip();

switch($_GET['format']) {
	case "html":
		echo($fca->toHtml());
		break;
	case "xml":
		Header('Content-type: text/xml');
		echo($fca->addHeader($diXml2));
		break;
	case "zip":
		$fca->getZip();
		break;
	case "metadata":
		Header('Content-type: text/xml');
		echo($fca->addHeader($fca->getMetadata()));
		break;
	default: throw new Exception("Unsupported format ".$_GET['format']);
}
