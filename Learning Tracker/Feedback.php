<?php

namespace App\Controllers;
use App\Models\Feedbacks;

/**
 * schedule controller
 *
 * PHP version 7.0
 */
class Feedback extends Authenticated {

    public function getQuestionsAction() {

        $course_fb_form_id = $this->route_params['token'];
        $course_id = $_SESSION['course_id'];
        
        $response = Feedbacks::getQuestiuionsOfTheForm($course_id, $course_fb_form_id);
        
        if(count($response) > 0){
            echo json_encode(array(
                "status" => "Success",
                "data" => $response
            ));
        }else{
            echo json_encode(array(
                "status" => "Error",
                "data" => "There seem to be no questions in the form"
            ));
        }
        
    }
    
    public function updateAction() {
      
        $courseFbFormId = $_POST['courseFbFormId'];
        $answers = $_POST['answers'];
        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION["user_id"];
        
        //Get all the questions of the form and check if answers contain all the questions
        $questionsOfTheForm = Feedbacks::getQuestiuionsOfTheForm($course_id, $courseFbFormId);
        foreach ($questionsOfTheForm as $question){
            $id  = $question['id'];
            $found = false;
            foreach ($answers as $answer){
                if($answer['id'] == $id){
                    $found = true;
                }
            }
            if(!$found){
                echo json_encode(array(
                    "status" => "Error",
                    "data" => "Answer to following question seem to be missing. Please raise a service request to Technical team<br>".$question['question']
                )); 
                exit;
            }
        }
        
        //Update answers
        $response = Feedbacks::updateFeedbackForm($course_id, $user_id, $courseFbFormId, $answers);
        echo json_encode($response);
    }
    
    
    
}
