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

