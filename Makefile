.PHONY: run
run:
	php bin/php.php < test.php

.PHONY: install
install:
	composer install
