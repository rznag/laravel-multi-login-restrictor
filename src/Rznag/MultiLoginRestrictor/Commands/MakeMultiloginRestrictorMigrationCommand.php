<?php namespace Rznag\MultiLoginRestrictor\Commands;

use Artisan;
use Config;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeMultiloginRestrictorMigrationCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'multi-login:make-migration';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make a migration to add necessary multi-login restriction fields to users table.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $userLoginsTable = Config::get('multi-login-restrictor.user_logins_table');

        // create the user logins table migration
        Artisan::call('make:migration:schema', [ 'name' => "create_{$userLoginsTable}_table", '--schema' => 'user_id:integer, login_time:timestamp', '--model' => false ]);

        $usersTable = Config::get('multi-login-restrictor.users_table');
        $seatsField = Config::get('multi-login-restrictor.users_num_seats_field');

        // add the logins count to the users table
        Artisan::call('make:migration:schema', [ 'name' => "add_seats_to_{$usersTable}", '--schema' => "{$seatsField}:integer:default(1)", '--model' => false ]);

        $this->info('Migrations created.  Run artisan migrate to complete the install');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			//array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			//array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
