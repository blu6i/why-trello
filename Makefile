# Учебное окружение: nginx + php-fpm + PostgreSQL (Windows / MinGW Make)

ifneq (,$(wildcard .env))
include .env
export
endif

.DEFAULT_GOAL := help
.PHONY: help init build up down ps logs clean composer sh psql pint pint-test

help:
	@echo make init      — создать .env из .env.example
	@echo make build     — собрать образы
	@echo make up        — поднять контейнеры (в фоне)
	@echo make down      — остановить и удалить контейнеры
	@echo make ps        — статус контейнеров
	@echo make logs      — логи всех сервисов (follow)
	@echo make clean     — down -v: удалить volume pgdata (данные будут потеряны)
	@echo make composer  — composer install внутри php (без ошибок прав на Windows)
	@echo make pint      — автоформатирование кода (Laravel Pint)
	@echo make pint-test — проверка стиля кода без исправлений (--test)
	@echo make sh        — shell в php-контейнере
	@echo make psql      — консоль psql к postgres

init:
	@if not exist .env ( copy .env.example .env && echo .env создан ) else ( echo .env уже существует, пропускаю )

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

ps:
	docker compose ps

logs:
	docker compose logs -f

clean:
	docker compose down -v

# На Windows запускаем без -u www-data, чтобы избежать ошибки "touch(): Utime failed: Operation not permitted" на NTFS
composer:
	docker compose exec php composer install

sh:
	docker compose exec php sh

psql:
	docker compose exec postgres psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)

# Автоформатирование кода (Laravel Pint)
pint:
	docker compose exec php ./vendor/bin/pint

# Проверка стиля кода без внесения изменений (--test)
pint-test:
	docker compose exec php ./vendor/bin/pint --test
