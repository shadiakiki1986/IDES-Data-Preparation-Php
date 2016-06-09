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

# apache configs
WORKDIR /etc/apache2/sites-enabled
COPY etc/apache2/sites-available/IDES-Data-Preparation-Php-sample.conf ../sites-available/
RUN ln -s ../sites-available/ffamfe-dg-api.conf

# Continue
COPY . /var/lib/IDES/
WORKDIR /var/lib/IDES/
composer install --quiet

# chown of backup folder so that apache can put files there
RUN chown www-data:www-data bkp -R

# LAUNCH
ENTRYPOINT /usr/sbin/apache2ctl -D FOREGROUND
