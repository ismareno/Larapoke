![
Yerlin Matu - Unslash (UL) #GtwiBmtJvaU](https://images.unsplash.com/photo-1513360371669-4adf3dd7dff8?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Stable Version](https://poser.pugx.org/darkghosthunter/larapoke/v/stable)](https://packagist.org/packages/darkghosthunter/larapoke) [![License](https://poser.pugx.org/darkghosthunter/larapoke/license)](https://packagist.org/packages/darkghosthunter/larapoke)
![](https://img.shields.io/packagist/php-v/darkghosthunter/larapoke.svg)
 [![Build Status](https://travis-ci.com/DarkGhostHunter/Larapoke.svg?branch=master)](https://travis-ci.com/DarkGhostHunter/Larapoke) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Larapoke/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Larapoke?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/5c93490206b5d426c842/maintainability)](https://codeclimate.com/github/DarkGhostHunter/Larapoke/maintainability) [![Test Coverage](https://api.codeclimate.com/v1/badges/5c93490206b5d426c842/test_coverage)](https://codeclimate.com/github/DarkGhostHunter/Larapoke/test_coverage)

# Larapoke

Keep your forms alive, avoid `TokenMismatchException` by gently poking your Laravel app.

## Requirements

* PHP >= 7.1.3
* Laravel 5.7

## Installation

Require this package into your project using Composer:

```bash
composer require darkghosthunter/larapoke
```

## How does it work?

Larapoke pokes your App with a HEAD request to the `/poke` route at given intervals. In return, the route sends an `HTTP 204` status code, which is an OK Response without body. This amounts to **barely 800 bytes sent!**

## Usage

There are three ways to turn on Larapoke in your app. 

* `auto` (enabled by default)
* `middleware`
* `manual`

You can change the default mode using your environment file:

```dotenv
LARAPOKE_MODE=auto
```

### `auto`

Just install this package and *look at it go*. This will push a global middleware that will look into all your Responses content where:

a) an input where `csrf` token is present, or
b) a header where `csrf-token` is present.

If there is any match, this will inject the Larapoke script, that will be in charge to keep the forms alive, just before the `</body>` tag.

> It's recommended to the other modes if your application has many routes or Responses with a lot of text.

### `middleware`

This will disable the global middleware, allowing you to use the `larapoke` middleware only in the routes you explicitly decide.

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('register', 'Auth\RegisterController@showForm')
    ->middleware('larapoke');
```

This will forcefully inject the script, even if there is no form, into the route. You can also apply this to a [route group](https://laravel.com/docs/routing#route-groups).

Since a route group may contain routes without any form, you can add the `detect` option to the middleware which will scan the Response for a form and inject the script only if it finds one.

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('informationForms')
    ->middleware('larapoke:detect')
    ->group(function () {
        
        Route::get('register', 'Auth\RegisterController@showForm');
        Route::get('status', 'Auth\RegisterController@status');
        
    });
```

### `blade`

The `blade` allows you to use the `@larapoke` directive to inject the script anywhere in your view, keeping the forms of that Response alive.

```html
<form action="/login" method="post">
    @csrf
    @larapoke
    <input type="text" name="username" required>
    <input type="password" name="password" required>
    <button type="submit">Log me in!</button>
</form>

<form action="/password" method="post">
    @csrf
    @larapoke
    <input type="email" name="email" required>
    <button type="submit">I forgot my password!</button>
</form>
```

Don't worry if you use many `@larapoke` directives in your view; the script will intelligently use only the first occurrence instead of instancing multiple times. 

## Configuration

For fine tuning, you can publish the `larapoke.php` config file.

```bash
php artisan vendor:publish --provider="DarkGhostHunter\Larapoke\LarapokeServiceProvider"
```

Let's examine the configuration array for Larapoke:

```php
<?php return [
    'mode' => env('LARAPOKE_MODE', 'auto'),
    'times' => 4,
    'timeout' => false,
    'poking' => [
        'route' => 'poke',
        'name' => 'larapoke',
        'domain' => null,
        'middleware' => ['web']
    ]
];
```

### Times (Interval)

How many times the poking will be done relative to the global session lifetime. The more times, the shorter the poking interval. The default `4` should be fine for any normal application. 

For example, if our session lifetime is the default of 120 minutes:

- 3 times will poke the application each 40 minutes. 
- 4 times will poke the application each 30 minutes. 
- 5 times will poke the application each 24 minutes.
- 6 times will poke the application each 20 minutes.
- and so on.

So, basically, `session lifetime / times = poking interval`.

### Timeout

When activated, this will allow Larapoke script to guess if the session is expired based on unsuccessful pokes, every two seconds. If the session should be expired by then, it will force a page reload.

This is handy in situations when the user laptop is put to sleep, or loses phone signal. Because the session may expire during this events, when the browser wakes up the page is reloaded to get the new CSRF token. 

### Poking

This is the array relative to the poking route itself.

#### Route

The route (relative to the root URL of your application) that will be using to receive the pokes.

```php
<?php 
return [
    'poking' => [
        'route' => '/dont-sleep'
    ],
];
```

> The poke routes are registered before any set in your application. You *could* rewrite the poke route with your own logic before responding with HTTP 204. 

#### Name

Just the naming of the route. This way you can find the poke route in your app for whatever reason by its name.

```php
<?php 
return [
    'poking' => [
        'name' => 'the-larapoke-route'
    ],
];
```

#### Domains

In case you are using different domains, or a subdomain, it may be convenient to allow this route only under a certain one instead of all domains. A classic example, is to make the poking available at `http://user.myapp.com/poke` but no `http://myapp.com/poke`.

- `null` (default): the poke route will be only applied at the default root domain and **every route and domain** in the app.
- `mydomain.com`: the poke route will be applied only to that domain, like so: `http://mydomain.com/poke`. 

```php
<?php 
return [
    'poking' => [
        'domain' => 'mysubdomain.myapp.com'
    ],
];
```

#### Middleware

The default Larapoke route uses the "web" middleware group, which is the default for handling web requests in a fresh installation. If you are using another group, or want to use a particular middleware, you can modify where here.

```php
<?php 
return [
    'poking' => [
        'middleware' => ['web', 'validates-ip','app-demilitarized-zone']
    ],
];
```

## License

This package is licenced by the [MIT License](LICENSE).