appName = ticket-manager
compose = docker-compose -f docker-compose.yaml -p $(appName)

up: down
	@echo "Starting app..."
	$(compose) up -d
	@echo "Docker images built and started!"

down:
	@echo "Stopping docker compose..."
	$(compose) down
	@echo "Done!"
