setup:
	docker compose up -d --build
	docker compose run --rm php composer install
	docker compose restart php worker

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose down && docker compose up -d

build:
	docker compose up -d --build

# Prod targets read .env then .env.local (Symfony-style override) so that
# environment-specific values (PROJECT_URL, APP_SECRET, ...) live in the
# gitignored .env.local and the committed .env stays pristine — no pull conflicts.
ENV_FILES = --env-file .env $(shell test -f .env.local && echo --env-file .env.local)

up-prod:
	docker compose $(ENV_FILES) -f compose.yaml -f compose.prod.yaml up -d --build

down-prod:
	docker compose $(ENV_FILES) -f compose.yaml -f compose.prod.yaml down

fix-permissions:
	docker compose run --rm php chown -R $(shell id -u):$(shell id -g) .

upstream-log:
	git fetch upstream
	git log --oneline upstream/main --not HEAD

upstream-pick:
	git cherry-pick $(COMMIT)
