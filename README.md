Laravel Multi-Login Restrictor
=============================

Forked from [Laravel Multi-Login Restrictor](https://github.com/jonstavis/laravel-multi-login-restrictor) and refactored for Laravel 5.3

Restrict users on a Laravel 5.3 system from logging in more than a certain number of times.

This package provides a middleware to add to your Laravel routes that will log out the oldest user session for a particular user when he or she is logged in more than a set number of times.

There is a current dev dependency on [Laravel 5 Generators](https://github.com/laracasts/Laravel-5-Generators-Extended).

## Usage

Add the composer definition to your composer.json:

```json
"rznag/multi-login-restrictor": "dev-master"
```

And run `composer update`.  When it is installed, register the service provider in `app/config/app.php` in the `providers` array:

```php
'providers' => array(
        'Rznag\MultiLoginRestrictor\MultiLoginRestrictorServiceProvider',
)        
```
Don't forget to add the [Laravel 5 Generators](https://github.com/laracasts/Laravel-5-Generators-Extended) service provider too.

Publish configuration and review settings in `app/config/packages/rznag/multi-login-restrictor/config.php`:

```
php artisan vendor:publish
```

In particular, make sure the `users_table` property is set to the name of the users table in your application.  You may also change the field on the users table that indicates how many simultaneous logins each user will be allowed.  To do this edit `users_num_seats_field`.

Run the artisan command to generate migrations which will add a `num_seats` field to the users table and a `users_logins` table:

```
php artisan multi-login:make-migration
```

Run the migrations:

```
php artisan migrate
```

Add the `multi-login-restrict` middlware to your `web` middleware:

```php
/**
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middlewareGroups = [
			'web' => [
					\App\Http\Middleware\EncryptCookies::class,
					\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
					\Illuminate\Session\Middleware\StartSession::class,
					\Illuminate\View\Middleware\ShareErrorsFromSession::class,
					\App\Http\Middleware\VerifyCsrfToken::class,
					\App\Http\Middleware\LogLastUserActivity::class,
					\Illuminate\Routing\Middleware\SubstituteBindings::class,
					\Rznag\MultiLoginRestrictor\Middleware\MultiLoginRestrictorMiddleware::class,

			],
```

Users by default will be allowed to login at most one simultaneous time.  You may change the value of `num_seats` for any particular user to allow his account additional simultaneous logins.

If the maximum number of logins is exceeded for a user, the oldest user session that logged in will be logged out and Redirected to 'login' route and a 'global' message will be flashed to the session to inform the logged out user.

