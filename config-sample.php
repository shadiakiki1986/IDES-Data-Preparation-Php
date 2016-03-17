<?php

#-------------------------------------------
# This is a config file with paths mostly.
# Check installation instructions.
#-------------------------------------------

# Root directory of installation
define("ROOT_IDES_DATA", dirname(__FILE__));// from https://github.com/shadiakiki1986/IDES-Data-Preparation-Php

# more required files
define('FatcaCrt',"path/to/ssl_certificate.crt");// FFA certificate bought using the private key
define('FatcaKeyPrivate',"path/to/ffa-fatca-private.pem"); // FFA Private key used to get the FFA SSL certificate
define('FatcaKeyPublic',"path/to/ffa-fatca-public.pem"); // FFA public key extracted out of private key above

# some files already downloaded in `INSTALL.md` if instructions were followed
define('FatcaIrsPublic',ROOT_IDES_DATA.'/downloads/encryption-service_services_irs_gov.crt');
define('FatcaXsd',ROOT_IDES_DATA."/downloads/FATCA XML Schema v1.1/FatcaXML_v1.1.xsd");
define('MetadataXsd',ROOT_IDES_DATA.'/downloads/SenderMetadatav11/FATCA IDES SENDER FILE METADATA XML LIBRARY/FATCA-IDES-SenderFileMetadata-1.1.xsd');

# Fatca GIIN's
define('ffaid','000000.00000.AA.000'); // Sender GIIN
define('ffaidReceiver','000000.00000.TA.840'); // IRS GIIN

# make sure www-data is the owner of the below folder
# sudo chown www-data:www-data path/to/folder
define('ZipBackupFolder','path/to/folder/in/which/to/save/the/zip/files/for/backup'); // This is in case the references in the unencrypted xml file need to be checked later
