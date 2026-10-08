# customization

PHPUNIT = vendor/bin/phpunit

# do not edit the following lines

vendor:
	@composer install

# testing

.PHONY: test-dependencies
test-dependencies: vendor

.PHONY: test
test: test-dependencies
	@rm -rf tests/sandbox/*
	@$(PHPUNIT) $(ARGS)

.PHONY: test-db
test-db: test-dependencies
	@rm -rf tests/sandbox/*
	@$(PHPUNIT) --group db $(ARGS)

.PHONY: test-coverage
test-coverage: test-dependencies
	@mkdir -p build/coverage
	@XDEBUG_MODE=coverage $(PHPUNIT) --coverage-html build/coverage

.PHONY: test-coveralls
test-coveralls: test-dependencies
	@mkdir -p build/logs
	@XDEBUG_MODE=coverage $(PHPUNIT) --coverage-clover build/logs/clover.xml

.PHONY: test-cleanup
test-cleanup:
	@rm -rf tests/sandbox/*

.PHONY: test-container
test-container: test-container-84

.PHONY: test-container-84
test-container-84:
	@-docker compose run --rm app84 bash
	@docker compose down -v

.PHONY: test-container-db
test-container-db: test-container-db-sqlite

.PHONY: test-container-db-sqlite
test-container-db-sqlite:
	@docker compose run --rm \
		-e ACTIVERECORD_DSN='sqlite::memory:' \
		app84 make test-db
	@docker compose down -v

.PHONY: test-container-db-pgsql
test-container-db-pgsql:
	@docker compose run --rm \
		-e ACTIVERECORD_DSN='pgsql:host=postgres;dbname=postgres' \
		-e ACTIVERECORD_USERNAME='postgres' \
		-e ACTIVERECORD_PASSWORD='postgres' \
		app84 make test-db
	@docker compose down -v

.PHONY: test-container-db-mysql
test-container-db-mysql:
	@docker compose run --rm \
		-e ACTIVERECORD_DSN='mysql:host=mysql;dbname=activerecord' \
		-e ACTIVERECORD_USERNAME='root' \
		-e ACTIVERECORD_PASSWORD='root' \
		app84 make test-db
	@docker compose down -v

.PHONY: lint
lint:
	#@XDEBUG_MODE=off phpcs -s # broken with property hooks
	@XDEBUG_MODE=off vendor/bin/phpstan --memory-limit=-1
