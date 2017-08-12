#Docker images for centos php-fpm server
#Version 0.01
FROM centos
MAINTAINER IsCod "iscodd@gmail.com"

#ENV
ENV REFRESHED_AT 2017-03-16

#update centos system
RUN yum update -y && yum clean all && yum install bind -y

#install php-fpm
RUN yum install -y php-fpm

#install php extension
RUN yum install -y php-mysql php-gd php-mbstring libmemcached-devel

#install php-redis
RUN yum install -y git gcc make php-devel wget zlib-devel
WORKDIR /usr/local/src/
RUN git clone https://github.com/phpredis/phpredis.git phpredis
WORKDIR /usr/local/src/phpredis
RUN phpize && ./configure && make && make install
RUN echo "extension=redis.so" >> /etc/php.ini

#intsall php-memcache
WORKDIR /usr/local/src
RUN git clone https://github.com/tricky/php-memcache.git
WORKDIR /usr/local/src/php-memcache
RUN phpize && ./configure && make && make install
RUN echo "extension=memcache.so" >> /etc/php.ini

#install php-memecached
WORKDIR /usr/local/src/
#RUN yum install -y libmemcached
RUN wget https://github.com/php-memcached-dev/php-memcached/archive/2.2.0.tar.gz -O php-memcached-2.2.0.tar.gz && tar -zxvf php-memcached-2.2.0.tar.gz && mv php-memcached-2.2.0 php-memcached
WORKDIR /usr/local/src/php-memcached
RUN phpize && ./configure && make && make install
RUN echo "extension=memcached.so" >> /etc/php.ini

#set timezone
RUN echo "date.timezone = Asia/Shanghai" >> /etc/php.ini

EXPOSE 9000

WORKDIR /usr/local/src/
RUN git clone https://github.com/IsCod/Ctrs.git
RUN ln -s /usr/local/src/Ctrs/ctrsd.sh /etc/init.d/ctrsd
RUN cp /usr/local/src/Ctrs/ctrsd.service /usr/lib/systemd/system/ctrsd.service

ENTRYPOINT ["php-fpm"]
