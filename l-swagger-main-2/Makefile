appName = swagger
compose = docker-compose -f docker-compose-local.yml -p $(appName)

up: down
	@echo "Building (when required) and starting docker images..."
	$(compose) up -d
	@echo "Docker images built and started!"

down:
	@echo "Stopping docker compose..."
	$(compose) down
	@echo "Done!"