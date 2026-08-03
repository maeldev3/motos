.PHONY: up up-mail up-full down build bash migrate fresh logs test key

## Démarre app + nginx + mysql + phpmyadmin (stack complet par défaut)
up:
	docker compose up -d

## + Mailpit (capture des emails envoyés par Laravel)
up-mail:
	docker compose --profile mail up -d

## Démarre absolument tout (mail + redis + queue + postgres alternatif)
up-full:
	docker compose --profile mail --profile redis --profile queue --profile local-postgres up -d

down:
	docker compose down

build:
	docker compose build --no-cache

## Ouvre un shell dans le conteneur app
bash:
	docker compose exec app sh

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

logs:
	docker compose logs -f app

test:
	docker compose exec app php artisan test

key:
	docker compose exec app php artisan key:generate