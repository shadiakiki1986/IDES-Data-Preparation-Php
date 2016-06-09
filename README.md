# IDES-Data-Preparation-Php
This is a sample php web app that uses [FatcaIdesPhp](https://github.com/shadiakiki1986/FatcaIdesPhp) php library to convert bank client data to FATCA files submittable via the IDES gateway.

For more information check the [IRS FATCA IDES Technical FAQs](http://www.irs.gov/Businesses/Corporations/FATCA-IDES-Technical-FAQs)

For other language implementations, please check the [IRS github page](https://github.com/IRSgov)

# Pre-requisites
[docker](docker.com)

# Usage
You can use this image as a web app or as a CLI.

To use it as a web app: 
1. Run the dockerfile with `docker run -p 8123:80 -it shadiakiki1986/IDES-Data-Preparation-Php`
2. Navigate in your browser to `http://localhost:8123`

To use it as a CLI
1. Run the dockerfile with `docker run -p 8123:80 -i --entrypoint bash -t shadiakiki1986/IDES-Data-Preparation-Php`
2. At the terminal inside the image run `php www/transmitter.php --help`

# License
Please check [[LICENSE]]

# Developer notes
```bash
docker build .
docker run -i -p 80:80 -t CONTAINERID
docker run -i -p 80:80 -v /home/ubuntu/Development/IDES-Data-Preparation-Php/www:/var/lib/IDES/www -t CONTAINERID

docker build --no-cache .
docker run -i -p 80:80 --entrypoint bash -t CONTAINERID
```

When moving to php 7, I need to change
* the php5-... package names in apt-get install
* the php5/cli/php.ini paths
* use pecl install yaml-beta instead of yaml
* check if php5enmod or just phpenmod

To publish app in aws elasticbeanstalk
* read [this](http://blogs.aws.amazon.com/application-management/post/Tx1ZLAHMVBEDCOC/Dockerizing-a-Python-Web-App)
