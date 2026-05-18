# =========================================================
# Makefile — Symfony Blog App (Docker)
# =========================================================

# Build the Docker image
build:
	docker compose build

# Start the containers
up:
	docker compose up

# Start the containers in detached mode (background)
up-d:
	docker compose up -d

# Stop the containers
down:
	docker compose down

# Rebuild and restart the containers
restart: down build up

# Open a shell inside the running container
shell:
	docker compose exec app bash

# Create a new user
create-user:
	docker compose exec app php bin/console app:create-user

# Clear the Symfony cache
cache-clear:
	docker compose exec app php bin/console cache:clear

# List all registered routes
routes:
	docker compose exec app php bin/console debug:router

# Inspect users in the database
db-users:
	docker compose exec app php bin/console dbal:run-sql "SELECT email, username FROM user"

# Inspect all posts in the database
db-posts:
	docker compose exec app php bin/console dbal:run-sql "SELECT * FROM post"

# Follow container logs
logs:
	docker compose logs -f