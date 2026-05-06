fix-permissions:
	docker compose run --rm php chown -R $(shell id -u):$(shell id -g) .
