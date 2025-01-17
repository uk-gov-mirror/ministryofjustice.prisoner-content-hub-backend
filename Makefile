build-deps:
	composer clear-cache
	composer install --no-dev --no-ansi --no-scripts --prefer-dist --ignore-platform-reqs --no-interaction --no-autoloader

build:
	docker build -t prisoner-content-hub-backend .

clean:
	rm -rf modules/contrib

push:
	@docker login -u $(DOCKER_USERNAME) -p $(DOCKER_PASSWORD)
	docker tag prisoner-content-hub-backend mojdigitalstudio/prisoner-content-hub-backend:build-$(CIRCLE_BUILD_NUM)
	docker tag prisoner-content-hub-backend mojdigitalstudio/prisoner-content-hub-backend:latest
	docker push mojdigitalstudio/prisoner-content-hub-backend:build-$(CIRCLE_BUILD_NUM)
	docker push mojdigitalstudio/prisoner-content-hub-backend:latest

push-preview:
	@docker login -u $(DOCKER_USERNAME) -p $(DOCKER_PASSWORD)
	docker tag prisoner-content-hub-backend mojdigitalstudio/prisoner-content-hub-backend:preview
	docker push mojdigitalstudio/prisoner-content-hub-backend:preview

install-drupal:
	vendor/bin/drush site-install prisoner_content_hub_profile --existing-config -y

run-tests:
	echo "Running tests on existing site"
	vendor/bin/phpunit --testsuite=existing-site --log-junit ~/phpunit/junit-unit.xml --verbose
