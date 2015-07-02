<?php

require_once dirname(__FILE__).'/../config.php'; // copy the provided sample in repository/config-sample.php
require_once ROOT_IDES_DATA.'/lib/Transmitter.php';

// if installation instructions were not followed by copying the file getFatcaData-SAMPLE to getFatcaData, then just use the sample file
if(!file_exists(ROOT_IDES_DATA.'/lib/getFatcaData.php')) {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData-SAMPLE.php'; // use sample file
} else {
	require_once ROOT_IDES_DATA.'/lib/getFatcaData.php';
}

// retrieval from mf db table
$di=getFatcaData(2014);
if(count($di)==0) throw new Exception("No data");

$fca=new Transmitter($di,false,"",2014);
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

$diXml1=$fca->toXmlSigned(true);
if(!$fca->verifyXmlSigned()) print 'preservewhitespace=true => signature not verified'.PHP_EOL;

$diXml2=$fca->toXmlSigned(false);
if(!$fca->verifyXmlSigned()) print 'preservewhitespace=false => signature not verified'.PHP_EOL;

if($diXml1!=$diXml2) {
    print 'whitespace changed'.PHP_EOL;
}


file_put_contents("/home/shadi/Development/f1.xml",$diXml1);
file_put_contents("/home/shadi/Development/f2.xml",$diXml2);
