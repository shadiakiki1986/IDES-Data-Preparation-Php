<?php
require_once dirname(__FILE__).'/../config.php'; // copy the provided sample in repository/config-sample.php
require_once ROOT_IDES_DATA.'/lib/newGuid.php';

$Guid = newGuid();
echo $Guid."\n";
echo "a1aa421f-3820-4e09-8853-251465b6fe9a\n"; // from ides sample test file
