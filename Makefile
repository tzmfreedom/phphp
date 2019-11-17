.PHONY: run
run:
	php bin/php.php test.php

.PHONY: install
install:
	composer install

.PHONY: docker/build
docker/build:
	docker build . -t phphp-apache

.PHONY: docker/run
docker/run:
	docker run --rm -d -p 8080:80 -v $(PWD):/var/www/html phphp-apache
