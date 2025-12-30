<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('tracks', function (Blueprint $table) {
			$table->id();
			$table->string('title')->nullable();
			$table->string('artist')->nullable();
			$table->string('path');
			$table->string('hash'); // TODO: Unique?
			$table->integer('plays')->default(0);
			$table->timestamp('last_played_at')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('tracks');
	}
};
