#!make
########################## Variables #####################
DOCKER		    ?= docker
DOCKER_COMPOSE  ?= docker-compose
CWD             := $(shell pwd | sed 's/ /\\ /g')
UID				:= $(shell id -u)
GID				:= $(shell id -g)

##########################################################

##### Makefile related #####
.PHONY: composer composer_dump_autoload composer_update docker-build

default: help

##@ Composer
composer: ## Start Composer
	@echo ""
	@echo "\033[92mRun the 'docker-compose' with composer console \033[0m"
	@echo ""
	$(DOCKER_COMPOSE) composer  --env-file .dev.env --file docker-compose-dev.yml run

composer_update: ## Start composer
	@echo ""
	@echo "\033[92mRun the 'docker-compose' with composer console \033[0m"
	@echo ""
	$(DOCKER_COMPOSE) --env-file .dev.env --file docker-compose-dev.yml \
 		run --entrypoint "composer update" composer

composer_dump_autoload: ## Start composer
	@echo ""
	@echo "\033[92mRun the 'docker-compose' with composer console \033[0m"
	@echo ""
	$(DOCKER_COMPOSE) --env-file .dev.env --file docker-compose-dev.yml \
		run --entrypoint "composer dump-autoload" composer

##@ Docker related

docker-build: ## Start application
	@echo ""
	@echo "\033[92mStarting the 'docker-compose' infrastructure and services...\033[0m"
	@echo ""
	$(DOCKER) build --rm  -t "apigility-tools-cli:2" $(CWD)


##@ Main Targets


info: ## Show info
	@echo ""
	@echo "\033[92mSetup Info\033[0m"
	@echo ""
	$(DOCKER) ps -a

clean: ## Destroy the system
	@echo ""
	@echo "\033[92mDestroying the 'docker-compose' infrastructure and services...\033[0m"
	@echo ""
	$(DOCKER_COMPOSE) rm -fsv
	$(DOCKER_COMPOSE) down --remove-orphans

##@ Help

help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-40s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

