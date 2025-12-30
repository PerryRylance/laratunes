<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ElevateUserToAdmin extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:elevate-user-to-admin {email}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$email = $this->argument('email');

		User::whereEmail($email)
			->firstOrFail()
			->update([
				'is_admin' => true,
			]);

		$this->info("Made $email admin");
	}
}
