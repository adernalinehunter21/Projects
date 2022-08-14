<?php

namespace App\Controllers;

use App\Models\Files;

/**
 * FAQ controller
 *
 * PHP version 7.0
 */
class File extends Authenticated {

    public function removeUnassociatedAction() {
        $internal_name = $_POST['internal_name'];
        $uploaded_purpose = $_POST['uploaded_purpose'];
        $user_id = $_SESSION['user_id'];
        switch ($uploaded_purpose) {
            case 'MESSAGE_ATTACHMENT':
                $response = Files::removeFileBeingAttached($internal_name, $uploaded_purpose, $user_id);
                break;

            default:
                return array(
                    "status" => "Error",
                    "error" => "Upload purpose not supported"
                );
                break;
        }
        
        echo json_encode($response);
    }
    
    public function removeAssociatedAction() {
        $file_type = $_POST['file_type'];
        
        switch ($file_type) {
            case "":


//                break;

            default:
                $response = array(
                    "status" => "Error",
                    "error" => "file type not supported"
                );
                break;
        }
        echo json_encode($response);
    }

}
