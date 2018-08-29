FROM bitboole-php72
COPY ./ /opt/
RUN chown -R apache:apache /opt
CMD [ "php-fpm", "-c", "/etc/php.ini" ,"-y" ,"/etc/php-fpm.d/www.conf","-F"]
EXPOSE 9000
