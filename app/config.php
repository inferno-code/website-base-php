<?php declare(strict_types = 1);

require_once __DIR__ . '/../vendor/autoload.php';

use \Psr\Container\ContainerInterface;

use function DI\factory;
use function DI\create;
use function DI\get;
use function DI\env;

return (function () {

	return [

		Psr\Log\LoggerInterface::class => factory(function (ContainerInterface $c) {

			$loggerName = getenv("MAIN_LOGGER_NAME");
			$loggerTimeZone = getenv("MAIN_LOGGER_TIMEZONE");
			$loggerFileName = __DIR__ . '/../' . getenv("MAIN_LOGGER_FILENAME");
			$loggerLevel = constant('\Monolog\Logger::' . getenv("MAIN_LOGGER_LEVEL"));

			$log = new \Monolog\Logger($loggerName);

			$log->setTimezone(new \DateTimeZone($loggerTimeZone));

			$formatter = new \Monolog\Formatter\LineFormatter(
				\Monolog\Formatter\LineFormatter::SIMPLE_FORMAT,
				\Monolog\Formatter\LineFormatter::SIMPLE_DATE
			);

			$formatter->includeStacktraces(true);

			$stream = new \Monolog\Handler\StreamHandler($loggerFileName, $loggerLevel);
			$stream->setFormatter($formatter);

			$log->pushHandler($stream);
			$log->pushHandler(new \Monolog\Handler\FirePHPHandler());

			return $log;

		}),

		\Slim\Views\Twig::class => function (ContainerInterface $container) {

			$path = __DIR__ . '/../' . getenv("TWIG_TEMPLATES");

			$options = [
				'cache_enabled' => getenv("TWIG_CAHCE_ENABLED") === 'true' ? true : false,
	        	'cache_path' => __DIR__ . '/../' . getenv("TWIG_CAHCE_DIRECTORY"),
			];

			$options['cache'] = $options['cache_enabled'] ? $options['cache_path'] : false;

			$twig = \Slim\Views\Twig::create([ $path ], $options);

			return $twig;
		},

		\OpenDBAL\Connection::class => factory(function (ContainerInterface $c) {

			$url = getenv("DATABASE_URL");

			$conn = new OpenDBAL\PDO\PDOConnection(
				$url,
				$c->get(Psr\Log\LoggerInterface::class)
			);

//			$conn->query("set client_encoding = 'utf-8'");
//			$conn->query("set time zone 'UTC-3'");

		    return $conn;

		}),

		\SulacoTech\PSR7Sessions\SessionMiddleware::class => factory(function (ContainerInterface $c) {

			// prepare configuration
			$sessionsDirectory = __DIR__ . '/../' . getenv('SESSION_DIRECTORY');
			$sessionName = getenv('SESSION_NAME');
			$sessionsExpirationTime = (int) getenv('SESSION_EXPIRATION_TIME'); // in seconds
			$config = new \SulacoTech\PSR7Sessions\SessionFileStorageConfiguration($sessionsDirectory, $sessionName, $sessionsExpirationTime);

			// create storage with some configuration
			$sessionStorage = new \SulacoTech\PSR7Sessions\SessionFileStorage($config);

			// call garbage collector
			$sessionStorage->gc();

			// create and add middleware
			return new \SulacoTech\PSR7Sessions\SessionMiddleware($sessionStorage);

		}),

	];

})();
