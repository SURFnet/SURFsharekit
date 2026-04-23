.PHONY: install start dev build clean

# Install dependencies
install:
	npm install

# Start the application
start:
	npm start

# Run development server
dev:
	npm run dev

# Build the application
build:
	npm run build

clean:
	rm -rf node_modules
	rm -f package-lock.json
	npm install

# Clean and reinstall dependencies
clean-start:
	rm -rf node_modules
	rm -f package-lock.json
	npm install
	npm start

# Help command
help:
	@echo "Available commands:"
	@echo "  make install     - Install dependencies"
	@echo "  make start       - Start the application"
	@echo "  make dev         - Run development server"
	@echo "  make build       - Build the application"
	@echo "  make clean       - Clean and reinstall dependencies"
	@echo "  make clean-start - Clean and reinstall dependencies and start the application" 