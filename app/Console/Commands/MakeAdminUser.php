<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class MakeAdminUser extends MakeUserCommand
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:make-admin-user';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Creates an admin user, used by the installation script';

	protected function createUser(): Model&Authenticatable
	{
		$user = parent::createUser();

		$user->update(['is_admin' => true]);

		return $user;
	}
}
