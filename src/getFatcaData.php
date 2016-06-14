<?php

// year: year for which taxes are being submitted
// ...
// Returns object of type FatcaDataArray
function getFatcaData($shuffle,$corrDocRefId,$taxYear,$config) {

  $di=yaml_parse_file(__DIR__.'/../vendor/shadiakiki1986/fatca-ides-php/tests/FatcaIdesPhp/fdatIndividual.yml');

  if($shuffle) {
    // shuffle all fields except these... ,"Compte"
    $fieldsNotShuffle = array("ResidenceCountry","posCur","cur","ENT_TYPE");
    $di=\FatcaIdesPhp\Utils::array2shuffledLetters($di,$fieldsNotShuffle); 
  }

  $dm = new \FatcaIdesPhp\Downloader(null); //,$LOG_LEVEL);
  $conMan = new \FatcaIdesPhp\ConfigManager($config,$dm); //,$LOG_LEVEL);
  $fda = new \FatcaIdesPhp\FatcaDataArray($di,$shuffle,$corrDocRefId,$taxYear,$conMan);
  return $fda;
};
