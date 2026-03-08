# Preload
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/preload.svg)](https://packagist.org/packages/laragear/preload)
[![Latest stable test run](https://github.com/Laragear/Preload/workflows/Tests/badge.svg)](https://github.com/Laragear/Preload/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/Preload/graph/badge.svg?token=DPGO1BDJCJ)](https://codecov.io/gh/Laragear/Preload)
[![Maintainability](https://qlty.sh/badges/9009a472-5951-4c48-875f-54c9a991692a/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Preload)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Preload&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Preload)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/9.x/octane#introduction)

Dynamically preload your Laravel application.

This package generates a [PHP preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in.

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **spread the word on social media!**

## Requirements

* PHP 8.3 or later
* Laravel 12 or later
* [Opcache & Preloading enabled](https://www.php.net/manual/en/book.opcache.php) (`ext-opcache`).

## Installation

Require this using Composer into your project

```bash
composer require laragear/preload
```

> [!NOTE]
>
> This package doesn't require the `ext-opcache` extension to install. Just be sure to have it [enabled in your deployment server](https://www.php.net/manual/en/book.opcache.php).

## What is Preloading? Does it make my app FAST?

PHP interpreter needs to read and compile each requested file in your project. When Opcache is enabled, it will keep interpreted files in memory instead of reading them again from the file system, which is miles faster.

Opcache's Preloading allows storing in memory a given list of files when the PHP process starts, before normal execution. This makes the application _faster_ for first requests, as these files to read are already in memory. With [JIT](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.jit), these files are also compiled into byte-code, saving another step.

This package generates a file with a list containing the most accessed files of your application and a script that will load these files on startup. You can point the "loader" script into your `php.ini`:

```ini
opcache.preload_user = 'www-data'
opcache.preload = '/www/app/preload.php';
```

After that, the next time PHP starts, this list of files will be preloaded automatically.

> [!NOTE]
>
> If you're behind a shared server, preloading may be not available for your application. Normally, shared servers also share the same PHP process, and its configuration file (`php.ini`) is not available for modification. Check your server if you're not sure if Laragear Preload should be installed.

## Usage

By default, this package pushes a queued job each 10,000 requests, containing a limited list with the most accessed files of the application. [This condition can be changed](#custom-condition).

First, you should publish the stub script with the `preload:stub` Artisan command. By default, it will copy a stub preloader into the application root directory [by default](#paths).

```bash
php artisan preload:stub

# Stub copied at [/www/app/preload.php]
#
# Remember to edit your [php.ini] file:
# opcache.preload = /www/app/preload.php;
```

This way, you can add the preload file path in your `php.ini` as instructed by the command.

```ini
opcache.preload = '/www/app/preload.php';
```

That's it. At the 10,000th request, the preloader stub will be replaced by a real preload script along with the list of files that should be warmed up by PHP at startup.

## Custom condition

This package includes a simple condition callback: each 10,000 requests, generate a Preloading script.

If this condition is not enough for your application, or you require a custom condition, you can easily create a callback or even an _invokable_ class with your own logic. The callable will be resolved by the application container, and run after the request has been sent to the browser.

Once you create the condition, register it through the `condition()` method of the `Preloader` facade. You can do this in your `App\Providers\AppServiceProvider` or `bootstrap/app.php`.

```php
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Support\Lottery;
use Laragear\Preload\Facades\Preload;

return Application::configure()
    ->registered(function () {
        Preload::condition(function (Request $request) {
            if ($request->user()?->isAdmin()) {
                return false;
            }
            
            return random_int(0, 100) === 50;
        });
    })->create();
```

You may also return a `Illuminate\Support\Lottery` instance for convenience, which is great for testing purposes.

```php
use Illuminate\Support\Lottery;
use Laragear\Preload\Facades\Preload;

Preload::condition(function (Request $request) {
    if ($request->user()?->isAdmin()) {
        return false;
    }
    
    return Lottery::odds(2, 100);
});
```

## Include and exclude

To include or exclude PHP files or entire directory paths from the Preload list, use the `include()` and `exclude()` methods from the `Preload` facade, respectively.

Both methods accept an array of [glob patterns](https://en.wikipedia.org/wiki/Glob_(programming)) or a callback that receives the [Symfony Finder](https://symfony.com/doc/current/components/finder.html) for greater filtering options. On both cases, only `.php` files will be included in the list.

```php
use Laragear\Preload\Facades\Preload;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;

return Application::configure()
    ->booted(function () {
        Preload::include(base_path('/services/**'));
        
        Preload::exclude(function (Finder $find) {
            $find->in(base_path('/temp/'))->contains('class ');
        });
    })
    ->create();
```

> [!IMPORTANT]
> 
> Included files will be _appended_ to the list. This means that exclusion logic will run first. 

### Excluding and including libraries

You can easily exclude or include entire libraries from your application by setting them at the `extra.preload` key of your `composer.json`, respectively.

The `preload` object should contain the library name as key, and `false` to exclude it or `true` to include. If you require fine-tuning, you can use an object with `exclude` and `include` files and a glob pattern for the file or files you wish to exclude or include, respectively. These patterns will be fed to the underlying Symfony Finder.

```json
{
    "extra": {
        "preload": {
            "laragear/meta": {
                "exclude": ["src/Foo/*", "src/Bar/*/**"],
                "include": "src/Request/*"
            },
            "charlesdp/builder": false
        }
    }
}
```

> [!IMPORTANT]
>
> Included libraries will be _appended_ to the list. This means that exclusion logic will run first.

## Configuration

Some people may not be happy with the "default" behaviour. Luckily, you can configure your own way to generate the script.

First publish the configuration file:

```bash
php artisan vendor:publish --provider="Laragear\Preload\PreloadServiceProvider"
```

Let's check the config array:

```php
<?php

return [
    'enabled' => env('PRELOAD_ENABLE'),
    'project_only' => true,
    'memory' => 32,
    'job' => [
        'connection' => env('PRELOAD_JOB_CONNECTION'),
        'queue' => env('PRELOAD_JOB_QUEUE'),
    ],
    'path' => base_path(),
    'use_require' => false,
    'autoload' => base_path('vendor/autoload.php'),
    'ignore_not_found' => true,
];
```

#### Enable

```php
return [
    'env' => env('PRELOAD_ENABLE'),
];
```

By default, a global middleware is registered automatically on production environments. You can forcefully enable or disable this middleware using an environment variable set to `true` or `false`, respectively.

```dotenv
PRELOAD_ENABLE=true
```

### Project Scope

```php
return [
    'project_only' => true,
];
```

Some PHP processes may be shared between multiple projects. To avoid preloading files outside the current project, this is set to `true` by default. Disabling it will allow preloading files regardless of the directory, even outside the project path.

### Memory Limit

```php
return [
    'memory' => 32,
];
```

The memory limit, in MiB (aka "Windows MegaBytes"), of the List. Once this threshold is reached, no more scripts will be included in the list.

For most applications, 32MB is fine, but you may fine-tune it for your project specifically.

> [!NOTE]
>
> This is not Opcache memory limit, as its handled separately.

### Job configuration

```php
return [
    'job' => [
        'connection' => env('PRELOAD_JOB_CONNECTION'),
        'queue' => env('PRELOAD_JOB_QUEUE'),
    ],
];
```

When the job receives the list to persist, it will be dispatched to the connection and queue set here. When `null`, the framework uses the defaults. You should use your `.env` file to set them:

```dotenv
PRELOAD_JOB_CONNECTION=redis
PRELOAD_JOB_QUEUE=low
```

### Paths

```php
return [
    'path' => base_path(),
];
```

This set the directory path where the preloader files should be stored. By default, it uses your project root path.   

If you change the directory path, ensure PHP has permissions to write on it. Whatever you place it, never do it in a public/accessible directory, like `public` or `storage/app/public`.

> [!IMPORTANT]
>
> Double-check your file permissions to avoid failures on production when reading the file.

### Method

```php
return [
    'use_require' => true,
    'autoload' => base_path('vendor/autoload.php'),
];
```

Opcache allows preloading files using `require_once` or `opcache_compile_file()`.

Preload uses `opcache_compile_file()` by default for better manageability on the files preloaded. Some unresolved links may output warnings at startup, but nothing critical.

Using `require_once` will **execute** the files found. By resolving all the links (imports, parent classes, traits, interfaces, etc.) before compiling it, it may output heavy errors on files that shouldn't be executed like plain scripts. Depending on your application, you may want to use one over the other.

If you plan use `require_once`, ensure you have set the correct path to the Composer Autoloader, since it will be used to resolve classes, among other files.

### Ignore not found files

```php
return [
    'ignore_not_found' => true,
];
```

Some files are created by Laravel at runtime and actively cached by Opcache, but on deployment are absent, like [real-time facades](https://laravel.com/docs/facades#real-time-facades) or compiled Blade Views. It's safe to ignore them by default.

You can disable this for any reason, which will throw an Exception if any file is missing, but is recommended leaving it alone unless you know what you're doing.

## Testing

On `testing` environments, the middleware responsible for executing the condition is never registered in the HTTP Kernel, so your test won't mistakenly create a preload script.

You may alternatively force the middleware to not be registered by setting the `PRELOAD_ENABLE` environment variable to `false` in your `phpunit.xml`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <!-- ... -->
    <php>
        <!-- ... -->
        <env name="PRELOAD_ENABLE" value="false"/>
    </php>
</phpunit>
```

## FAQ

* **Can I manually disable the Preloader?**

[Yes](#enable). When disabled, the global middleware that executes the condition doesn't run at all.

* **Do I need to restart PHP after the list is generated?**

No. The files are already in Opcache memory.

* **Why I can't use something like `php artisan preload:generate` instead or a [scheduled job](https://laravel.com/docs/scheduling)?**

Because it requires Opcache statistics from the live application.

Running PHP CLI will gather CLI statistics, different to the web server, which is shows unrealistic statistics.

* **Does this excludes the package itself from the list? Does make a difference?**

No, and it does not. Only the global middleware and condition may be heavily requested, but most of this package files won't.

* **I activated this Preload but my application still doesn't feel _faster_. What's wrong?**

Initial requests _should_ be faster under a preload script. This does not affect Opcache or the whole application performance in any other way.

If you still _feel_ your app is slow, remember to benchmark your app, cache your config and views, check your database queries and API calls, and queue expensive logic, among other things. You can also use [Laravel Octane](https://github.com/laravel/octane).

* **How the list is created?**

Most hit files in descending order. Each file consumes memory, so the list is cut when the cumulative memory usage reaches the [configurable limit](#memory-limit).

When classes with links to files not contained in the list are loaded, PHP will issue some warnings, which is normal and intended.

* **Can I just put all the files in my project?**

You shouldn't. Including all the files of your application may have diminishing returns compared to, for example, only the most requested. Also, it will make the preloading take more time.

You can always benchmark your app yourself to prove this is wrong for your exclusive case.

* **Can I use a custom condition?**

[Yes.](#custom-condition)

* **Can I deactivate the middleware? Or check only XXX status?**

[Yes.](#enable) If you need to check only for a given response status code, you can create a custom middleware.

* **Does the middleware works on unit testing?**

Nope. The middleware is not registered if the application is running under Unit Testing environment.

* **How can I know when a Preload script is successfully generated?**

The `ListGenerated` and `PreloadGenerated` events are fired when the list is generated during a request, and the script is saved through a queued job, respectively.

You can [add a Listener](https://laravel.com/docs/events#registering-events-and-listeners) to dispatch an email or a Slack notification.

## Laravel Octane Compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

Aside from that, the (real) condition callback is always executed each Request using the Service Container, so it can (but shouldn't) resolve a fresh config repository.

## Security

If you discover any security-related issues, please [use the online form](https://github.com/Laragear/Preload/security).

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at the time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011–2026 Laravel LLC.
