.PHONY: up up-full down build bash migrate fresh logs test key

## Démarre l'app + nginx (utilise Neon comme base de données)
up:
	docker compose up -d

## Démarre tout, y compris postgres local + redis + worker de queue
up-full:
	docker compose --profile local-db --profile redis --profile queue up -d

## phpMyAdmin branché sur un MySQL conteneurisé (http://localhost:8080)
up-mysql:
	docker compose --profile local-mysql --profile phpmyadmin up -d

## phpMyAdmin branché sur le MySQL de l'hôte (nécessite PMA_HOST=host.docker.internal dans .env)
up-phpmyadmin:
	docker compose --profile phpmyadmin up -d

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