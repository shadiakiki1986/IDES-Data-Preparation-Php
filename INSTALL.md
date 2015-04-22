# Notes
This repository depends on having a php class that can return client data. In my case, this class is called MarketflowClient. It is in a private repository. Replace with your own implementation

# Pre-requisites
* FFA-Marketflow extensions (ffa-mfe/databases-api) containing MarketflowClient php class interfacing Marketflow's database to php
* Fatca XML schema file from http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966
* SSL certificate for your financial institution
* Private and public keys used to get the SSL certificate
* IRS public key from https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt

# Installation instructions
* Clone the repository at https://github.com/shadiakiki1986/IDES-Data-Preparation-Php
* Download the files indicated above in the Pre-Requisites section
* Copy the file in etc/IDES-Data-Preparation-Php-sample.php to /etc/IDES-Data-Preparation-Php.php
* Edit the paths /etc/IDES-Data-Preparation-Php.php to match with the installation/download locations

