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
 		php getFatcaClients.php [format=html(default)|xml|zip]

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

require_once ROOT_DB_API.'/lib/MarketflowClient.php';
require_once ROOT_DB_API.'/var/www/api/argsProcessor.php';
require_once ROOT_IDES_DATA.'/lib/libxml_helpers.php';
require_once ROOT_IDES_DATA.'/lib/FatcaClientsAdapter.php';
require_once ROOT_IDES_DATA.'/lib/array2shuffledLetters.php';

try {
	if(!array_key_exists("format",$_GET)) $_GET['format']="html"; # default
	if(!in_array($_GET['format'],array("html","xml","zip"))) throw new Exception("Unsupported format. Please use html or xml");

	// retrieval from mf db table
	$mfDb=new MarketflowClient($base,$location);
	$di=$mfDb->getFatcaClients();
	$di=array2shuffledLetters($di,array("ResidenceCountry"));
	$mfDb->disconnect();

	if(count($di)==0) throw new Exception("No data");

	$fca=new FatcaClientsAdapter($di);
	$fca->toXml(); # convert to xml 

	if(!$fca->validateXml()) {# validate
	    print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
	    libxml_display_errors();
	    exit;
	}

	$diXml2=$fca->toXmlSigned();
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
		default: throw new Exception("Unsupported format ".$_GET['format']);
	}
} catch(Exception $e) { echo json_encode(array('error'=>$e->getMessage())); }

