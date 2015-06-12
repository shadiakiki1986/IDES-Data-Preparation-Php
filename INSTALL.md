# Pre-requisites
* a function lib/getFatcaData.php that returns client data to be submitted for FATCA
* Fatca XML schema file from http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966
* SSL certificate for your financial institution
* Private and public keys used to get the SSL certificate
* IRS public key from https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt
* php5, apache2, ...

# Installation instructions
* Clone the repository at https://github.com/shadiakiki1986/IDES-Data-Preparation-Php
* Download the files indicated above in the Pre-Requisites section
* Copy the file in config-sample.php in the root folder to config.php
* Edit the paths in config.php to match with the installation/download locations
* Copy the file in lib/getFatcaData-SAMPL.php to lib/getFatcaData.php in the same folder
* Edit it to get the data from your own data source
* Publish the contents of var/www in apache2 by using the sample config file provided in etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf
** Refer to standard apache2 guides on publishing websites
* Navigate in your browser to http://your-server/IDES-Data-Preparation-Php and get your data in html, xml, or zip format
