<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';

use \Symfony\Component\Dotenv\Dotenv;

(function () {

	$dotenv = new Dotenv(true);
	$envFiles = [
		__DIR__ . '/../.env',
		__DIR__ . '/../.env.dev'
	];

	foreach ($envFiles as $fileName) {
		if (file_exists($fileName)) {
			$dotenv->load($fileName);
		}
	}

})();
