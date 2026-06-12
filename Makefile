.PHONY: install up down migrate test ci

install:
	composer install

up:
	docker compose up --build

down:
	docker compose down

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

test:
	vendor/bin/phpunit

ci: install test
