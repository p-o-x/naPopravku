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
- ./vendor/bin/sail up artisan migrate