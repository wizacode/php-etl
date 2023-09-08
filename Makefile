dev:
	nix shell github:loophp/nix-shell --impure

phpcs:
	vendor/bin/phpcs -n

phpcbf:
	vendor/bin/phpcbf

phpstan:
	vendor/bin/phpstan clear-result-cache
	php -d memory_limit=2G vendor/bin/phpstan analyse

phpcsfixer:
	vendor/bin/php-cs-fixer fix --dry-run --allow-risky=yes --diff

test:
	vendor/bin/phpunit --testdox

infection:
	vendor/bin/infection

PHPUNIT_REPORT_PATH = /tmp/phpunit_coverage_report
coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit \
		--coverage-clover cov.xml \
		--coverage-filter src \
		--coverage-html $(PHPUNIT_REPORT_PATH)
	xdg-open $(PHPUNIT_REPORT_PATH)/index.html
