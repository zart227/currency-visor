# Currency Vision

Веб-приложение для конвертации валют, построенное на Laravel 11 и Vue 3.

![Laravel](https://img.shields.io/badge/Laravel-11.0-FF2D20?style=flat-square&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.0-4FC08D?style=flat-square&logo=vue.js)
![TypeScript](https://img.shields.io/badge/TypeScript-5.0-3178C6?style=flat-square&logo=typescript)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.0-38B2AC?style=flat-square&logo=tailwind-css)

## 🚀 Возможности

- Мгновенная конвертация валют в реальном времени
- Поддержка множества мировых валют
- Современный и отзывчивый интерфейс
- История конвертаций
- Автоматическое обновление курсов валют

## 🛠 Технологический стек

### Backend
- PHP 8.3
- Laravel 11
- MySQL/PostgreSQL
- Redis для кэширования

### Frontend
- Vue 3 с Composition API
- TypeScript
- Tailwind CSS
- Inertia.js
- Pinia для управления состоянием

## 📦 Требования

- PHP >= 8.3
- Composer
- Node.js >= 20.x
- Docker (опционально)

## 🚀 Установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/zart227/currency-visor.git
cd currency-visor
```

2. Установите PHP зависимости:
```bash
composer install
```

3. Установите JavaScript зависимости:
```bash
npm install
```

4. Скопируйте файл окружения:
```bash
cp .env.example .env
```

5. Сгенерируйте ключ приложения:
```bash
php artisan key:generate
```

6. Настройте подключение к базе данных в файле `.env`

7. Выполните миграции:
```bash
php artisan migrate
```

8. Запустите сборку фронтенда:
```bash
npm run dev
```

## 🐳 Запуск через Docker

1. Запустите контейнеры:
```bash
docker compose up -d
```

2. Установите зависимости и выполните миграции:
```bash
docker compose exec app composer install
docker compose exec app php artisan migrate
```

## 🔧 Конфигурация

1. Настройте API ключ для курсов валют в `.env`:
```
CURRENCY_API_KEY=your_api_key
```

2. Настройте кэширование (опционально):
```
CACHE_DRIVER=redis
REDIS_HOST=redis
```

## 📝 Использование

1. Откройте приложение в браузере
2. Выберите исходную валюту
3. Выберите целевую валюту
4. Введите сумму для конвертации
5. Получите результат мгновенно

## 🧪 Тестирование

Запуск тестов:
```bash
php artisan test
```

## 📈 Планы развития

- [ ] Добавление графиков изменения курсов
- [ ] Поддержка криптовалют
- [ ] Мобильное приложение
- [ ] API для внешних интеграций
- [ ] Уведомления об изменении курсов
