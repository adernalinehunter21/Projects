<?php

use \App\Mail;
use \Core\View;
use \App\EventLoger;

class quizUpdateHandler extends UpdateHandlers {

    /**
     * Log the support request into db and send notification email to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return array containing status and message
     */
    public function handleRequest($data) {
        $quizDetails = array();
        $quizDetails = $this->getQuestions($data);
        if ($quizDetails['status'] == "Success") {
            return $quizDetails;
        }
        return array(
            "status" => "Error",
            "message" => $quizDetails['message']
        );
    }

    public function handleUpdate($data) {

        $data = json_decode($data);
        //Log the request and return error if there is any issue
        $evaluatedResult = $this->evaluateAnswers($data);
        if (!$evaluatedResult['status']) {
            return array(
                "status" => "Error",
                "message" => $evaluatedResult['message']
            );
        }
        $logAnswers = array();
        $logAnswers = $this->logAnswers($evaluatedResult['result']);
        //Log the request and return error if there is any issue
        
        //eventlogging for Quiz Submission
        $logDetails = array(
            "quiz_details" => $evaluatedResult
        );
        EventLoger::logEvent('Submit quiz', json_encode($logDetails));

        if ($logAnswers['status'] == "Error") {
            return array(
                "status" => "Error",
                "message" => $logAnswers['message']
            );
        }
        $arr = array(
            "questionGroupId" => $logAnswers['quizGroupId']
        );

        $quizDetails = $this->handleRequest($arr);
        return array(
            "status" => "Success",
            "quizDetails" => $quizDetails['quizDetails']
        );
    }

    /**
     * Insert support request data received from client into table exam_prep_answers
     * @param type $data is an array containing selected_option, question_id & score
     * @return boolean. True if successful and False otherwise
     */
    private function getQuestions($data) {
        $questionGroupId = $data['questionGroupId'];
        $user_id = $_SESSION['user_id'];

        try {
            $answerStatus = "";
            $db = static::getDB();
            $question_details = array();
            //this query will give quiz group name
            $stmt = $db->prepare('SELECT `id`,`quiz_group_name` FROM `quiz_question_group` WHERE `id` = :question_group_id AND `status` = :status ');
            $stmt->bindValue(':question_group_id', $questionGroupId, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $quizGroupDetails = array_pop($result1);
            $quizGroupName = $quizGroupDetails['quiz_group_name'];
            //this query will give id from quiz group answers using quiz group id and user id
            //By using this query we can know weather this quiz is answered by the user or not
            $stmt = $db->prepare('SELECT `id` FROM `quiz_group_answers` WHERE `quiz_group_id` = :question_group_id AND `user_id` = :user_id AND `status` = :status ');
            $stmt->bindValue(':question_group_id', $questionGroupId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $result7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($result7) == 0) {
                $answerStatus = "UNANSWERED";
                //this query will give question details 
                $stmt = $db->prepare('SELECT `id`, `question`, `answer_type` FROM `course_session_quiz_question` WHERE `group_id` = :group_id AND `status` = :status');
                $stmt->bindValue(':group_id', $questionGroupId, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting data"
                    );
                }
                $result9 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($result9 as $value3) {
                    $answerType = $value3['answer_type'];
                    if ($answerType === "OBJECTIVE" || $answerType === "MULTIPLE_CORRECT") {
                        //this query will give options from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id`, `option_value` FROM `course_session_quiz_options` WHERE `quiz_id` = :quiz_id AND `status` = :status ORDER BY `id` ASC');
                        $stmt->bindValue(':quiz_id', $value3['id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $value3['quiz_options'] = $result2;
                    } 
                    
                    array_push($question_details, $value3);
                }
            } else {
                $answerStatus = "ANSWERED";
                //this query will give question details 
                $stmt = $db->prepare("SELECT `course_session_quiz_question`.`id` AS question_id, `question`, `answer_type`,  `quiz_answers`.`id` AS answer_id
                                    FROM `course_session_quiz_question` 
                                    JOIN `quiz_answers` ON (course_session_quiz_question.id = quiz_answers.question_id)
                                    WHERE `group_id` = :group_id
                                    AND `quiz_answers`.`user_id` = :user_id
                                    AND `course_session_quiz_question`.`status` = :status
                                    AND `quiz_answers`.`status` = :status");
                $stmt->bindValue(':group_id', $questionGroupId, PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting data"
                    );
                }
                $result9 = $stmt->fetchAll(PDO::FETCH_ASSOC);
               
                foreach ($result9 as $value3) {
                    $answerType = $value3['answer_type'];
                    $selectedOptions = array();
                    $correctOptions = array();

                    if ($answerType == "OBJECTIVE") {
                        //this query will give options from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id`, `option_value` FROM `course_session_quiz_options` WHERE `quiz_id` = :quiz_id AND `status` = :status ORDER BY `id` ASC');
                        $stmt->bindValue(':quiz_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $value3['quiz_options'] = $result2;
                        //this query will give selected option from quiz answers table
                        $stmt = $db->prepare('SELECT `objective_option_id` FROM `quiz_answers` WHERE `question_id` = :question_id AND `user_id` = :user_id AND `status` = :status ');
                        $stmt->bindValue(':question_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $quizOptionSelected = array_pop($result3);
                        $selectedOption = $quizOptionSelected['objective_option_id'];
                        //this query will give correct option id from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id` FROM `course_session_quiz_options` WHERE `quiz_id` = :quiz_id  AND `correctness` = "CORRECT" AND `status` = :status ');
                        $stmt->bindValue(':quiz_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result6 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $correctOptionId = array_pop($result6);
                        $correctOption = $correctOptionId['id'];

                        $quiz_details_temp = array();
                        foreach ($value3 as $key => $value) {
                            $quiz_details_temp[$key] = $value;
                        }
                        $quiz_details_temp['chosenOptionId'] = $selectedOption;
                        $quiz_details_temp['correctOptionId'] = $correctOption;
                        array_push($question_details, $quiz_details_temp);
                    } else if ($answerType == "MULTIPLE_CORRECT") {

                        //this query will give options from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id`, `option_value` FROM `course_session_quiz_options` WHERE `quiz_id` = :quiz_id AND `status` = :status ORDER BY `id` ASC');
                        $stmt->bindValue(':quiz_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $value3['quiz_options'] = $result2;
                        //this query will give selected option from quiz answer multi options table
                        $stmt = $db->prepare('SELECT `multi_option_id` FROM `quiz_answer_multi_options` WHERE `answer_id` = :answer_id AND `selection_status` = :selection_status AND `status` = :status ');
                        $stmt->bindValue(':answer_id', $value3['answer_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':selection_status', "SELECTED", PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result4 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        //this query will give id of correct options from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id` FROM `course_session_quiz_options` WHERE `quiz_id` = :quiz_id  AND `correctness` = "CORRECT" AND `status` = :status ');
                        $stmt->bindValue(':quiz_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result6 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $quiz_details_temp = array();
                        foreach ($value3 as $key => $value) {
                            $quiz_details_temp[$key] = $value;
                        }
                        $quiz_details_temp['chosenOptionIds'] = $result4;
                        $quiz_details_temp['correctOptionIds'] = $result6;
                        array_push($question_details, $quiz_details_temp);
                    } else {
                        //this query will give selected option from quiz answers table
                        $stmt = $db->prepare('SELECT `descriptive_answer` FROM `quiz_answers` WHERE `question_id` = :question_id AND `user_id` = :user_id AND `status` = :status ');
                        $stmt->bindValue(':question_id', $value3['question_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data "
                            );
                        }
                        $result5 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $quizOptionSelected = array_pop($result5);
                        $enteredDescriptiveAnswer = $quizOptionSelected['descriptive_answer'];

                        $quiz_details_temp = array();
                        foreach ($value3 as $key => $value) {
                            $quiz_details_temp[$key] = $value;
                        }
                        $quiz_details_temp['enteredDescriptiveAnswer'] = $enteredDescriptiveAnswer;
                        array_push($question_details, $quiz_details_temp);
                    }
                }

            }
            return array(
                "status" => "Success",
                "quizDetails" => array(
                    "answerStatus" => $answerStatus,
                    "quizGroupName" => $quizGroupName,
                    "questions" => $question_details
                )
            );
            
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    private function evaluateAnswers($data) {
        $user_id = $_SESSION['user_id'];
        $course_id = $_SESSION['course_id'];
        try {
            $db = static::getDB();
            if (is_array($data)) {
                $evaluatedAnswers = array();
                foreach ($data as $answer) {
                    $quizGroupId = $answer->quizGroupId;
                    $question_id = $answer->question_id;
                    $question_type = $answer->question_type;
                    $value = $answer->value;
                    $question = $answer->question;
                    if ($question_type == "OBJECTIVE") {

                        //this query will give option_id's  from course_session_quiz_options table
                        $stmt = $db->prepare('SELECT `id` FROM `course_session_quiz_options` WHERE `quiz_id` = :question_id AND `correctness` = :correctness  AND `status` = :status');
                        $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                        $stmt->bindValue(':correctness', "CORRECT", PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while checking answer for question: $question "
                            );
                        }
                        $result1 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        if (count($result1) == 0) {
                            return array(
                                "status" => "Error",
                                "message" => "error while checking answer for question: $question "
                            );
                        }
                        $objective_option_id = array_pop($result1);
                        if ($value == $objective_option_id) {
                            //this query will give the correct option score from course_session_quiz_question table
                            $stmt = $db->prepare('SELECT `score` FROM `course_session_quiz_question` WHERE `id` = :question_id AND `status` = :status');
                            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                            if (!$stmt->execute()) {
                                return array(
                                    "status" => "Error",
                                    "message" => "error while getting score for question: $question"
                                );
                            }
                            $result2 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $objective_option_score = array_pop($result2);
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "OBJECTIVE",
                                "answered_option" => $value,
                                "correct_value" => $objective_option_id,
                                "score" => $objective_option_score,
                                "correctness" => "CORRECT"
                            ));
                        } else {
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "OBJECTIVE",
                                "answered_option" => $value,
                                "correct_value" => $objective_option_id,
                                "score" => 0,
                                "correctness" => "WRONG"
                            ));
                        }
                    } else if ($question_type == "MULTIPLE_CORRECT") {
                        //this query will give option_id and correctness from course_session_quiz_options table for corresponding question
                        $stmt = $db->prepare('SELECT `id`, `correctness` FROM `course_session_quiz_options` WHERE `quiz_id` = :question_id AND `status` = :status');
                        $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while checking answer for question: $question"
                            );
                        }
                        $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $overall_result = "pass";
                        $answered_choices = array();
                        foreach ($answer->value as $val) {
                            $choice_id = $val->choice_id;
                            $status = $val->status;
                            array_push($answered_choices, array(
                                "choice_id" => $choice_id,
                                "status" => $status
                            ));
                            $choice_result = "unknown";
                            foreach ($result3 as $one_choice) {
                                if ($one_choice['id'] == $choice_id) {
                                    if (($status == "checked" && $one_choice['correctness'] == "CORRECT") || ($status == "not checked" && $one_choice['correctness'] == "WRONG")) {
                                        $choice_result = "pass";
                                        break;
                                    } else {
                                        $choice_result = "fail";
                                        break;
                                    }
                                }
                            }
                            if ($overall_result == "pass" && $choice_result != "pass") {
                                $overall_result = $choice_result;
                            }
                        }
                        if ($overall_result == "unknown") {
                            return array(
                                "status" => "Error",
                                "message" => "unknown option found while checking answer for question: $question"
                            );
                        } elseif ($overall_result == "pass") {
                            //this query will give the correct option score from course_session_quiz_question table
                            $stmt = $db->prepare('SELECT `score` FROM `course_session_quiz_question` WHERE `id` = :question_id AND `status` = :status');
                            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                            if (!$stmt->execute()) {
                                return array(
                                    "status" => "Error",
                                    "message" => "error while getting score for question: $question"
                                );
                            }
                            $result4 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $multi_option_score = array_pop($result4);
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "MULTIPLE_CORRECT",
                                "answered_option" => $answered_choices,
                                "correct_value" => $result3,
                                "score" => $multi_option_score,
                                "correctness" => "CORRECT"
                            ));
                        } else {
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "MULTIPLE_CORRECT",
                                "answered_option" => $answered_choices,
                                "correct_value" => $result3,
                                "score" => 0,
                                "correctness" => "WRONG"
                            ));
                        }
                    } else {
                        $value = trim($answer->value);
                        if (!empty($value)) {
                            //this query will give the correct option score from course_session_quiz_question table
                            $stmt = $db->prepare('SELECT `score` FROM `course_session_quiz_question` WHERE `id` = :question_id AND `status` = :status');
                            $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                            if (!$stmt->execute()) {
                                return array(
                                    "status" => "Error",
                                    "message" => "error while getting score for question: $question"
                                );
                            }
                            $result2 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $descriptive_option_score = array_pop($result2);
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "DESCRIPTIVE",
                                "answered_option" => $value,
                                "score" => $descriptive_option_score,
                                "correctness" => "CORRECT"
                            ));
                        } else {
                            array_push($evaluatedAnswers, array(
                                "quizGroupId" => $quizGroupId,
                                "question_id" => $question_id,
                                "question_type" => "DESCRIPTIVE",
                                "answered_option" => $value,
                                "score" => 0,
                                "correctness" => "WRONG"
                            ));
                        }
                    }
                }
                return array(
                    "status" => "Success",
                    "result" => $evaluatedAnswers
                );
            } else {
                return array(
                    "status" => "Error",
                    "message" => "no data received at server"
                );
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    private function logAnswers($evaluatedResult) {

        try {
            $course_id = $_SESSION['course_id'];
            $user_id = $_SESSION['user_id'];
            $db = static::getDB();
            //Start the transaction
            $db->beginTransaction();
            $total_score = 0;
            $quizGroupId = $evaluatedResult[0]['quizGroupId'];
            //Inserting quiz_group_id and user_id into quiz_group_answers 
            $stmt = $db->prepare("INSERT INTO `quiz_group_answers` "
                    . "(`quiz_group_id`, `user_id`, `status`) "
                    . "VALUES (:quiz_group_id, :user_id, :status) ");
            $stmt->bindValue(':quiz_group_id', $quizGroupId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Error while storing data"
                );
            }
            
            $quizGroupAnswerId = $db->lastInsertId();
            foreach ($evaluatedResult as $row => $oneResult) {
                $quizGroupId = $oneResult['quizGroupId'];
                $question_id = $oneResult['question_id'];
                $question_type = $oneResult['question_type'];
                $answered_option = $oneResult['answered_option'];
                $score = $oneResult['score'];
                if ($question_type == "OBJECTIVE") {
                    //Inserting objective answers into quiz_answers table 
                    $stmt = $db->prepare("INSERT INTO `quiz_answers` "
                            . "(`quiz_group_answer_id`, `question_id`, `user_id`, `objective_option_id`, `descriptive_answer`, `score`, `status`) "
                            . "VALUES (:quiz_group_answer_id, :question_id, :user_id, :answered_option, :descriptive_answer, :score, :status) ");
                    $stmt->bindValue(':quiz_group_answer_id', $quizGroupAnswerId, PDO::PARAM_INT);
                    $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answered_option', $answered_option, PDO::PARAM_INT);
                    $stmt->bindValue(':descriptive_answer', "NULL", PDO::PARAM_STR);
                    $stmt->bindValue(':score', $score, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error while storing data"
                        );
                    }
                } elseif ($question_type == "DESCRIPTIVE") {
                    //Inserting descriptive answers into quiz_answers table
                    $stmt = $db->prepare("INSERT INTO `quiz_answers` "
                            . "(`quiz_group_answer_id`, `question_id`, `user_id`, `objective_option_id`, `descriptive_answer`, `score`, `status`) "
                            . "VALUES (:quiz_group_answer_id, :question_id, :user_id, :answered_option, :descriptive_answer, :score, :status) ");
                    $stmt->bindValue(':quiz_group_answer_id', $quizGroupAnswerId, PDO::PARAM_INT);
                    $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answered_option', NULL, PDO::PARAM_NULL);
                    $stmt->bindValue(':descriptive_answer', $answered_option, PDO::PARAM_STR);
                    $stmt->bindValue(':score', $score, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error while storing data"
                        );
                    }
                } else {
                    //Inserting multiple choice answers into quiz_answers table
                    $stmt = $db->prepare("INSERT INTO `quiz_answers` "
                            . "(`quiz_group_answer_id`, `question_id`, `user_id`, `objective_option_id`, `descriptive_answer`, `score`, `status`) "
                            . "VALUES (:quiz_group_answer_id, :question_id, :user_id, :answered_option, :descriptive_answer, :score, :status) ");
                    $stmt->bindValue(':quiz_group_answer_id', $quizGroupAnswerId, PDO::PARAM_INT);
                    $stmt->bindValue(':question_id', $question_id, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':answered_option', NULL, PDO::PARAM_NULL);
                    $stmt->bindValue(':descriptive_answer', NULL, PDO::PARAM_NULL);
                    $stmt->bindValue(':score', $score, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error while storing data"
                        );
                    }
                    $answer_id = $db->lastInsertId();
                    foreach ($oneResult['answered_option'] as $row => $val) {
                        $choice_id = $val['choice_id'];
                        $status = $val['status'];
                        if ($status == "checked") {
                            $status = "SELECTED";
                        } else {
                            $status = "NOT SELECTED";
                        }
                        //Inserting multiple choice answers into quiz_answers_multi_options table
                        $stmt = $db->prepare("INSERT INTO `quiz_answer_multi_options` "
                                . "(`answer_id`, `multi_option_id`, `selection_status`, `status`) "
                                . "VALUES (:answer_id, :multi_option_id, :selection_status, :status) ");
                        $stmt->bindValue(':answer_id', $answer_id, PDO::PARAM_INT);
                        $stmt->bindValue(':multi_option_id', $choice_id, PDO::PARAM_INT);
                        $stmt->bindValue(':selection_status', $status, PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            $db->rollBack();
                            return array(
                                "status" => "Error",
                                "message" => "Error while storing data"
                            );
                        }
                    }
                }
                
                    $total_score = $total_score + $score;
            }
                
                //Get the Score that needs to be awarded for this submission
                $stmt = $db->prepare("SELECT `id`,`max_score`  
                                FROM `reward_point_criterias` 
                                WHERE `course_id` = :course_id 
                                    AND `criteria` = :criteria 
                                    AND `reference_id` = :quizGroupId 
                                    AND `status` = :status ");
                $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->bindValue(':criteria', "QUIZ_GROUP", PDO::PARAM_STR);
                $stmt->bindValue(':quizGroupId', $quizGroupId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                if (!$stmt->execute()) {
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
                $stmt->bindValue(':submission_score', $total_score, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    $db->rollBack();
                    return array(
                        "status" => "Error",
                        "message" => "Error while recording score"
                    );
                }
            
            //Finally commit and response success to client
            $db->commit();
            return array(
                "status" => "Sucess",
                "quizGroupId" => $quizGroupId
            );
        } catch (PDOException $e) {
            return array(
                "status" => "Error",
                "message" => $e->getMessage()
            );
        }
    }

}
