test:
ifdef method_name
	./vendor/bin/phpunit ./src/piplapis/APITester.php --filter $(method_name)
else
	./vendor/bin/phpunit ./src/piplapis/APITester.php
endif