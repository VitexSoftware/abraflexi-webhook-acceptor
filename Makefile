package=abraflexi-webhook-acceptor
repoversion=$(shell LANG=C aptitude show $(package)| grep Version: | awk '{print $$2}')
nextversion=$(shell echo $(repoversion) | perl -ne 'chomp; print join(".", splice(@{[split/\./,$$_]}, 0, -1), map {++$$_} pop @{[split/\./,$$_]}), "\n";')

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

postinst:
	DEBCONF_DEBUG=developer /usr/share/debconf/frontend /var/lib/dpkg/info/multi-flexibee-setup.postinst configure $(nextversion)

redeb:
	 sudo apt -y purge abraflexi-webhook-acceptor; rm ../abraflexi-webhook-acceptor_*_all.deb ; debuild -us -uc ; sudo gdebi  -n ../abraflexi-webhook-acceptor_*_all.deb ; sudo apache2ctl restart

deb:
	debuild -i -us -uc -b

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

release:
	echo Release v$(nextversion)
	docker build -t vitexsoftware/abraflexi-webhook-acceptor:$(nextversion) .
	dch -v $(nextversion) `git log -1 --pretty=%B | head -n 1`
	debuild -i -us -uc -b
	git commit -a -m "Release v$(nextversion)"
	git tag -a $(nextversion) -m "version $(nextversion)"
	docker push vitexsoftware/abraflexi-webhook-acceptor:$(nextversion)
	docker push vitexsoftware/abraflexi-webhook-acceptor:latest


