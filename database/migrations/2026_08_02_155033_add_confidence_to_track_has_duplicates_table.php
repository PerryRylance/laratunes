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
		Schema::table('track_has_duplicates', function (Blueprint $table) {
			$table->unsignedInteger('confidence')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('track_has_duplicates', function (Blueprint $table) {
			$table->dropColumn('confidence');
		});
	}
};
