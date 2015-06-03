<?php

class Receiver {

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

var $from;
var $to;

function __construct($tf4=false) {
	if(!$tf4) {
		$tf4=sys_get_temp_dir();
		$temp_file = tempnam(sys_get_temp_dir(), 'Tux');
		unlink($temp_file);
		mkdir($temp_file);
		$tf4=$temp_file;
	}

	$this->tf1=tempnam("/tmp","");
	$this->tf2=tempnam("/tmp","");
	$this->tf3=tempnam("/tmp","");
	$this->tf4=$tf4;

	$this->ts=time();
	$this->ts2=strftime("%Y-%m-%dT%H:%M:%SZ",$this->ts);
}

function fromZip($filename) {
	$zip = new ZipArchive();
	if ($zip->open($filename) === TRUE) {
	    $zip->extractTo($this->tf4);
	    $zip->close();
	} else {
	    throw new Exception('failed to open archive');
	}

	$xx=scandir($this->tf4);
	$this->files["payload"]=array_values(preg_grep("/.*_Payload/",$xx));
	$this->files["payload"]=$this->files["payload"][0];
	$this->from=preg_replace("/(.*)_Payload/","$1",$this->files["payload"]);
	$this->files["key"]=array_values(preg_grep("/.*_Key/",$xx));
	$this->files["key"]=$this->files["key"][0];
	$this->to  =preg_replace("/(.*)_Key/","$1",$this->files["key"]);

	  $fp=fopen($this->tf4."/".$this->files["key"],"r");
	  $this->aesEncrypted=fread($fp,8192);
	  fclose($fp);
}

	function decryptAesKey() {
		$this->aeskey="";
		if(!openssl_private_decrypt( $this->aesEncrypted , $this->aeskey , $this->readFfaPrivateKey() )) throw new Exception("Could not decrypt aes key");
		if($this->aeskey=="") throw new Exception("Failed to decrypt AES key");
	}

	function readFfaPrivateKey($returnResource=true) {
	  $kk=($this->from=="000000.00000.TA.840"?FatcaKeyPrivate:("B7PPBF.00000.LE.422"?FatcaIrsPublic:die("WTF")));
	  $fp=fopen($kk,"r");
	  $priv_key_string=fread($fp,8192);
	  fclose($fp);
	  if($returnResource) {
		$priv_key="";
		$priv_key=openssl_get_privatekey($priv_key_string); 
		return $priv_key;
	  } else {
		return $priv_key_string;
	  }
	}

	function fromEncrypted() {
		//$this->aeskey = openssl_random_pseudo_bytes(32);
		// $this->aeskey = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
		$key_size =  strlen($this->aeskey);
		if($key_size!=32) throw new Exception("Invalid key size ".$key_size);

		$fp=fopen($this->tf4."/".$this->files["payload"],"r");
		$this->dataEncrypted=fread($fp,8192);
		fclose($fp);

/*		// remove PCKS7 padding
		// http://php.net/manual/en/function.mcrypt-encrypt.php#47973
		$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
		$len = strlen($crypttext);
		$padding = $block - ($len % $block);
		$crypttext .= str_repeat(chr($padding),$padding); // change this to drop last characters
*/
		$this->dataCompressed = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->aeskey, $this->dataEncrypted, "ecb");
	}

	function fromCompressed() {
		$tf3=tempnam("/tmp","");
		file_put_contents($tf3,$this->dataCompressed);

		$zip = new ZipArchive();
		if ($zip->open($tf3) === TRUE) {
			$this->dataXmlSigned=$zip->getFromIndex(0);
			$zip->close();
		} else {
		    throw new Exception('failed to read compressed data');
		}
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

} // end class
