<?php

class FatcaClientsAdapter {

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
var $file_name;

function __construct($dd) {
	$this->data=$dd;
	$this->tf1=tempnam("/tmp","");
	$this->tf2=tempnam("/tmp","");
	$this->tf3=tempnam("/tmp","");
	$this->tf4=tempnam("/tmp","");

	$this->ts=time();
	$this->ts2=strftime("%Y-%m-%dT%H:%M:%SZ",$this->ts);
}

function toHtml() {
	return sprintf("<table border=1>%s%s</table>",
		implode(array_map(function($x) { return "<th>".$x."</th>"; },array_keys($this->data[0]))),
		implode(
		array_map(function($y) { return "<tr>".implode(array_map(function($x) { return "<td>".$x."</td>"; },$y))."</tr>"; },$this->data)
		));
}

function toXml() {
    $di=$this->data; # $di: output of getFatcaClients

    # convert to xml 
    $diXml=sprintf("
        <ftc:FATCA_OECD version='1.1'
            xsi:schemaLocation='urn:oecd:ties:fatca:v1 FatcaXML_v1.1.xsd'
            xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
            xmlns:sfa='urn:oecd:ties:stffatcatypes:v1'
            xmlns:ftc='urn:oecd:ties:fatca:v1'>
            <ftc:MessageSpec>
                <sfa:SendingCompanyIN>".ffaid."</sfa:SendingCompanyIN>
                <sfa:TransmittingCountry>LB</sfa:TransmittingCountry>
                <sfa:ReceivingCountry>US</sfa:ReceivingCountry>
                <sfa:MessageType>FATCA</sfa:MessageType>
                <sfa:Warning/>
                <sfa:MessageRefId>DBA6455E-8454-47D9-914B-FEE48E4EF3AA</sfa:MessageRefId>
                <sfa:ReportingPeriod>".strftime("%Y-%m-%d",$this->ts)."</sfa:ReportingPeriod>
                <sfa:Timestamp>$this->ts2</sfa:Timestamp>
            </ftc:MessageSpec>
            <ftc:FATCA>
                <ftc:ReportingFI>
                    <sfa:Name>FFA Private Bank</sfa:Name>
                    <sfa:Address>
                        <sfa:CountryCode>LB</sfa:CountryCode>
                        <sfa:AddressFree>Foch street</sfa:AddressFree>
                    </sfa:Address>
                    <ftc:DocSpec>
                        <ftc:DocTypeIndic>FATCA1</ftc:DocTypeIndic>
                        <ftc:DocRefId>50B80D2D-79DA-4AFD-8148-F06480FFDEB5</ftc:DocRefId>
                    </ftc:DocSpec>
                </ftc:ReportingFI>
            <ftc:ReportingGroup>
            %s
            </ftc:ReportingGroup>
            </ftc:FATCA>
        </ftc:FATCA_OECD>",
        implode(array_map(
            function($x) { return sprintf("
    <ftc:AccountReport>
    <ftc:DocSpec>
    <ftc:DocTypeIndic>FATCA1</ftc:DocTypeIndic>
    <ftc:DocRefId>50B80D2D-79DA-4AFD-8148-F06480FFDEB5</ftc:DocRefId>
    </ftc:DocSpec>
    <ftc:AccountNumber>%s</ftc:AccountNumber>
    <ftc:AccountHolder>
    <ftc:Individual>
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
    <ftc:AccountBalance currCode='USD'>0</ftc:AccountBalance>
    </ftc:AccountReport>
                ",
                $x['ENT_COD'],
                $x['ENT_FIRSTNAME'],
                $x['ENT_LASTNAME'],
                $x['ResidenceCountry'],
                $x['ENT_ADDRESS']
                ); },
            $di
        ),"\n")
    );

    $this->dataXml=$diXml;
    return $diXml;
}

function addHeader($x) { return sprintf("<?xml version='1.0' encoding='UTF-8'?> %s", $x); }

function validateXml() {
	# validate
	$xml=DOMDocument::loadXML($this->addHeader($this->dataXml));
	return ($xml->schemaValidate(FatcaXsd));
}

function toXmlSigned() {
	$diXml=$this->dataXml;

	// get hash
	$this->diDigest=base64_encode(hash("sha256", $diXml));

	// sign ... for some reason, the php signature is not the same as getting the openssl signature.
	// Moreover, the php signature does not get verified, whereas the openssl one does
	// I'll just use system calls to openssl instead
	//$diSign="";
	//if(!openssl_sign($this->diDigest, $diSign, openssl_pkey_get_private("file://".FatcaKeyPrivate), "RSA-SHA256")) die("Failed to sign with private key");
	file_put_contents($this->tf1,$this->diDigest);
	$cmd="openssl rsautl -sign -in $this->tf1 -inkey ".FatcaKeyPrivate." -out $this->tf2";
	system($cmd);
	$diSign=file_get_contents($this->tf2);

	// certificate
	$diCrt1=openssl_x509_parse(file_get_contents(FatcaCrt));
	if(!$diCrt1) die("Failed to open certificate");
	$diCrt1=implode(", ",array_map(function($x) use($diCrt1) { return sprintf("%s=%s",$x,$diCrt1["subject"][$x]); }, array_keys($diCrt1["subject"])));
	$diCrt2=file_get_contents(FatcaCrt,NULL,NULL,strlen("-----BEGIN CERTIFICATE-----\n"),1724-strlen("-----BEGIN CERTIFICATE-----\n")-strlen("\n-----END CERTIFICATE-----")-2);

	// envelope
	$diXml2=sprintf("
<Signature xmlns='http://www.w3.org/2000/09/xmldsig#'>
        <SignedInfo>
                <CanonicalizationMethod Algorithm='http://www.w3.org/2001/10/xml-exc-c14n#'/>
                <SignatureMethod Algorithm='http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'/>
                <Reference URI='#FATCA'>
                        <Transforms>
                                <Transform Algorithm='http://www.w3.org/2001/10/xml-exc-c14n#'/>
                        </Transforms>
                        <DigestMethod Algorithm='http://www.w3.org/2001/04/xmlenc#sha256'/>
                        <DigestValue>%s</DigestValue>
                </Reference>
        </SignedInfo>
        <SignatureValue>%s</SignatureValue>
        <KeyInfo>
                <X509Data>
                        <X509SubjectName>%s</X509SubjectName>
                        <X509Certificate>%s</X509Certificate>
                </X509Data>
        </KeyInfo>
        <Object Id='FATCA'>
		%s
        </Object>
</Signature>
		",
		$this->diDigest, 
		base64_encode($diSign),
		$diCrt1,
		$diCrt2,
		$diXml);

		$this->dataXmlSigned=$diXml2;
		return $diXml2;
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

		// add PCKS7 padding
		// http://php.net/manual/en/function.mcrypt-encrypt.php#47973
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
		$len = strlen($crypttext);
		$padding = $block - ($len % $block);
		$crypttext .= str_repeat(chr($padding),$padding);

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
			<SenderFileId>'.$this->file_name.'</SenderFileId>
			<FileCreateTs>'.$this->ts2.'</FileCreateTs>
			<TaxYear>'.strftime("%Y",$this->ts).'</TaxYear>
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
