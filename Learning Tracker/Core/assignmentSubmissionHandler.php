<?php
use \App\EventLoger;



class assignmentSubmissionHandler extends UpdateHandlers{

    /**
     * Log the support request into db and send notification email to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return array containing status and message
     */
    public function handleRequest($data) {
//{"upload_original_name":"","upload_custom_name":"Screenshot 2020-06-28 at 7.30.35 PM.png"}
        //Get all expected data into variables
        $userId = $_SESSION["user_id"];
        $courseId = $_SESSION["course_id"];

        $result = $this->logDetails($userId,$courseId, $data);
        if($result['status'] == "Error"){
            return $result;
        }

        $response = $this->notify($data);

        return $result;
    }

    /**
     * Insert support request data received from client into table interaction_messages
     * @param type $data is an array containing target_role, subject & message
     * @return boolean. True if successful and False otherwise
     */
    private function logDetails($userId,$courseId, $data) {

        $mandatoryParams = array('uploadedFileName', 'internalFileName', 'assignmentId', 'uploadedFileType', 'uploadedFileSize', 'assignmentType');
        //check for any missing parameters
        foreach ($mandatoryParams as $param) {
            if(!isset($data[$param])){
                return array(
                    "status" => "Error",
                    "message" => "Missing mandatory parameter: $param "
                );
            }elseif($data[$param] == ""){
                return array(
                    "status" => "Error",
                    "message" => "Mandatory parameter $param is empty!"
                );
            }
        }

        if($data['assignmentType'] != 'COURSE_SESSION' && $data['assignmentType'] != 'SUBJECT'){
            return array(
                "status" => "Error",
                "message" => "Invalid assignment type!"
            );
        }

        // set variable $submissionType as INDIVIDUAL or TEAM
        if (isset($data['teamMembers'])) {
            $submissionType = "TEAM";
        } else {
            $submissionType = "INDIVIDUAL";
        }


        //eventlogging for Assignment Submission
        $logDetails = array(
                            "fileDetails" => $data,
                            "submissionType"=>$submissionType
                            );
                            EventLoger::logEvent('Submit assignment', json_encode($logDetails));

        $fileCount = 1;//For now, we are supporting single file per assignment

        try {
            $db = static::getDB();

             $memberDetails = array();
            if ($submissionType == "TEAM") {
                foreach ($data['teamMembers'] as $teamMember) {
                    $stmt = $db->prepare("SELECT `users`.`id`
                                        FROM `users`
                                            JOIN `user_to_course_mapping` ON (`users`.`id` = `user_to_course_mapping`.`user_id`)
                                        WHERE `name` = :user_name
                                            AND  `users`.`status` = :status
                                            AND `user_to_course_mapping`.`role` = :role
                                            AND `course_id` = :course_id
                                            AND `user_to_course_mapping`.status = :status
                                            ");

                    $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
                    $stmt->bindValue(':role', 'PARTICIPANT', PDO::PARAM_STR);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->bindValue(':user_name', $teamMember, PDO::PARAM_STR);

                    if (!$stmt->execute()) {
                        return array(
                            "status" => "Error",
                            "message" => "Cought error while identifying team members"
                        );
                    }
                    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    array_push($memberDetails, array(
                        'user_id' => array_pop($results),
                        'user_name' => $teamMember
                    ));
                }
            }
            //Start the transaction
            $db->beginTransaction();

            //Record the details of the submission
            $stmt = $db->prepare("INSERT INTO `assignment_submissions` "
                                . "(`id`, `assignment_id`,`assignment_type` , `user_id`,`submission_type`, `Description`, `uploaded_file_count`, `updated_timestamp`, `status`) "
                                . "VALUES (NULL, :assignmentId, :assignment_type ,:userId, :submissionType, :description, :fileCount, current_timestamp(),'ACTIVE')");

            $stmt->bindValue(':assignmentId', intval($data['assignmentId']), PDO::PARAM_INT);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':submissionType', $submissionType, PDO::PARAM_STR);
            $stmt->bindValue(':assignment_type', $data['assignmentType'], PDO::PARAM_STR);
            $stmt->bindValue(':description', "", PDO::PARAM_STR);
            $stmt->bindValue(':fileCount', $fileCount, PDO::PARAM_INT);
//            $stmt->bindValue(':time', time(), PDO::PARAM_STR);

            if(!$stmt->execute()){
                return array(
                    "status" => "Error",
                    "message" => "Could not generate submission id"
                );
            }
            $submissionId = $db->lastInsertId();

            //Record the details of each file that has been uploaded as part of the submission
            $stmt = $db->prepare("INSERT INTO `course_assignment_files` "
                                . "(`submission_id`, `file_name`, `internal_file_name`, `file_type`, `file_size`, `file_description`, `file_path`, `status`) "
                                . "VALUES (:submissionId, :fileName, :s3FileName, :fileType, :fileSize, '', :filePath, 'ACTIVE')");
            $stmt->bindValue(':submissionId', $submissionId, PDO::PARAM_INT);
            $stmt->bindValue(':fileName', $data['uploadedFileName'], PDO::PARAM_STR);
            $stmt->bindValue(':s3FileName', $data['internalFileName'], PDO::PARAM_STR);
            $stmt->bindValue(':fileType', $data['uploadedFileType'], PDO::PARAM_STR);
            $stmt->bindValue(':fileSize', $data['uploadedFileSize'], PDO::PARAM_STR);
            $stmt->bindValue(':filePath', $data['internalFileName'], PDO::PARAM_STR);
            if(!$stmt->execute()){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Could not insert file details"
                );
            }

            if ($submissionType == "TEAM") {
                foreach ($memberDetails as $teamMember) {
                    $stmt = $db->prepare("INSERT INTO `assignment_submission_team` "
                            . "(`id`, `submission_id`, `user_id`, `status`) "
                            . "VALUES (NULL, :submissionId, :userId, :status)");

                    $stmt->bindValue(':submissionId', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':userId', $teamMember['user_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                    if (!$stmt->execute()) {
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Could not insert file details"
                        );
                    }
                }
            }
            $course_id = $_SESSION['course_id'];

            //Get the Score that needs to be awarded for this submission
            if($data['assignmentType'] === 'COURSE_SESSION'){
                $stmt = $db->prepare("SELECT `id`,`max_score`
                                    FROM `reward_point_criterias`
                                    WHERE `course_id` = :course_id
                                        AND `criteria` = :criteria
                                        AND `reference_id` = :assignmentId
                                        AND `status` = :status ");

                $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->bindValue(':criteria', 'ASSIGNMENT', PDO::PARAM_STR);
                $stmt->bindValue(':assignmentId', intval($data['assignmentId']), PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            }
            else{
                $stmt = $db->prepare("SELECT `id`,`max_score`
                                    FROM `reward_point_criterias`
                                    WHERE `course_id` = :course_id
                                        AND `criteria` = :criteria
                                        AND `reference_id` = :assignmentId
                                        AND `status` = :status ");

                $stmt->bindValue(':course_id', 0, PDO::PARAM_INT);
                $stmt->bindValue(':criteria', 'SUBJECT_ASSIGNMENT', PDO::PARAM_STR);
                $stmt->bindValue(':assignmentId', intval($data['assignmentId']), PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            }

            if(!$stmt->execute()){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Encountered error while comuting reward points"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $score = array_pop($results);

            $scoreCriteriaId = $score['id'];
            $scoreValue = $score['max_score'];

            //Insert the score for the user
            $stmt = $db->prepare("INSERT INTO `reward_points_scored` "
                                . "(`id`, `user_id`, `criteria_id`, `score`, `status`) "
                                . "VALUES (NULL, :userId, :criteria_id, :score, :status) ");
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':criteria_id', $scoreCriteriaId, PDO::PARAM_INT);
            $stmt->bindValue(':score', $scoreValue, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if(!$stmt->execute()){
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Error while recording score"
                );
            }
            if ($submissionType == "TEAM") {
                foreach ($memberDetails as $teamMember) {
                    //Invalidate previous reward points
                    $stmt = $db->prepare("UPDATE `reward_points_scored`
                                SET `status` = :new_status
                                WHERE `user_id` =  :userId
                                    AND `criteria_id` = :criteria_id
                                    AND `status` = :current_status ");
                    $stmt->bindValue(':userId', $teamMember['user_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':criteria_id', $scoreCriteriaId, PDO::PARAM_INT);
                    $stmt->bindValue(':current_status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->bindValue(':new_status', "INACTIVE", PDO::PARAM_STR);
                    $stmt->execute();

                    //Reward points fresh
                    $stmt = $db->prepare("INSERT INTO `reward_points_scored` "
                                        . "(`id`, `user_id`, `criteria_id`, `score`, `status`) "
                                        . "VALUES (NULL, :userId, :criteria_id, :score, :status) ");
                    $stmt->bindValue(':userId', $teamMember['user_id'], PDO::PARAM_INT);
                    $stmt->bindValue(':criteria_id', $scoreCriteriaId, PDO::PARAM_INT);
                    $stmt->bindValue(':score', $scoreValue, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    if(!$stmt->execute()){
                        $db->rollBack();
                        return array(
                            "status" => "Error",
                            "message" => "Error while recording score"
                        );
                    }
                }
            }

            //Finally commit and response success to client
            $db->commit();
            return array(
                "status" => "Success",
                "message" => ""
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

    /**
     * Send notification message to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return boolean. True if successful and False otherwise
     */
    private function notify($data) {
        /**
        Add the logic to send the email to participant with the uploaded files as attachment
         */
        return array(
            "status" => "Success",
            "message" => ""
        );
    }



}
