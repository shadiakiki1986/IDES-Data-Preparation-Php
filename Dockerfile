FROM php:7-apache
MAINTAINER Shadi Akiki

# use apt-cacher
# RUN echo "Acquire::http::Proxy \"http://172.17.0.2:3142\";" | tee /etc/apt/apt.conf.d/01proxy

# set up
RUN apt-get -qq update > /dev/null
RUN apt-get -qq -y install curl git libmcrypt-dev libyaml-dev > /dev/null
RUN docker-php-ext-install mcrypt
RUN apt-get -qq -y install zlib1g-dev > /dev/null
RUN pecl channel-update pecl.php.net && \
    pecl install zip yaml-beta < /dev/null
RUN docker-php-ext-enable yaml zip mcrypt

RUN curl -sS https://getcomposer.org/installer | php && \
    chmod +x composer.phar && \
    mv composer.phar /usr/local/bin/composer

# apache configs
WORKDIR /etc/apache2/sites-enabled
COPY etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf ../sites-available/
RUN ln -s ../sites-available/IDES-Data-Preparation-Php-sample.conf

# php configs
# Edit 2016-09-06: This was for php5 ... now we're at php5, so commenting this out
# COPY etc/php5/php.ini /etc/php5/cli/php.ini
# COPY etc/php5/php.ini /etc/php5/apache2/php.ini

# Continue
COPY . /var/lib/IDES/
WORKDIR /var/lib/IDES/

# assert config availability
RUN test -f etc/config.yml && mkdir etc/ssl && mkdir -p cache/bkp && mkdir cache/downloads

RUN composer install --quiet

# copy test ssl files
RUN cp vendor/robrichards/xmlseclibs/tests/mycert.pem etc/ssl/ && \
    cp vendor/robrichards/xmlseclibs/tests/privkey.pem etc/ssl/ && \
    cp vendor/shadiakiki1986/fatca-ides-php/tests/FatcaIdesPhp/pubkey.pem etc/ssl/

# LAUNCH
ENTRYPOINT ["bash","entrypoint.sh"]
