<?php

require_once dirname(__FILE__).'/../config.php';
require_once ROOT_IDES_DATA.'/vendor/autoload.php'; #  if this line throw an error, I probably forgot to run composer install

// Load the XML to be signed
$doc = new DOMDocument();
$doc->loadXML('<bla><something>else</something></bla>');

$objDSig = new XMLSecurityDSig();// Create a new Security object 
$objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);// Use the c14n exclusive canonicalization

// Sign using SHA-256
$objDSig->addReference(
    $doc, 
    XMLSecurityDSig::SHA256, 
    array('http://www.w3.org/2000/09/xmldsig#enveloped-signature')
);

$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type'=>'private'));// Create a new (private) Security key
$objKey->loadKey(FatcaKeyPrivate, TRUE);// Load the private key
$objDSig->sign($objKey);// Sign the XML file
$objDSig->add509Cert(file_get_contents(FatcaCrt));// Add the associated public key to the signature
$objDSig->appendSignature($doc->documentElement);// Append the signature to the XML

$doc2 = new DOMDocument();
$doc2->loadXML('<bla><something>else</something></bla>');
$xx=$objDSig->addObject($doc2->documentElement);

$xml = $xx->ownerDocument->saveXML();
var_dump($xml);

//print $doc->saveXML();// Save the signed XML

