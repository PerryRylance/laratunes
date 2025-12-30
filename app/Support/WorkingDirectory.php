<?php

namespace App\Support;

class WorkingDirectory
{
	private static $stack = [];

	public static function push(string $dir): void
	{
		static::$stack[] = getcwd();

		chdir($dir);
	}

	public static function pop(): string
	{
		if (empty(static::$stack))
			throw new \Exception('Directory stack is empty');

		$dir = array_pop(static::$stack);

		chdir($dir);

		return $dir;
	}

	public static function current(): string
	{
		return getcwd();
	}

	public static function within(string $dir, callable $callback): void
	{
		static::push($dir);

		$callback();

		static::pop();
	}
}
