<?php
//Load all dependancies
require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

/**
 * Start the php session
 */
session_start();

/**
 * Routing
 */
$router = new Core\Router();

// Routing Table Definition
$router->add('', ['controller' => 'Home', 'action' => 'index']);
$router->add('login', ['controller' => 'Login', 'action' => 'new']);
$router->add('logout', ['controller' => 'Login', 'action' => 'destroy']);
$router->add('password/reset/{token:[\da-f]+}', ['controller' => 'Password', 'action' => 'reset']);
$router->add('signup/activate/{token:[\da-f]+}', ['controller' => 'Signup', 'action' => 'activate']);
$router->add('Module/details/{token:[\d]+}', ['controller' => 'Module', 'action' => 'details']);
$router->add('ExternalLink/topBanner/{token:[\w]+}', ['controller' => 'ExternalLink', 'action' => 'topBanner']);
$router->add('ExternalLink/bottomBanner/{token:[\w]+}', ['controller' => 'ExternalLink', 'action' => 'bottomBanner']);
$router->add('ExternalLink/navbarLink/{token:[\w]+}', ['controller' => 'ExternalLink', 'action' => 'navbarLink']);
$router->add('Resource/library/{token:[\w]+}', ['controller' => 'Resource', 'action' => 'library']);
$router->add('Feedback/getQuestions/{token:[\d]+}', ['controller' => 'Feedback', 'action' => 'getQuestions']);
$router->add('SlideNotes/exportModuleNotes/{moduleindex:[\d]+}/{typeindex:[\d]+}', ['controller' => 'SlideNotes', 'action' => 'exportModuleNotes']);
$router->add('SlideNotes/previewModuleNotes/{moduleindex:[\d]+}/{typeindex:[\d]+}', ['controller' => 'SlideNotes', 'action' => 'previewModuleNotes']);
$router->add('SlideNotes/exportSessionNotes/{sessionindex:[\d]+}/{typeindex:[\d]+}', ['controller' => 'SlideNotes', 'action' => 'exportSessionNotes']);
$router->add('SlideNotes/previewSessionNotes/{sessionindex:[\d]+}/{typeindex:[\d]+}', ['controller' => 'SlideNotes', 'action' => 'previewSessionNotes']);
$router->add('Message/view/{token:[\d]+}', ['controller' => 'Message', 'action' => 'view']);
$router->add('{controller}/{action}');

//Call Router
$router->dispatch($_SERVER['QUERY_STRING']);
