# PHP File Service

Docker based Stateless file service application.

#### Features:
- Upload files (chunk based)
- Download files
- Upload complete webhook
- API based access control

## Install

#### Requirements:
- PHP (>8.1)
- Nginx (>1.2)
- PostgreSQL (>14)
- RabbitMQ (>3)

#### Docker-compose
```
docker-compose up -d
docker-compose exec php composer install
```

## Api


- `POST "/chunked-upload"`, See [ChunkedUploadForm.php](https://github.com/zemkogabor/php-file-service/blob/main/src/File/Form/ChunkedUploadForm.php) for details
- `PUT "/chunked-upload-complete"`, See [ChunkedUploadCompleteForm.php](https://github.com/zemkogabor/php-file-service/blob/main/src/File/Form/ChunkedUploadCompleteForm.php) for details
- `GET "/download/([a-zA-Z0-9-]+)"`, See [DownloadForm.php](https://github.com/zemkogabor/php-file-service/blob/main/src/File/Form/DownloadForm.php) for details

## Useful scripts:

Linter
```
docker-compose exec php ./vendor/bin/php-cs-fixer fix --diff --dry-run --config .php-cs-fixer.php --verbose
```

## Thank You
- Special thanks to [@dblaci](https://www.github.com/dblaci) for the technical support.
