.PHONY: build up down restart logs shell db-shell migrate jwt-keys

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

shell:
	docker compose exec php sh

db-shell:
	docker compose exec database psql -U app -d coin_collection

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

jwt-keys:
	docker compose exec php mkdir -p config/jwt
	docker compose exec php openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your_passphrase_here
	docker compose exec php openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_passphrase_here
	docker compose exec php chown www-data:www-data config/jwt/*.pem

cache-clear:
	docker compose exec php php bin/console cache:clear

install:
	docker compose exec php composer install

fresh: build up jwt-keys migrate
	@echo "Application is ready at http://localhost:8080"
