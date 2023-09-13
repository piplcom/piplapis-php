#FROM tomsowerby/php-5.3-composer
FROM belphemur/docker-php-5.3-cli-composer
WORKDIR /sdk
COPY . .



#RUN rm /etc/apt/sources.list
#
#RUN echo "deb http://archive.debian.org/debian-security jessie/updates main" >> /etc/apt/sources.list.d/jessie.list
#
#RUN echo "deb http://archive.debian.org/debian jessie main" >> /etc/apt/sources.list.d/jessie.list
#RUN apt update && apt install bash -y  --force-yes