<?php

date_default_timezone_set('UTC');
$ts1=time();
$ts2=strftime("%Y-%m-%dT%H:%M:%SZ",$ts1); 
$tz=date_default_timezone_get();
var_dump($ts1,$ts2,$tz);
