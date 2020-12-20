<?php declare(strict_types = 1);

require_once __DIR__ . '/../app/bootstrap.php';

$container = require __DIR__ . '/../app/container.php';

use \Slim\Factory\AppFactory;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// create and add middleware
$app->add($container->get(\SulacoTech\PSR7Sessions\SessionMiddleware::class));
$app->add(\Slim\Views\TwigMiddleware::createFromContainer($app, \Slim\Views\Twig::class));

// basic example
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {

	$db = $this->get(\OpenDBAL\Connection::class);
	$now = $db->fetchColumn("select now()");

	// get session
	$session = $request->getAttribute(\SulacoTech\PSR7Sessions\SessionMiddleware::SESSION_ATTRIBUTE);

	// read and update session's data
	//$counter = $session->get('counter', 0);
	//$session->set('counter', ++ $counter);

	// same instructions using array access style
	$counter = $session['counter'] ?? 0;
	$session['counter'] = ++ $counter;

	// make a response
	//$response->getBody()->write("Hello, {$args['name']}! This page is visited $counter times. Now is $now");

	// same instructions for twig template
	$view = $this->get(\Slim\Views\Twig::class);
	return $view->render($response, 'hello.twig', [
        'name' => $args['name'],
		'counter' => $counter,
		'now' => $now,
    ]);

	return $response;
});

// run application
$app->run();
