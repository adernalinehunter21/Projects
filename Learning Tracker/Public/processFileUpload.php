<?php

require dirname(__DIR__) . '/vendor/autoload.php';

foreach (glob(dirname(__DIR__) ."/Core/UpdateHandlers/*.php") as $filename) {
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
    echo json_encode(array(
        "status" => "Error",
        "message" => "You seem to have logged out. Please login and Try Again"
    ));
    exit;
}

//echo json_encode(array("your data"=>$_POST));exit;
//{"upload_original_name":"","upload_custom_name":"Screenshot 2020-06-28 at 7.30.35 PM.png"}
switch($_POST['filePurpose']){
    case "assignmentSubmission":
        $assignmentHandler = new assignmentSubmissionHandler();
        $response = $assignmentHandler->handleRequest($_POST);
        break;
    default:
        $response = array(
            "status" => "Error",
            "message" => "Invalid Request"
        );  
}
echo json_encode($response);
