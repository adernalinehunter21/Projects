<?php

namespace App\Controllers;

use App\Models\Topics;
use App\Models\Subjects;

/**
 * module controller
 *
 * PHP version 7.0
 */
class Topic extends Authenticated {

    /**
     * Get the json containing the list of sessions of the course 
     * Required to list the sessions for dorp-downs
     */
    public function getTopicsOfTheModuleAction() {

        $module_id = $_POST['data']['module_id'];
        $user_id = $_SESSION['user_id'];
        
        $topic_list = array();
        if(Subjects::isModuleMappedToFacilitatorSubject($module_id, $user_id)){
            $topic_list = Topics::getTopicsOfTheCourse($module_id);
        }
        
        echo json_encode($topic_list);
    }

}
