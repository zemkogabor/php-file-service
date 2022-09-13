# PHP File Service

Docker based Stateless file service application.

#### Features:
- Upload files (chunk based)
- Download files
- Upload complete webhook - *Development in progress*
- API based access control - *Development in progress*

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

```
- POST "/chunked-upload", See ChunkedUploadForm.php for details
- PUT "/chunked-upload-complete", See ChunkedUploadCompleteForm.php for details
- GET "/download/([a-zA-Z0-9-]+)", See DownloadForm.php for details
```

## Useful scripts:

Linter
```
docker-compose exec php ./vendor/bin/php-cs-fixer fix --diff --dry-run --config .php-cs-fixer.php --verbose
```

## Thank You
- Special thanks to @dblaci for the technical support.
