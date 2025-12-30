<?php

header('Content-type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE')
{
	http_response_code(405);
	echo 'Method not allowed';
	exit;
}

$path = '/root/.olaf';

// Recursive iterator to traverse all subdirectories
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
	RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($iterator as $file)
{
	// Only delete files with .mdb extension
	if ($file->isFile() && strtolower($file->getExtension()) === 'mdb')
	{
		$filePath = $file->getRealPath();
		if (@unlink($filePath))
		{
			echo "Deleted: $filePath\n";
		}
		else
		{
			echo "Failed to delete: $filePath\n";
		}
	}
}
