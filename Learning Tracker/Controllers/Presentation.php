<?php

namespace App\Controllers;
use \App\Auth;
use \App\Models\Presentations;


/**
 * Profile controller
 *
 * PHP version 7.0
 */
class Presentation extends Authenticated {

    /**
     * Before filter - called before each action method
     *
     * @return void
     */
    protected function before() {
        parent::before();

        $this->user = Auth::getUser();
    }

    /**
     * Show the profile
     *
     * @return void
     */
    public function getAction() {

        
        $currentImageOrder = $_POST['order'];
        $topicId = $_POST['topicId'];
        $type = $_POST['type'];
        $direction = isset($_POST['direction'])? $_POST['direction']: "Next";
        
        $subjectId = $_SESSION['subject_id'];
        $subjectVersion = $_SESSION['subjectVersion'];
        
        $response = array();
        if($type === "Session"){
            $sessionId = $_POST['sessionId'];
            $response = Presentations::getNextPresentationSlideOfSession($subjectId, $subjectVersion, $sessionId, $topicId, $currentImageOrder, $direction);
        }
        elseif ($type === "Module") {
            $moduleIndex = $_POST['moduleIndex'];
            $response = Presentations::getNextPresentationSlideOfModule($subjectId, $subjectVersion, $moduleIndex, $topicId, $currentImageOrder, $direction);
        }
        

        echo json_encode($response);
    }
    
    public function getSlideNumUnderModuleAction(){
        $module_index = $_POST['module_index'];
        $image_topic_id = $_POST['image_topic_id'];
        $image_order = $_POST['image_order'];

        $subject_id = $_SESSION['subject_id'];
        
        $slide_number = Presentations::getSlideNumberUnderModule($module_index, $subject_id, $image_topic_id,$image_order);

        $response = array(
            "status" => "Success",
            "slide_number" => $slide_number
        );

        echo json_encode($response);
    }
}
