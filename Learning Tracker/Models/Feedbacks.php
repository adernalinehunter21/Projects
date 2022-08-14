<?php

namespace App\Models;

use PDO;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Feedbacks extends \Core\Model {

    /**
     * Get some values as an associative array, some as column, some as int
     *
     * @return array called module details
     */
    public static function getQuestiuionsOfTheForm($course_id, $course_fb_form_id) {
        try {
            
            $db = static::getDB();
            //this query will give reflection_topics details
            $stmt = $db->prepare("SELECT `feedback_form_questions`.`id`,`question`
                                FROM `course_feedback_forms` 
                                    JOIN `feedback_form_questions` ON(`course_feedback_forms`.`feedback_form_id` = `feedback_form_questions`.`feedback_form_id`)
                                WHERE `course_feedback_forms`.`id` = :id
                                    AND `course_id` = :course_id
                                    AND `course_feedback_forms`.`status` = :status
                                    AND `feedback_form_questions`.`status` = :status ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':id', $course_fb_form_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
    /**
     * Get the list of feedback forms to be responded by the user
     * @param type $course_id
     * @param type $user_id 
     * @return type array of feedback-id and feedback name
     */
    public static function getPendingFeedbacks($course_id, $user_id) {
        $sql = "SELECT `id`,`feedback_form_id`, `belongs_to`, `reference_id`
                FROM `course_feedback_forms` 
                WHERE `course_id` = :course_id
                    AND `status` = :status
                    AND (`expirable` = 'NO' OR `expiry_date` > :today)
                    AND `feedback_form_id` NOT IN (
                        SELECT `feedback_form_id` 
                        FROM `feedback_submissions` 
                        WHERE `user_id` = :user_id 
                        AND `status` = :status
                    )";

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':today', date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $pendingFeedbackList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $feedbackArray = array();
        foreach ($pendingFeedbackList as $pendingFeedback){
            if($pendingFeedback['belongs_to'] == "SESSION"){
                $session_id = $pendingFeedback['reference_id'];
                $stmt = $db->prepare("SELECT `session_index`, `session_name`, `status` 
                                    FROM `course_sessions` 
                                    WHERE `session_id` = :session_id ");
                $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
                $stmt->execute();
                $sessionArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if(!$sessionArray || count($sessionArray) < 1){
                    //This should never happen
                    continue;
                }
                if($sessionArray[0]['status'] == "INACTIVE"){
                    //This is a special case. session has been disabled after creation of feedback-form
                    continue;
                }
                array_push($feedbackArray, array(
                    "courseFbFormId" => $pendingFeedback['id'],
                    "fbFormName" => "Feedback for Session-".$sessionArray[0]['session_index'].": ".$sessionArray[0]['session_name']
                ));
            }
            elseif($pendingFeedback['belongs_to'] === "MODULE"){
                $module_id = $pendingFeedback['reference_id'];
                $stmt = $db->prepare("SELECT `module_index`, `module_name`, `status`
                                    FROM `subject_modules` 
                                    WHERE `module_id` = :module_id ");
                $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
                $stmt->execute();
                $moduleArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if(!$moduleArray || count($moduleArray) < 1){
                    //This should never happen
                    continue;
                }
                if($moduleArray[0]['status'] == "INACTIVE"){
                    //This is a special case. module has been disabled after creation of feedback-form
                    continue;
                }
                array_push($feedbackArray, array(
                    "courseFbFormId" => $pendingFeedback['id'],
                    "fbFormName" => "Feedback for Module-".$moduleArray[0]['module_index'].": ".$moduleArray[0]['module_name']
                ));
            }
            elseif($pendingFeedback['belongs_to'] === "TOPIC"){
                $topic_id = $pendingFeedback['reference_id'];
                $stmt = $db->prepare("SELECT `name`,`status` 
                                    FROM `subject_topics` 
                                    WHERE `id` = :topic_id ");
                $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
                $stmt->execute();
                $topicArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if(!$topicArray || count($topicArray) < 1){
                    //This should never happen
                    continue;
                }
                if($topicArray[0]['status'] == "INACTIVE"){
                    //This is a special case. module has been disabled after creation of feedback-form
                    continue;
                }
                array_push($feedbackArray, array(
                    "courseFbFormId" => $pendingFeedback['id'],
                    "fbFormName" => "Feedback for topic: ".$topicArray[0]['module_name']
                ));
            }
            elseif($pendingFeedback['belongs_to'] === "PROGRAM"){
                array_push($feedbackArray, array(
                    "courseFbFormId" => $pendingFeedback['id'],
                    "fbFormName" => "Feedback for the overall learning in the course "
                ));
            }
            else{
                //This should never happen
                continue;
            }
        }
        return $feedbackArray;
    }
    
    public static function updateFeedbackForm($course_id, $user_id, $courseFbFormId, $answers) {
        try {
            $db = static::getDB();
            //Start the transaction
            $db->beginTransaction();
            
            //Record the details of the submission
            $stmt = $db->prepare("INSERT INTO `feedback_submissions` 
                                (`feedback_form_id`, `user_id`, `status`)
                                VALUES(:feedback_form_id, :user_id, :status) ");
            
            $stmt->bindValue(':feedback_form_id', $courseFbFormId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            
            if(!$stmt->execute()){
                return array(
                    "status" => "Error",
                    "message" => "Could not generate submission id"
                );
            }
            $submissionId = $db->lastInsertId();
            
            //Insert each answer
            foreach ($answers as $answer){
                $qid = $answer['id'];
                $answer = $answer['answer'];
                
                $stmt = $db->prepare("INSERT INTO `feedback_submission_details` 
                                    (`feedback_submission_id`, `question_id`, `answer_text`, `status`)
                                    VALUES(:feedback_submission_id, :question_id, :answer_text, :status ) ");

                $stmt->bindValue(':feedback_submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':question_id',  $qid, PDO::PARAM_INT);
                $stmt->bindValue(':answer_text', $answer, PDO::PARAM_STR);
                 $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Could not update one of the question"
                    );
                }
            }
            $db->commit();
            Feedbacks::removeFeedbackFromSession($courseFbFormId);
            return array(
                "status" => "Success",
                "message" => "Your feedback recorded successfully"
            );
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Faced following error while saving your feedback. Request you to kindly raise a support request: " . $error
            );
        }
    }
    
    private static function removeFeedbackFromSession($courseFbFormId) {
        $currentPendingFBs = $_SESSION['pending_feedbacks'];
        $newPendingFBs = array();
        foreach ($currentPendingFBs as $currentPendingFb){
            if($currentPendingFb['courseFbFormId'] == $courseFbFormId){
                continue;
            }
            else{
                array_push($newPendingFBs, $currentPendingFb);
            }
        }
        $_SESSION['pending_feedbacks'] = $newPendingFBs;
    }
}


