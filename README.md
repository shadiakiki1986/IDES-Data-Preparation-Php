# IDES-Data-Preparation-Php
The IDES Data Preparation Php project repository demonstrates a sample working application developed using Php.
Please note that this implementation is specific to the Financial Institution for which I did this work.
You will need to make plenty of modifications to tailor it for your needs.

For more information check http://www.irs.gov/Businesses/Corporations/FATCA-IDES-Technical-FAQs

For other language implementations, please check https://github.com/IRSgov

# General
The links published in var/www/index.html via an apache server expose 3 formats of the client data: html, xml, zip.

The code itself is far from perfect. Constructive feedback is welcome.

# Pre-requisites
* a function lib/getFatcaData.php that returns client data to be submitted for FATCA
* SSL certificate for your financial institution
* Private and public keys used to get the SSL certificate
* php5, apache2, ...

# Installation instructions
Run

    git clone https://github.com/shadiakiki1986/IDES-Data-Preparation-Php
    cd IDES-Data-Preparation-Php
    composer install
    [sudo] apt-get install php5-mcrypt
    [sudo] php5enmod mcrypt
    [sudo] service apache2 restart

Download Fatca XML schema file, Sender metadata stylesheet, and IRS public key
* links available at http://www.irs.gov/Businesses/Corporations/FATCA-XML-Schemas-and-Business-Rules-for-Form-8966

    cd downloads
    wget https://www.irs.gov/pub/fatca/FATCAXMLSchemav1.zip
    unzip FATCAXMLSchemav1.zip
    rm FATCAXMLSchemav1.zip

    wget https://www.irs.gov/pub/fatca/SenderMetadatav1.1.zip
    unzip SenderMetadatav1.1.zip 
    rm SenderMetadatav1.1.zip 

    wget https://ides-support.com/Downloads/encryption-service_services_irs_gov.crt

* Download the remaining files indicated above in the Pre-Requisites section
* Copy the file in config-sample.php in the root folder to config.php
* Edit the paths in config.php to match with the installation/download locations
* Copy the file in lib/getFatcaData-SAMPL.php to lib/getFatcaData.php in the same folder
* Edit it to get the data from your own data source
* Test with `phpunit tests`

* Publish the contents of var/www in apache2 by using the sample config file provided in etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf
 * Refer to standard apache2 guides on publishing websites
* Navigate in your browser to http://your-server/IDES-Data-Preparation-Php and get your data in html, xml, or zip format
* create the folder defined in config.php under ZipBackupFolder
 * and don''t forget to chown www-data:www-data
* can also use `var/www/api/transmitter.php` from CLI
 * run `php var/www/api/transmitter.php --help` for options
