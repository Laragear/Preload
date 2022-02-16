# Preload

Dynamically preload your Laravel application. 

This package generates a [PHP preloading](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.preload) script from your Opcache statistics automatically. No need to hack your way in.

## Keep this package free

[![](.assets/patreon.png)](https://patreon.com/packagesforlaravel)[![](.assets/ko-fi.png)](https://ko-fi.com/DarkGhostHunter)[![](.assets/buymeacoffee.png)](https://www.buymeacoffee.com/darkghosthunter)[![](.assets/paypal.png)](https://www.paypal.com/paypalme/darkghosthunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FPreload&hashtags=PHP,Laravel)**

## Requirements

* Laravel 9.x or later
* PHP 8.0 or later
* [Opcache & Preloading enabled](https://www.php.net/manual/en/book.opcache.php) (`ext-zend-opcache`).

## Installation

Require this using Composer into your project

```bash
composer require laragear/preload
```

> This package doesn't require the `ext-zend-opcache` extension to install. Just be sure to have it [enabled in your deployment server](https://www.php.net/manual/en/book.opcache.php).

## What is Preloading? What does this?

By default, PHP needs to read, interpret and compile each requested file in your project. When Opcache is enabled, it will keep interpreted files in memory to avoid reading them again from the file system.

Opcache preloading stores in memory a given list of files when the PHP process starts, before normal execution. This makes the application _faster_ during the first requests, as these files to read are already in memory. With JIT, these files are also compiled into byte-code.

This package generates a preload file with the most accessed files of your application. Once done, you can point the generated list into your `php.ini`:

```ini
opcache.preload = 'www/app/preload.php';
```

After that, the next time PHP starts, this list of files will be preloaded automatically.

## Usage

By default, this package pushes a queued job data each 10,000 requests, containing a limited list of the most accessed files of the application.

First, since you will start with no script generated, create an empty preload list using the `preload:placeholder` command.

```bash
php artisan preload:placeholder

# Generating a preload placeholder at: /www/app/preload.php
#
# Empty preload stub generated
# Remember to edit your [php.ini] file:
# opcache.preload = '/www/app/preload.php';
```

> The command won't replace the file if it exists. You can force the operation using `--force`. 

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
return [
    'condition' => [
        'store' => null,
        'hits' => 10000,
        'key' => 'preload|request_count'
    ],
];
```

This package comes with a _simple_ condition callback that returns `true` when it counts 10,000 successful requests. This array is sent to the callback as the `$options` parameter, which will be useful if you want to define [your own condition](#custom-condition).

```php
use Illuminate\Http\Request;
use Laragear\Preload\Facades\Preload;

Preload::condition(function (array $options) {
    return random_int(1, $options['max']) < 3;
});
```

### Project Scope

```php
return [
    'project_only' => true,
];
```

Some PHP processes may be shared between multiple projects. To avoid preloading files outside the current project, this is set to `true` by default. Disabling it will allow preloading files regardless of the directory.

#### Memory Limit

```php
return [
    'memory' => 64,
];
```

The memory limit, in MegaBytes, of the List. Once this threshold is reached, no more scripts will be included in the list. 

For most applications, 32MB is fine, but you may fine-tune it for your project specifically.

> This is not Opcache memory limit, as its handled separately.

#### Job configuration

```php
return [
    'job' => [
        'connection' => env('PRELOAD_JOB_CONNECTION'),
        'queue' => env('PRELOAD_JOB_QUEUE'),
    ],
];
```

When the job receives the list to persist, it will be dispatched to the connection and queue set here. When `null`, the framework uses the defaults. You can use your `.env` file to set them:

```dotenv
PRELOAD_JOB_CONNECTION=redis
PRELOAD_JOB_QUEUE=low
```

#### Path

```php
return [
    'path' => base_path('preload.php'),
];
```

By default, the script is saved in your project root path, but you can change the filename and path to save it as long PHP has permissions to write on it. Whatever you place it, never do it in a public/accessible directory, like `public` or `storage/app/public`.

> Double-check your file permissions to avoid failures on production when reading the file.

#### Method

```php
return [
    'use_require' => true,
    'autoload' => base_path('vendor/autoload.php'),
];
```

Opcache allows preloading files using `require_once` or `opcache_compile_file()`.

Preload uses `opcache_compile_file()` for better manageability on the files preloaded. Some unresolved links may output warnings at startup, but nothing critical.

Using `require_once` will **execute** all files. By resolving all the links (imports, parent classes, traits, interfaces, etc.) before compiling it, it may output heavy errors on files that shouldn't be executed like plain scripts. Depending on your application, you may want to use one over the other.

If you plan use `require_once`, ensure you have set the correct path to the Composer Autoloader, since it will be used to resolve classes, among other files.

### Ignore not found files

```php
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

On some scenarios, you may want to use a random seed, or generate a list periodically. You can create your own condition by setting the callback in your `AppServiceProvider`:

```php
use Laragear\Preload\Facades\Preload;
use Illuminate\Support\Facades\Cache;

public function register()
{
    Preload::condition(function () {
        if (Cache::has('preload generated yesterday')) {
            return false;
        }
        
        Cache::put('preload generated yesterday', true, now()->endOfDay());
        
        return true;
    });
    
    // ...
}
```

### FAQ

* **Can I manually disable Preloader?**

[Yes.](#enable) This basically doesn't register the global middleware.

* **Do I need to restart PHP after the list is generated?**

No, the list generated is already in Opcache memory.

* **The package returned errors when I used it!**

Check you're using the [latest PHP stable version](https://www.php.net/supported-versions.php), and Opcache is enabled. Also, check the script path is writable. All PHP errors are logged, so check it out.

If you're sure this is an error by the package, [open an issue](https://github.com/Laragear/Preload/issues/new) with full details and stack trace.

* **Why I can't use something like `php artisan preload:generate` instead or a [scheduled job](https://laravel.com/docs/scheduling)?**

Opcache is not enabled when using PHP CLI, and if it is, it gathers CLI statistics. You must let this package gather real statistics from a live application.

* **Does this excludes the package itself from the list? Does make a difference?**

No, and it does not. Only the global middleware and condition may be heavily requested, but most of this package files won't.

* **I activated this Preload but my application still doesn't feel _faster_. What's wrong?**

Initial requests _should_ be faster under a preload script. This does not affect Opcache or the whole application performance in any other way.

If you still _feel_ your app is slow, remember to benchmark your app, cache your config and views, check your database queries and API calls, and queue expensive logic, among other things. You can also use [Laravel Octane](https://github.com/laravel/octane).

* **How the list is created?**

Basically: the most hit files in descending order. Each file consumes memory, so the list is cut when the cumulative memory usage reaches the limit (32MB by default).

If the last file is a class with links outside the list, PHP will issue some warnings, which is normal and intended, but it won't compile the linked files if these were not added before.

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

The `ListGenerated` and `ScriptStored` events are fired when the list is generated during a request, and the script is saved through a queued job, respectively.

You can [add a Listener](https://laravel.com/docs/events#registering-events-and-listeners) to dispatch an email or a Slack notification.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2022 Laravel LLC.
