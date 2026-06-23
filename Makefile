server := "vedo8279.odns.fr"
domain := "bsm-laiguizeure.fr"

.PHONY: install deploy

deploy:
	ssh vedo8279@vedo8279.odns.fr 'cd bsm-laiguizeure.fr && git pull origin main && make install'

install: vendor/autoload.php
	php bin/console doctrine:migrations:migrate -n
	php bin/console importmap:install
	php bin/console asset-map:compile
	composer dump-env prod
	php bin/console cache:clear

vendor/autoload.php: composer.lock composer.json
	composer install --no-dev --optimize-autoloader
	touch vendor/autoload.php