<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Use the HOST variable if available to define prefix
$prefix = 'pim-behat';

if (isset($_SERVER['HTTP_HOST'])) {
    $prefix .= '-'.$_SERVER['HTTP_HOST'];
}

$loader = new ApcClassLoader($prefix, $loader);
$loader->register(true);

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('behat4', false);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
