<?php

require_once dirname(__FILE__).'/../config.php'; // copy the provided sample in repository/config-sample.php
require_once ROOT_DB_API.'/lib/MarketflowClient.php';
require_once ROOT_DB_API.'/lib/BankflowClient.php';

$mfDb=new MarketflowClient("Lebanon","Beirut");
$di=$mfDb->getFatcaClients();
$di3=$mfDb->odbc_fetch_array_array(sprintf("select CLI_COD,CLI_ENT_COD from CLIENT where CLI_ENT_COD in (%s)",implode(",",array_map(function($x) { return sprintf("'%s'",$x["ENT_COD"]); }, $di))),'');
$mfDb->disconnect();

$di4=array_unique(array_map(function($x) { return $x["CLI_COD"]; }, $di3));

$bfDb=new BankflowClient("Lebanon","Beirut");
$di2=$bfDb->cash(date("Y-m-d"),$di4,"entityid");
$bfDb->disconnect();

foreach($di as $k=>$v) {
	$t=array_filter($di2,function($x) use($v) { return $x["CLI_ENT_COD"]==$v["ENT_COD"]; });
	$t=array_values($t);
	$di[$k]["accounts"]=$t;
}

var_dump($di);
