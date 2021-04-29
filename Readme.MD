Dotenv Component
================

Mim Dotenv parses `.env` files to make environment variables stored in them
accessible via `$_SERVER` or `$_ENV`.

```
$ composer require mim/dotenv
```

```php
use Mim\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// you can also load several files
$dotenv->load(__DIR__.'/.env', __DIR__.'/.env.dev');

// overwrites existing env variables
$dotenv->overload(__DIR__.'/.env');

// loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
$dotenv->loadEnv(__DIR__.'/.env');
```