<?php

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createArrayBacked(__DIR__.'/..');
$values = $dotenv->load();

if (isset($values['MEDIA_PATH']))
{
	$output = new ConsoleOutput();
	$style = new OutputFormatterStyle('white', 'red');
	$output->getFormatter()->setStyle('error', $style);
	$output->writeln('<error>MEDIA_PATH must not be set at container start time or run time for tests, remove it from .env and restart the container with --force-recreate</error>');
	exit(1);
}
