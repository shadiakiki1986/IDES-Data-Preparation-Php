# Notes
This repository depends on having a php class that can return client data. In my case, this class is called MarketflowClient. It is in a private repository. Replace with your own implementation

# Pre-requisites
* FFA-Marketflow extensions (ffa-mfe/databases-api) containing MarketflowClient php class interfacing Marketflow's database to php
* Fatca XML schema file from http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966
* SSL certificate for your financial institution
* Private and public keys used to get the SSL certificate
* IRS public key from https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt
* php5, apache2, ...

# Installation instructions
* Clone the repository at https://github.com/shadiakiki1986/IDES-Data-Preparation-Php
* Download the files indicated above in the Pre-Requisites section
* Copy the file in etc/IDES-Data-Preparation-Php-sample.php to /etc/IDES-Data-Preparation-Php.php
* Edit the paths /etc/IDES-Data-Preparation-Php.php to match with the installation/download locations
* Publish the contents of var/www in apache2 by using the sample config file provided in etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf
** Refer to standard apache2 guides on publishing websites
* Navigate in your browser to http://your-server/IDES-Data-Preparation-Php and get your data in html, xml, or zip format
