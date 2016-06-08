FROM eboraas/apache-php
MAINTAINER Shadi Akiki

# use apt-cacher
# RUN echo "Acquire::http::Proxy \"http://172.17.0.2:3142\";" | tee /etc/apt/apt.conf.d/01proxy
RUN apt-get -qq update > /dev/null

# enable mcrypt
RUN apt-get install php5-mcrypt
RUN php5enmod mcrypt

# composer
RUN apt-get -qq -y install wget curl git > /dev/null

RUN curl -sS https://getcomposer.org/installer | php
RUN chmod +x composer.phar
RUN mv composer.phar /usr/local/bin/composer

# download public files
WORKDIR /var/lib/IDES/downloads
RUN download.sh

# apache configs
WORKDIR /etc/apache2/sites-enabled
COPY etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf ../sites-available/
RUN ln -s ../sites-available/ffamfe-dg-api.conf

# keys
COPY keys /var/lib/IDES/keys

# create backup folder
WORKDIR /var/lib/IDES/bkp
RUN chown www-data:www-data . -R

# Continue
COPY . /var/lib/IDES/src
WORKDIR /var/lib/IDES/src
composer install --quiet
ENTRYPOINT entrypoint.sh
