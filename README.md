Тестовое задание https://spiky-sky-d11.notion.site/backend-web-cloud-storage-api-88c35e44afe94054a7608cfb5a095a8c

Файловый сервер, зарработанный в соответствии с тех. заданием
Реализованные дополнительные функции:
- получить размер всех файлов внутри директории
- получить размер всех файлов на диске
- генерация уникальной публичной ссылки на файл

коллекция postman napopravku.postman_collection.json

Инструкция запуска:
- git clone https://github.com/p-o-x/naPopravku
- cd naPopravku
- cat .env.example > .env
- docker run --rm --interactive --tty -v $(pwd):/app composer install
- ./vendor/bin/sail up -d
Если запустить миграции до того как mysql инициализирует базу,
то может выдать ошибку,значит нужно попробовать через 30 секунд ещё раз
- ./vendor/bin/sail artisan migrate
- cd ..
- chmod 777 naPopravku -R

Сервис запущен по адресу http://84.38.183.114
в коллекции postman примеры запросов к сервису