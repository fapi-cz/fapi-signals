.PHONY: install install-php install-js test test-php test-e2e phpstan phpcs lint-php dc-up dc-down wporg-sync release-prepare

install: install-php install-js

install-php:
	composer install

install-js:
	npm install

test: test-php test-e2e

test-php:
	php vendor/bin/phpunit

test-e2e:
	WP_BASE_URL=http://localhost:8071 WP_ADMIN_USER=test WP_ADMIN_PASS=asdf123jkl; npm run test:e2e

phpstan:
	php vendor/bin/phpstan analyse -c phpstan.neon

phpcs:
	php vendor/bin/phpcs --standard=phpcs.xml

phpcbf:
	php vendor/bin/phpcbf --standard=phpcs.xml

lint-php: phpstan phpcs

dc-up:
	docker compose up -d

dc-down:
	docker compose down

wporg-sync:
	mkdir -p wporg/trunk wporg/assets wporg/tags
	rsync -av --delete \
		--exclude "wporg/" \
		--exclude ".git/" \
		--exclude ".github/" \
		--exclude "node_modules/" \
		--exclude "tests/" \
		--exclude "e2e/" \
		--exclude "test-results/" \
		--exclude "docker-compose.yml" \
		--exclude "Dockerfile" \
		--exclude "README.md" \
		--exclude "SPECIFICATION.md" \
		--exclude "package.json" \
		--exclude "package-lock.json" \
		--exclude "playwright.config.js" \
		--exclude "phpunit.xml" \
		--exclude ".phpunit.result.cache" \
		--exclude ".env" \
		--exclude ".gitignore" \
		--exclude ".cursorignore" \
		./ wporg/trunk/

release-prepare: test wporg-sync
