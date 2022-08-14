<?php

//$postdata = json_decode(file_get_contents("php://input"), true);

/**
 * Front controller
 *
 * PHP version 7.0
 */
/**
 * Composer
 */
require dirname(__DIR__) . '/vendor/autoload.php';
foreach (glob(dirname(__DIR__) . "/Core/UpdateHandlers/*.php") as $filename) {
    include_once $filename;
}

use \App\Auth;


/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


/**
 * Sessions
 */
session_start();

if (!Auth::getUser()) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . "/login", true, 303);
    exit;
}
//echo json_encode($_POST);
//exit();
//switch($postdata['update']){//for testing with postman 

switch ($_POST['update']) {

    case "newSupportRequest":
        $supportRequestHandler = new supportRequestHandler();
        $response = $supportRequestHandler->handleRequest($_POST['data']);
        break;
    case "examPrepAnswer":
        $examPrepUpdateHandler = new examPrepUpdateHandler();
        $response = $examPrepUpdateHandler->handleUpdate($_POST['data']);
        break;
    case "getQuizGroupQuestions":
        $quizUpdateHandler = new quizUpdateHandler();
        $response = $quizUpdateHandler->handleRequest($_POST['data']);
        break;
    case "quizAnswer":
        $quizUpdateHandler = new quizUpdateHandler();
        $response = $quizUpdateHandler->handleUpdate($_POST['data']);
        break;
    default:
        $response = array(
            "status" => "Error",
            "message" => "Invalid Request"
        );
}
echo json_encode($response);

