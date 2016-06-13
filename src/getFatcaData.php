<?php

// year: year for which taxes are being submitted
// ...
// Returns object of type FatcaDataArray
function getFatcaData($shuffle,$corrDocRefId,$taxYear,$config) {

	$di = array(
		array("Compte"=>"1234","ENT_FIRSTNAME"=>"Clyde","ENT_LASTNAME"=>"Barrow","ENT_FATCA_ID"=>"123-1234-123","ENT_ADDRESS"=>"Some street somewhere","ResidenceCountry"=>"US","posCur"=>100000000,"cur"=>"USD","ENT_TYPE"=>"Individual"),
		array("Compte"=>"5678","ENT_FIRSTNAME"=>"Bonnie","ENT_LASTNAME"=>"Parker","ENT_FATCA_ID"=>"456-1234-123","ENT_ADDRESS"=>"Dallas, Texas","ResidenceCountry"=>"US","posCur"=>100,"cur"=>"LBP","ENT_TYPE"=>"Individual")
	);

  if($shuffle) {
    // shuffle all fields except these... ,"Compte"
    $fieldsNotShuffle = array("ResidenceCountry","posCur","cur");
    $di=\FatcaIdesPhp\Utils::array2shuffledLetters($di,$fieldsNotShuffle); 
  }

  $dm = new \FatcaIdesPhp\Downloader(null); //,$LOG_LEVEL);
  $conMan = new \FatcaIdesPhp\ConfigManager($config,$dm); //,$LOG_LEVEL);
  $fda = new \FatcaIdesPhp\FatcaDataArray($di,$shuffle,$corrDocRefId,$taxYear,$conMan);
  return $fda;
};
