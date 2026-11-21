COMPOSE := docker compose -f "$(dir $(abspath $(lastword $(MAKEFILE_LIST))))docker-compose.yaml"

.PHONY: lint version composer-version

lint:
	$(COMPOSE) run --build --rm -T php find . -path ./vendor -prune -o -type f -name '*.php' -exec php -l {} +

version:
	$(COMPOSE) run --build --rm -T php php --version

composer-version:
	$(COMPOSE) run --build --rm -T php composer --version
