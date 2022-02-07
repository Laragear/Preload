![Braden Collum - Unsplash (UL) #9HI8UJMSdZA](https://images.unsplash.com/photo-1461896836934-ffe607ba8211?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/preload.svg?style=flat-square)](https://packagist.org/packages/laragear/preload) [![License](https://poser.pugx.org/laragear/preload/license)](https://packagist.org/packages/darkghosthunter/preloader)
![](https://img.shields.io/packagist/php-v/laragear/preload.svg)
 ![](https://github.com/Laragear/Preload/workflows/PHP%20Composer/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/Laragear/Preload/badge.svg?branch=master)](https://coveralls.io/github/Laragear/Preload?branch=master)

# Laravel Preload

Dynamically preload your Laravel application. 

This package generates a [PHP preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in.

## Requirements

* Laravel 9.x or later
* PHP 8.0 or later
* [Opcache & Preloading enabled](https://www.php.net/manual/en/book.opcache.php) (`ext-zend-opcache`).

## Installation

Require this using Composer into your project

```bash
composer require darkghosthunter/laraload
```

> This package doesn't require the `ext-zend-opcache` extension to install. Just be sure to have it [enabled in your deployment server](https://www.php.net/manual/en/book.opcache.php).

## What is Preloading? What does this?

By default, PHP needs to read, interpret and compile each requested file in your project. When Opcache is enabled, it will keep interpreted files in memory to avoid reading them again from the file system.

Opcache preloading stores in memory a given list of files when the PHP process starts, before normal execution. This makes the application _faster_ during the first requests, as these files to read are already in memory.

This package generates a preload file with the most accessed files of your application. Once done, you can point the generated list into your `php.ini`:

```ini
opcache.preload = 'www/app/preload.php';
```

After that, the next time PHP starts, this list of files will be preloaded automatically.

## Usage

By default, this package pushes a queued job data each 10,000 requests, containing a list of all the most accessed files of the application.

First, since you will start with no script generated, create an empty preload list using the `preload:placeholder` command.

```bash
php artisan preload:placeholder

# Generating a preload placeholder at: /www/app/preload.php
#
# Empty preload stub generated
# Remember to edit your [php.ini] file:
# opcache.preload = '/www/app/preload.php';
```

> The command won't rewrite another placeholder file if it exists. You can force the operation using `--force`. 

Add the preload file path in your `php.ini`:

```ini
opcache.preload = '/www/app/preload.php';
```

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
    'condition' => [
        'store' => null,
        'hits' => 10000,
        'key' => 'preload|request_count'
    ],
    'project_only' => true,
    'memory' => 32,
    'job' => [
        'connection' => env('PRELOAD_JOB_CONNECTION'),
        'queue' => env('PRELOAD_JOB_QUEUE'),
    ],
    'path' => base_path('preload.php'),
    'use_require' => false,
    'autoload' => base_path('vendor/autoload.php'),
    'ignore_not_found' => true,
];

```

#### Enable

```php
<?php

return [
    'enable' => env('PRELOAD_ENABLED'),
];
```

By default, a global middleware is registered automatically on production environments. You can forcefully enable or disable this middleware using an environment variable set to `true` or `false`, respectively.

```dotenv
PRELOAD_ENABLED=true
```

#### Condition

```php
<?php

return [
    'condition' => [
        'store' => null,
        'hits' => 10000,
        'key' => 'preload|request_count'
    ],
];
```

This package comes with a _simple_ condition callback that returns `true` when it counts 10,000 successful requests. 

You can define [your own condition](#custom-condition). This array is sent to the callback as the `$options` parameter.

```php
use Illuminate\Http\Request;
use Laragear\Preload\Facades\Preload;

Preload::condition(function (array $options) {
    return random_int(0, $options['max']) === (int) ceil($options['max'] / 2);
});
```

### Project Scope

```php
<?php

return [
    'project_only' => true,
];
```

Some PHP processes may be shared between multiple projects. To avoid preloading files outside the current project, this is set to `true` by default. Disabling it will allow preloading files regardless of the directory.

#### Memory Limit

```php
<?php

return [
    'memory' => 64,
];
```

The memory limit, in MegaBytes, of the List. Once this threshold is reached, no more scripts will be included in the list. 

For most applications, 32MB is fine, but you may fine-tune it for your project specifically.

> This is not Opcache memory limit, as its handled separately.

#### Job configuration

```php
<?php

return [
    'job' => [
        'connection' => null,
        'queue' => null,
    ],
];
```

Once the job to persist the list is dispatched, it uses the queue and connection set here. When `null`, the framework uses the default connection and/or queue.

#### Path

```php
<?php

return [
    'path' => '/var/www/preloads/my_preload.php',
];
```

By default, the script is saved in your project root path, but you can change the filename and path to save it as long PHP has permissions to write on it. Whatever you place it, never place it in a publicly-accessible directory, like `public` or `storage/app/public`.

> Double-check your file permissions to avoid failures on production.

#### Method

```php
<?php

return [
    'use_require' => true,
    'autoload' => base_path('vendor/autoload.php'),
];
```

Opcache allows preloading files using `require_once` or `opcache_compile_file()`.

Preload uses `opcache_compile_file()` for better manageability on the files preloaded. Some unresolved links may output warnings at startup, but nothing critical.

Using `require_once` will **execute** all files. By resolving all the links (imports, parent classes, traits, interfaces, etc.) before compiling it, it may output heavy errors on files that shouldn't be executed. Depending on your application, you may want to use one over the other.

If you plan use `require_once`, ensure you have set the correct path to the Composer Autoloader, since it will be used to resolve classes, among other files.

### Ignore not found files

```php
<?php

return [
    'ignore_not_found' => true,
];
```

Some files are created by Laravel at runtime and actively cached by Opcache, but on deployment are absent, like [real-time facades](https://laravel.com/docs/facades#real-time-facades). Ignoring them is safe and enabled by default.

You can disable this for any reason, which will throw an Exception if any file is missing, but is recommended leaving it alone unless you know what you're doing.

### Exclude and append files

Exclude and append files from directories by just issuing an array of **directory paths** in your App Service Provider, through the `Preload` facade. 

You can also use a function that receives the [Symfony Finder](https://symfony.com/doc/current/components/finder.html), which is included in this package, for greater filtering options.

```php
use Symfony\Component\Finder\Finder;
use Illuminate\Support\ServiceProvider;
use Laragear\Preload\Facades\Preload;

class AppServiceProvider extends ServiceProvider
{
    // ...
    
    public function boot()
    {
        Preload::append(function (Finder $find) {
            $find->in(base_path('foo/'))
                ->contains('class ')
                ->name('*.php');
        });
        
        Preload::exclude(
            base_path('/bar/'),
            base_path('/baz/Http/'),
        );
    }
}
```

## Custom condition

This package includes a simple condition callback: return `true` each 10,000 requests. The number of requests, the cache to use and the key for the cache can be set in the [`condition` section of the configuration](#condition).

On some scenarios, you may want to use a random seed, or generate a list periodically. You can create your own condition by setting the callback in your `AppServiceProvider`.

```php
use Laragear\Preload\Facades\Preload;
use Illuminate\Support\Facades\Cache;

public function register()
{
    Preload::condition(function () {
        if (Cache::has('preload was generated for yesterday')) {
            return false;
        }
        
        Cache::put('preload was generated for yesterday', true, now()->endOfDay());
        
        return true;
    });
    
    // ...
}
```

### FAQ

* **Can I manually disable Preloader?**

[Yes.](#enable) This basically disables the global middleware.

* **Do I need to restart PHP after the list is generated?**

No, as the list generated is already in Opcache memory.

* **The package returned errors when I used it!**

Check you're using the [latest PHP stable version](https://www.php.net/supported-versions.php), and Opcache is enabled. Also, check the script path is writable. All PHP errors are logged, so check it out.

If you're sure this is an error by the package, [open an issue](https://github.com/Laragear/Preload/issues/new) with full details and stack trace.

* **Why I can't use something like `php artisan preload:generate` instead or a [scheduled job](https://laravel.com/docs/scheduling)?**

Opcache is not enabled when using PHP CLI, and if it is, it gathers CLI statistics. You must let this package gather real statistics from a live application.

* **Does this excludes the package itself from the list? Does make a difference?**

No, and it does not. Only the middleware may be heavily requested (as it's global), but most of this package files won't.

* **I activated this Preload but my application still doesn't feel _faster_. What's wrong?**

Initial requests should be fast once the preload script is loaded. This does not affect Opcache or the whole application performance in any other way.

If you still _feel_ your app is slow, remember to benchmark your app, cache your config and views, check your database queries and API calls, and queue expensive logic, among other things. You can also use [Laravel Octane](https://github.com/laravel/octane).

* **How the list is created?**

Basically: the most hit files in descending order. Each file consumes memory, so the list is _soft-cut_ when the cumulative memory usage reaches the limit (32MB by default).

* **You said "_soft-cut_", why is that?**

Each file is loaded using `opcache_compile_file()`. If the last file is a class with links outside the list, PHP will issue some warnings, which is normal and intended, but it won't compile the linked files if these were not added before.

* **Can I just put all the files in my project?**

You shouldn't. Including all the files of your application may have diminishing returns compared to, for example, only the most requested. You can always benchmark your app yourself to prove this is wrong for your exclusive case.

* **Can I use a custom condition?**

[Yes.](#custom-condition)

* **Can I deactivate the middleware? Or check only XXX status?**

[Yes.](#enable) If you need to check only for a given response status code, you should make your own global middleware.

* **Does the middleware works on unit testing?**

Nope. The middleware is not registered if the application is running under Unit Testing environment.

* **How can I know when a Preload script is successfully generated?**

The `ListGenerated` and `ScriptStored` events are fired when the list is generated during a request, and the script is saved through a queued job, respectively.

You can [add a Listener](https://laravel.com/docs/events#registering-events-and-listeners) to dispatch an email or a Slack notification.
