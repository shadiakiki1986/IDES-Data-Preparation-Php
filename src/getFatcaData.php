<?php

// year: year for which taxes are being submitted
// ...
// Returns object of type FatcaDataOecd
function getFatcaData($shuffle,$corrDocRefId,$taxYear,$config) {

  $di=yaml_parse_file(__DIR__.'/../vendor/shadiakiki1986/fatca-ides-php/tests/FatcaIdesPhp/fdatIndividual.yml');

  if($shuffle) {
    // shuffle all fields except these... ,"Compte"
    $fieldsNotShuffle = array("ResidenceCountry","posCur","cur","ENT_TYPE");
    if(array_key_exists("AccountReports", $di)) {
      $di["AccountReports"]=\FatcaIdesPhp\Utils::array2shuffledLetters($di["AccountReports"],$fieldsNotShuffle); 
    }
  }

  $conMan = new \FatcaIdesPhp\ConfigManager($config); //,$LOG_LEVEL);
  $fda = new \FatcaIdesPhp\FatcaDataArray($di,$shuffle,$corrDocRefId,$taxYear,$conMan);
  $factory = new \FatcaIdesPhp\Factory();
  $fdo = $factory->array2oecd($fda);
  return $fdo;
};
