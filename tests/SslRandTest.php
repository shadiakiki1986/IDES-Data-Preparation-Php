<?php

$x=openssl_random_pseudo_bytes(32);
$y=unpack("H*",$x);
var_dump($x,$y);
var_dump($y[1]);
var_dump(pack("H*",$y[1]));

