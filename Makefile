# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: static-code-analysis
static-code-analysis: vendor ## Runs a static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: check-symfony vendor ## Generates a baseline for static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyze --configuration=phpstan-default.neon.dist --generate-baseline=phpstan-default-baseline.neon --memory-limit=-1

.PHONY: tests
tests: vendor
	vendor/bin/phpunit tests

.PHONY: vendor
vendor: composer.json composer.lock ## Installs composer dependencies
	composer install

.PHONY: cs
cs: ## Update Coding Standards
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

.PHONY: clean
clean:
	rm -rf vendor composer.lock db/multiflexi.sqlite src/*/*dataTables*

.PHONY: migration
migration: ## Run database migrations
	cd src ; ../vendor/bin/phinx migrate -c ../phinx-adapter.php ; cd ..

.PHONY: sysmigration
sysmigration: ## Run database migrations using system phinx
	cd src ; /usr/bin/phinx migrate -c /usr/lib/multiflexi/phinx-adapter.php ; cd ..

.PHONY: seed
seed: ## Run database seeds
	cd src ; ../vendor/bin/phinx seed:run -c ../phinx-adapter.php ; cd ..


clean: dbreset
	rm -rf vendor composer.lock src/*/*dataTables*

migration:
	touch changes.sqlite
	cd src ; ../vendor/bin/phinx migrate -c ../phinx-adapter.php ; cd ..

autoload:
	composer update

demodata:
	cd src ; ../vendor/bin/phinx seed:run -c ../phinx-adapter.php ; cd ..

newmigration:
	read -p "Enter CamelCase migration name : " migname ; ./vendor/bin/phinx create $$migname -c ./phinx-adapter.php

newseed:
	read -p "Enter CamelCase seed name : " migname ; ./vendor/bin/phinx seed:create $$migname -c ./phinx-adapter.php

dbreset:
	sudo rm -f db/abraflexi-webhook-acceptor.sqlite* *.sqlite*
	echo > db/abraflexi-webhook-acceptor.sqlite
	chmod 666 db/abraflexi-webhook-acceptor.sqlite
	chmod ugo+rwX db

demo: dbreset migration demodata

redeb:
	 sudo apt -y purge abraflexi-webhook-acceptor; rm ../abraflexi-webhook-acceptor_*_all.deb ; debuild -us -uc ; sudo gdebi  -n ../abraflexi-webhook-acceptor_*_all.deb ; sudo apache2ctl restart

reset:
	vendor/bin/phinx seed:run -c ./phinx-adapter.php  -s Reset

testload:
	cd tests; php ./loadhooks.php

dimage:
	docker build -t vitexsoftware/abraflexi-webhook-acceptor .

drun: dimage
	docker run  -dit --name abraflexi-webhook-acceptorSetup -p 8080:80 vitexsoftware/abraflexi-webhook-acceptor
	firefox http://localhost:8080/abraflexi-webhook-acceptor?login=demo\&password=demo

vagrant:
	vagrant destroy -f
	vagrant up
#	firefox http://localhost:8080/abraflexi-webhook-acceptor?login=demo\&password=demo

