<?php namespace Rznag\MultiLoginRestrictor;

use Config;
use DateTime;
use DB;
use Event;
use Illuminate\Support\ServiceProvider;
use Log;
use Session;
use Rznag\MultiLoginRestrictor\Models\UserLogin;

class MultiLoginRestrictorServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app->bind('rznag::multi-login:make-migration', function($app) {
            return new Commands\MakeMultiloginRestrictorMigrationCommand();
        });
        $this->commands(array(
            'rznag::multi-login:make-migration'
        ));

        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('multi-login-restrictor.php'),
        ], 'config');

        $this->registerListeners();

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['multi-login-restrictor'] = $this->app->share(function($app) {
            return new MultiLoginRestrictor;
        });		

        $this->app->booting(function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('MultiLoginRestrictor', 'Rznag\MultiLoginRestrictor\Facades\MultiLoginRestrictor');
        });
	}


	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('multi-login-restrictor');
	}

    protected function registerListeners()
    {
        // Listen for the login event and add this login time to the user logins table and the user's session object 
        Event::listen(\Illuminate\Auth\Events\Login::class, function($user) {
            Log::info('[Multi-login-restrictor] Logging in user ' . $user->user->id);

            $userLoginsTable = Config::get('multi-login-restrictor.user_logins_table');
            $loginTime = new DateTime;

            $userLogin = new UserLogin([ 'user_id' => $user->user->id, 'login_time' => $loginTime ]);
            $userLogin->save();

            Session::put(Config::get('multi-login-restrictor.login_time_session_key'), $loginTime);
        });

        // Listen for the logout event and remove this user's login info from the logins table
        Event::listen(\Illuminate\Auth\Events\Logout::class, function($user) {
            Log::info('[Multi-login-restrictor] Logging out user ' . $user->user->id);

            $loginTimeSessionKey = Config::get('multi-login-restrictor.login_time_session_key');

            $userLoginsTable = Config::get('multi-login-restrictor.user_logins_table');
            $loginTime = Session::get($loginTimeSessionKey);

            $userLogin = UserLogin::where('user_id', $user->user->id)->where('login_time', $loginTime)->first();
            if ($userLogin)
            {
                $userLogin->delete();
            }

            Session::forget($loginTimeSessionKey);
        });
    }
}
