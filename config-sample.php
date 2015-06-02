<?php

#-------------------------------------------
# This is a config file with paths mostly.
# Check installation instructions.
#-------------------------------------------

# Root directory of installation
define("ROOT_IDES_DATA", dirname(__FILE__));// from https://github.com/shadiakiki1986/IDES-Data-Preparation-Php
define("ROOT_DB_API", "path/to/ffa-mfe/databases-api"); // installation for MarketflowClient class in ffa-mfe/databases-api (from my bitbucket private repository)
define("MF_DB_YML","path/to/file");# Marketflow database info


# more required files
define('FatcaXsd',"path/to/FATCA XML Schema v1.1/FatcaXML_v1.1.xsd"); // from http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966
define('FatcaCrt',"path/to/ssl_certificate.crt");// FFA certificate bought using the private key
define('FatcaKeyPrivate',"path/to/ffa-fatca-private.pem"); // FFA Private key used to get the FFA SSL certificate
define('FatcaKeyPublic',"path/to/ffa-fatca-public.pem"); // FFA public key extracted out of private key above
define('FatcaIrsPublic','path/to/encryption-service_services_irs_gov.crt'); // IRS public key from https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt

# Fatca GIIN's
define('ffaid','000000.00000.AA.000'); // Sender GIIN
define('ffaidReceiver','000000.00000.TA.840'); // IRS GIIN
