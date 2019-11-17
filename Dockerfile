FROM php:7.3-apache

RUN apt-get update
RUN apt-get install -y vim
RUN a2enmod cgi
