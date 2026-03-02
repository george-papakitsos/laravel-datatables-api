# jQuery DataTables API for Laravel
This package handles server-side implementation of jQuery DataTables Plugin by using Laravel's Eloquent ORM.

## Requirements
- [PHP >= 8.1](https://www.php.net/)
- [Laravel >= 10.0](https://laravel.com/)
- [jQuery DataTables >= 1.10](https://datatables.net/)

## Installation
Require the package with composer.
### Laravel version >= 10
```shell
composer require gpapakitsos/laravel-datatables-api
```
### Laravel version <= 9
```bash
composer require gpapakitsos/laravel-datatables-api "~1.12"
```

Laravel uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider.
### Laravel without auto-discovery:
If you don't use auto-discovery, add the ServiceProvider to the providers array in config/app.php
```php
GPapakitsos\LaravelDatatables\DatatablesServiceProvider::class,
```

#### Copy the package config file to your local config folder with the publish command:
```shell
php artisan vendor:publish --provider="GPapakitsos\LaravelDatatables\DatatablesServiceProvider"
```
