phpstan:
	vendor/bin/phpstan clear-result-cache
	vendor/bin/phpstan analyse

test:
	vendor/bin/phpunit --testdox
