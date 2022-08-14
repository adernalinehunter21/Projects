<?php

//use \App\Token;
use \App\Mail;
use \Core\View;
use \App\EventLoger;

class examPrepUpdateHandler extends UpdateHandlers{

    /**
     * Log the support request into db and send notification email to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return array containing status and message
     */
    public function handleUpdate($data) {

        $data['user_id'] = $_SESSION['user_id'];
        $data['course_id'] = $_SESSION['course_id'];
        
        //Log the request and return error if there is any issue
        $logResult = $this->logAnswer($data);
        if ($logResult['status'] == "Error") {
            return array(
                "status" => "Error",
                "message" => $logResult['message']
            );
        }
        $logOptionResult = array();
        $logOptionResult = $this->logOption($data);
        //Log the request and return error if there is any issue
        if (!$logOptionResult) {
            return array(
                "status" => "Error",
                "message" => "error while getting option details"
            );
        }
        return array(
            "status" => "Success",
            "option_id" => $logResult['option_id'],
            "correctness" => $logResult['correctness'],
            "score" => $logResult['score'],
            "option_type" => $logResult['option_type'],
            "all_options" => $logOptionResult
        );
    }

    /**
     * Insert support request data received from client into table exam_prep_answers
     * @param type $data is an array containing selected_option, question_id & score
     * @return boolean. True if successful and False otherwise
     */
    private function logAnswer($data) {
        $user_id = $data['user_id'];
        $selected_option = $data['selected_option'];
        $question_id = $data['question_id'];
        $course_id = $data['course_id'];
        try {
            $db = static::getDB();
            //Start the transaction
            $db->beginTransaction();
            
            //this query will give exam prep options from exam prep options table
            $stmt = $db->prepare('SELECT `id`, `option_type`, `correctness`, `score` FROM `exam_prep_options` WHERE `id` = :selected_option AND `question_id` = :question_id ORDER BY `id` ASC');
            $stmt->bindValue(':selected_option', $selected_option, PDO::PARAM_INT);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            if(!$result){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "error while checking correctness of the answer"
                );
            }
            $result7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $score_details = array_pop($result7);
            $score = $score_details['score'];
            $option_type = $score_details['option_type'];
            
            //eventlogging for Exam Prep Submission
            $logDetails = array(
                "question_id" => $question_id,
                "score_details" => $score_details
            );
            EventLoger::logEvent('Submit exam prep', json_encode($logDetails));

            //this query will insert into exam prep answers table
            $result = $db->exec("INSERT INTO `exam_prep_answers` "
                                . "(`question_id`, `user_id`, `option_id`, `option_type`, `score`, `update_timestamp`, `status`) "
                                . "VALUES ('$question_id', '$user_id', '$selected_option', '$option_type', '$score', CURRENT_TIMESTAMP(), 'ACTIVE')");

            if(!$result){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "error while saving data"
                );
            }
            
            //Get the Score that needs to be awarded for this submission
            $stmt = $db->prepare("SELECT `id`,`max_score`  
                                FROM `reward_point_criterias` 
                                WHERE `course_id` = :course_id 
                                    AND `criteria` = :criteria 
                                    AND `reference_id` = :question_id 
                                    AND `status` = :status ");
            
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':criteria', 'EXAM_PREP', PDO::PARAM_STR);
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            
            if(!$stmt->execute()){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Encountered error while getting reward criteria"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $submission_score = array_pop($results);
            
            $scoreCriteriaId = $submission_score['id'];
            
            //Insert the score for the user
            $stmt = $db->prepare("INSERT INTO `reward_points_scored` "
                                . "(`user_id`, `criteria_id`, `score`, `status`) "
                                . "VALUES (:user_id, :criteria_id, :submission_score, :status) ");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':criteria_id', $scoreCriteriaId, PDO::PARAM_INT);
            $stmt->bindValue(':submission_score', $score, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
           
            if(!$stmt->execute()){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Error while recording score"
                );
            }
            
            //Finally commit and response success to client
            $db->commit();
            
            
            return array(
                "status" => "Success",
                "option_id" => $score_details['id'],
                "correctness" => $score_details['correctness'],
                "score" => $score_details['score'],
                "option_type" => $score_details['option_type']
            );
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error = $e->getMessage();
           
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time ".$error
            );
        }
    }
    
    private function logOption($data) {
        $user_id = $data['user_id'];
        $question_id = $data['question_id'];
        
        try {
            $db = static::getDB();
            //this query will give exam prep option details
            $stmt = $db->prepare('SELECT `option_type`, `correctness`, `score` FROM `exam_prep_options` WHERE `question_id` = :question_id ORDER BY `id` ASC');
            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
            $stmt->execute();
            $result8 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $all_options = array();
            foreach($result8 as $value){
               
                array_push($all_options, array(
                    "status" => true,
                "option_correctness" => $value['correctness'],
                "option_score" => $value['score'],
                "option_type" => $value['option_type']
                ));
            }
            return $all_options; 
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return false;
        }
    }
   
}
