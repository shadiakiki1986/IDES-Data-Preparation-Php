<?php

require_once dirname(__FILE__).'/../config.php';
require_once ROOT_IDES_DATA.'/vendor/autoload.php'; #  if this line throw an error, I probably forgot to run composer install
require_once ROOT_IDES_DATA.'/lib/newGuid.php';

class Transmitter {

var $data; // php data with fatca information
var $dataXml;
var $dataXmlSigned;
var $dataCompressed;
var $dataEncrypted;
var $diDigest;
var $aeskey;
var $tf1;
var $tf2;
var $tf3;
var $tf4;
var $aesEncrypted;
var $ts;
var $ts2;
var $ts3;
var $file_name;
var $docType;
var $corrDocRefId;
var $taxYear;

function __construct($dd,$isTest,$corrDocRefId,$taxYear) {
// dd: 2d array with fatca-relevant fields
// isTest: true|false whether the data is test data. This will only help set the DocTypeIndic field in the XML file
// corrDocRefId: false|message ID. If this is a correction of a previous message, pass the message ID in subject, otherwise just pass false

	$this->data=$dd;
	$this->corrDocRefId=$corrDocRefId;
	$this->docType=sprintf("FATCA%s%s",$isTest?"1":"",$corrDocRefId?"2":"1");
	$this->taxYear=$taxYear;

	// Sanity check
	// docType: described in xsd for DocRefId
	// FATCA1: New Data, FATCA2: Corrected Data, FATCA3: Void Data, FATCA4: Amended Data
	// FATCA11: New Test Data, FATCA12: Corrected Test Data, FATCA13: Void Test Data, FATCA14: Amended Test Data
	$docTypeValid=array("FATCA11","FATCA12","FATCA13","FATCA14","FATCA1","FATCA2","FATCA3","FATCA4");
	if(!in_array($this->docType,$docTypeValid)) throw new Exception("Unsupported docType. Please use: ".implode(", ",$docTypeValid));


	// following http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schema-Best-Practices-for-Form-8966
	// the hash in an address should be replaced
	$this->data=array_map(function($x) {
		$reps=array(":","#",",","-",".","--","/");
		$x['ENT_ADDRESS']=str_replace($reps," ",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=preg_replace('/\s\s+/',' ',$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("1st","First",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("9th","Ninth",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("6th","Sixth",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("6TH","Sixth",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("3rd","Third",$x['ENT_ADDRESS']);
		$x['ENT_ADDRESS']=str_replace("8TH","Eighth",$x['ENT_ADDRESS']);
		$x['ENT_FATCA_ID']=str_replace($reps," ",$x['ENT_FATCA_ID']);
		$x['ENT_FATCA_ID']=str_replace(array("S","N"," "),"",$x['ENT_FATCA_ID']);
		return $x;
	}, $this->data);

	// reserving some filenames
	$this->tf1=tempnam("/tmp","");
	$this->tf2=tempnam("/tmp","");
	$this->tf3=tempnam("/tmp","");
	$this->tf4=tempnam("/tmp","");

	date_default_timezone_set('UTC');
	$this->ts=time();
	// ts2 is xsd:dateTime
	// http://www.datypic.com/sc/xsd/t-xsd_dateTime.html
	// Even though the xsd:dateTime supports dates without a timezone,
	// dropping the Z from here causes the metadata file not to pass the schema
	// (and a RC004 to be received instead of RC001)
	$this->ts2=strftime("%Y-%m-%dT%H:%M:%SZ",$this->ts); 
	$this->ts3=strftime("%Y-%m-%dT%H:%M:%S", $this->ts); 
}

function toHtml() {
	$dv=array_values($this->data);
	return sprintf("<table border=1>%s%s</table>",
		implode(array_map(function($x) { return "<th>".$x."</th>"; },array_keys($dv[0]))),
		implode(
		array_map(function($y) {
			return "<tr>".implode(array_map(function($x) {
				if(!is_array($x)) {
					return "<td>".$x."</td>";
				} else {
					return sprintf("<td><ul>%s</ul></td>",
						implode("",
							array_map(function($z) {
								return sprintf("<li>%s %s%s</li>",
									$z["posCur"],
									$z["cur"],
									($z["CLI_CLOSED_DATE"]?sprintf(" (%s)",$z["CLI_CLOSED_DATE"]):"")
								);
							},$x)
						)
					);
				}
			},$y))."</tr>"; },$this->data)
		));
}

function toXml() {
    $di=$this->data; # $di: output of getFatcaClients
    $docType=$this->docType;

    # convert to xml 
    #        xsi:schemaLocation='urn:oecd:ties:fatca:v1 FatcaXML_v1.1.xsd'
    $diXml=sprintf("
        <ftc:FATCA_OECD version='1.1'
            xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
            xmlns:sfa='urn:oecd:ties:stffatcatypes:v1'
            xmlns:ftc='urn:oecd:ties:fatca:v1'>
            <ftc:MessageSpec>
                <sfa:SendingCompanyIN>".ffaid."</sfa:SendingCompanyIN>
                <sfa:TransmittingCountry>LB</sfa:TransmittingCountry>
                <sfa:ReceivingCountry>US</sfa:ReceivingCountry>
                <sfa:MessageType>FATCA</sfa:MessageType>
                <sfa:Warning/>
                <sfa:MessageRefId>%s</sfa:MessageRefId>
                <sfa:ReportingPeriod>".sprintf("%s-12-31",$this->taxYear)."</sfa:ReportingPeriod>
                <sfa:Timestamp>$this->ts3</sfa:Timestamp>
            </ftc:MessageSpec>
            <ftc:FATCA>
                <ftc:ReportingFI>
                    <sfa:Name>FFA Private Bank</sfa:Name>
                    <sfa:Address>
                        <sfa:CountryCode>LB</sfa:CountryCode>
                        <sfa:AddressFree>Foch street</sfa:AddressFree>
                    </sfa:Address>
                    <ftc:DocSpec>
                        <ftc:DocTypeIndic>%s</ftc:DocTypeIndic>
                        <ftc:DocRefId>%s</ftc:DocRefId>
			%s
                    </ftc:DocSpec>
                </ftc:ReportingFI>
            <ftc:ReportingGroup>
            %s
            </ftc:ReportingGroup>
            </ftc:FATCA>
        </ftc:FATCA_OECD>",
	newGuid(),
	$docType, // not sure about this versus the same entry below
	//sprintf("%s.%s",ffaid,newGuid()), // based on http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-Best-Practices-for-Form-8966-DocRefID
	newGuid(),
	!$this->corrDocRefId?"":sprintf("<ftc:CorrDocRefId>%s</ftc:CorrDocRefId>",$this->corrDocRefId), 
        implode(array_map(
            function($x) use($docType) {
		
		return sprintf("
		    <ftc:AccountReport>
		    <ftc:DocSpec>
		    <ftc:DocTypeIndic>%s</ftc:DocTypeIndic>
		    <ftc:DocRefId>%s</ftc:DocRefId>
		    </ftc:DocSpec>
		    <ftc:AccountNumber>%s</ftc:AccountNumber>
		    <ftc:AccountHolder>
		    <ftc:Individual>
			<sfa:TIN issuedBy='US'>%s</sfa:TIN>
			<sfa:Name>
			    <sfa:FirstName>%s</sfa:FirstName>
			    <sfa:LastName>%s</sfa:LastName>
			</sfa:Name>
			<sfa:Address>
			    <sfa:CountryCode>%s</sfa:CountryCode>
			    <sfa:AddressFree>%s</sfa:AddressFree>
			</sfa:Address>
		    </ftc:Individual>
		    </ftc:AccountHolder>
		    <ftc:AccountBalance currCode='%s'>%s</ftc:AccountBalance>
		    </ftc:AccountReport>
                ",
		$docType, // check the xsd
		//sprintf("%s.%s",ffaid,newGuid()), // based on http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-Best-Practices-for-Form-8966-DocRefID
		newGuid(),
                $x['Compte'],
                $x['ENT_FATCA_ID'],
                $x['ENT_FIRSTNAME'],
                $x['ENT_LASTNAME'],
                $x['ResidenceCountry'],
                $x['ENT_ADDRESS'],
		$x['cur'],
		$x['posCur']
                ); },
            $di
        ),"\n")
    );

    $this->dataXml=$diXml;
    return $diXml;
}

function addHeader($x) {
	/*
	return sprintf("<?xml version='1.0' encoding='UTF-8'?> %s", $x);
	*/
	return $x;
}

function validateXml($whch="payload") {
	# validate
	$xml="";
	$xsd="";
	switch($whch) {
	case "payload":
		$xml=$this->dataXml;
		$xsd=FatcaXsd;
		break;
	case "metadata":
		$xml=$this->getMetadata();
		$xsd=MetadataXsd;
		break;
	default: throw new Exception("Invalid xml file to validate");
	}

	$xmlDom=DOMDocument::loadXML($this->addHeader($xml));
	return $xmlDom->schemaValidate($xsd);
}


function toXmlSigned() {
// using https://github.com/robrichards/xmlseclibs

		// Load the XML to be signed
		$doc = new DOMDocument();
		$doc->loadXML($this->dataXml);

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
		$doc2->loadXML($this->dataXml);
		$xx=$objDSig->addObject($doc2->documentElement);

		$this->dataXmlSigned = $xx->ownerDocument->saveXML();

		// character replacements to avoid the security threat
		$this->dataXmlSigned=str_replace("http://www.w3.org/2001/10/xml-exc-c14n#","http://www.w3.org/TR/2001/REC-xml-c14n-20010315",$this->dataXmlSigned);
		$this->dataXmlSigned=str_replace("http://www.w3.org/2000/09/xmldsig#enveloped-signature","http://www.w3.org/TR/2001/REC-xml-c14n-20010315",$this->dataXmlSigned);

		return $this->dataXmlSigned;
	}

	function verifyXmlSigned() {
		# verify signature key
/*
			// aparently this wouldn't work in php
			// so I'm just doing it in openssl CLI
			// http://stackoverflow.com/a/10604445
			$pubkeyid=openssl_pkey_get_public("file://".FatcaKeyPublic);

			// state whether signature is okay or not
			$ok = openssl_verify($this->diDigest, $diSign, $pubkeyid);
			if ($ok == 1) {
			    //echo "good";
			} elseif ($ok == 0) {
			    throw new Exception("bad verification of signature");
			} else {
			    die("ugly, error checking signature");
			}
			// free the key from memory
			openssl_free_key($pubkeyid);
*/
			return(exec("openssl rsautl -verify -in $this->tf2 -inkey ".FatcaKeyPublic." -pubin")==$this->diDigest);
	}

	function toCompressed() {
		$zip = new ZipArchive();
		$filename = $this->tf3;

		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
		    exit("cannot open <$filename>\n");
		}

		$zip->addFromString(ffaid."_Payload.xml", $this->addHeader($this->dataXmlSigned));
		$zip->close();

		$this->dataCompressed=file_get_contents($this->tf3);
	}

	function toEncrypted() {
		$this->aeskey = openssl_random_pseudo_bytes(32);
		// $this->aeskey = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
		$key_size =  strlen($this->aeskey);
		if($key_size!=32) die("Invalid key size ".$key_size);
		$text = $this->dataCompressed;
		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->aeskey, $text, "ecb");

/*		// add PCKS7 padding
		// http://php.net/manual/en/function.mcrypt-encrypt.php#47973
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
		$len = strlen($crypttext);
		$padding = $block - ($len % $block);
		$crypttext .= str_repeat(chr($padding),$padding);
*/
		$this->dataEncrypted=$crypttext;
	}

	function readIrsPublicKey($returnResource=true) {
	  $fp=fopen(FatcaIrsPublic,"r");
	  $pub_key_string=fread($fp,8192);
	  fclose($fp);
	  if($returnResource) {
		$pub_key="";
		$pub_key=openssl_get_publickey($pub_key_string); 
		return $pub_key;
	  } else {
		return $pub_key_string;
	  }
	}

	function encryptAesKeyFile() {
/*		$tf1=tempnam("/tmp","");
		$tf2=tempnam("/tmp","");
		file_put_contents($tf1,$this->aeskey);
		$cmd="openssl rsautl -encrypt -pubin -inkey ".FatcaIrsPublic." -in ".$tf1." -out ".$tf2;
		exec($cmd);
var_dump("asdfasdf",$cmd);
		$this->aesEncrypted=file_get_contents($tf2);
*/

		$this->aesEncrypted="";
		if(!openssl_public_encrypt ( $this->aeskey , $this->aesEncrypted , $this->readIrsPublicKey() )) throw new Exception("Did not encrypt aes key");
		if($this->aesEncrypted=="") throw new Exception("Failed to encrypt AES key");
	}

	function verifyAesKeyFileEncrypted() {
/*		$tf1=tempnam("/tmp","");
		file_put_contents($tf1,$this->aesEncrypted);
var_dump($this->aesEncrypted);
		$cmd="openssl rsautl -decrypt -pubin -in $tf1 -inkey ".FatcaIrsPublic;
var_dump($cmd);
		$temp=exec($cmd);
var_dump(base64_encode($this->aeskey));
var_dump($temp);
*/
		$pubk=$this->readIrsPublicKey(true);
//var_dump($pubk,$this->aesEncrypted);
		$decrypted="";
		if(!openssl_public_decrypt( $this->aesEncrypted , $decrypted , $pubk )) throw new Exception("Failed to decrypt aes key for verification purposes");
		return($decrypted==$this->aeskey);
	}

	function getMetadata() {
		$this->file_name = strftime("%Y%m%d%H%M%S00%Z",$this->ts)."_".ffaid.".zip";

		$md='<?xml version="1.0" encoding="utf-8"?>
		<FATCAIDESSenderFileMetadata xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:fatca:idessenderfilemetadata">
			<FATCAEntitySenderId>'.ffaid.'</FATCAEntitySenderId>
			<FATCAEntityReceiverId>000000.00000.TA.840</FATCAEntityReceiverId>
			<FATCAEntCommunicationTypeCd>RPT</FATCAEntCommunicationTypeCd>
			<SenderFileId>'.rand(1,9999999).'</SenderFileId>
			<FileCreateTs>'.$this->ts2.'</FileCreateTs>
			<TaxYear>'.$this->taxYear.'</TaxYear>
			<FileRevisionInd>false</FileRevisionInd>
		</FATCAIDESSenderFileMetadata>';
		return $md;
	}

	function toZip() {
		$zip = new ZipArchive();
		$filename = $this->tf4;

		if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
		    exit("cannot open <$filename>\n");
		}

		$zip->addFromString(ffaid."_Payload", $this->dataEncrypted);
		$zip->addFromString(ffaidReceiver."_Key", $this->aesEncrypted);
		$zip->addFromString(ffaid."_Metadata.xml", $this->getMetadata());
		$zip->close();
	}

	function getZip() {
		// or however you get the path
		$yourfile = $this->tf4;
		date_default_timezone_set("UTC");

		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$this->file_name);
		header("Content-Length: " . filesize($yourfile));

		readfile($yourfile);
	}

} // end class
