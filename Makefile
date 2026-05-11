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

up-prod:
	docker compose -f compose.yaml -f compose.prod.yaml up -d --build

down-prod:
	docker compose -f compose.yaml -f compose.prod.yaml down

fix-permissions:
	docker compose run --rm php chown -R $(shell id -u):$(shell id -g) .

upstream-log:
	git fetch upstream
	git log --oneline upstream/main --not HEAD

upstream-pick:
	git cherry-pick $(COMMIT)
