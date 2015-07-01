<?php
// http://phpgoogle.blogspot.com/2007/08/four-ways-to-generate-unique-id-by-php.html
// Generate Guid 
function newGuid() { 
//return rand(1,9999999);
    $s = strtolower(md5(uniqid(rand(),true))); 
    $guidText = 
        substr($s,0,8) . '-' . 
        substr($s,8,4) . '-' . 
        substr($s,12,4). '-' . 
        substr($s,16,4). '-' . 
        substr($s,20); 
    $guidText=str_replace("-","",$guidText);
    return $guidText;
}
// End Generate Guid 
