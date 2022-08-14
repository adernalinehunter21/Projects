<?php

namespace App\Models;

use PDO;
use App\s3;
use App\Models\Reflections;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Assignments extends \Core\Model {

    /**
     * Get name of the course
     * @param type $course_id
     * @return string
     */
    public static function getSubmittedAssignmentsOfTheCourse($course_id,$subjectId) {
        try {
//            Get the list of session that are already over
            $sessions = static::getSessionsOfTheCourse($course_id);
            if(count($sessions) === 0){
                return $sessions;
            }

            $participants = static::getParticipantsOfTheCourse($course_id);
            if(count($participants) === 0){
                return $participants;
            }

            $sessionwiseAssignmentData = array();
            foreach ($sessions as $session){

                $session_id = $session['session_id'];
                $teamSumissionsOfSession = static::getTeamSubmittedAssignmentsOfTheSession($session_id);

                $participantsData = array();
                foreach ($participants as $participant){

                    $user_id = $participant['id'];
                    $submittedAssignmentsOfTheUser = static::getTheIndividualSubmissionsOfTheUser($user_id, $session_id);
                    if(isset($teamSumissionsOfSession[$user_id])){
                        foreach ($teamSumissionsOfSession[$user_id] as $oneAssignment){
                            $team = $oneAssignment['otherTeamMembers'];
                            $teamMemberDetails = array();
                            foreach ($team as $memberId){
                                foreach ($participants as $member){
                                    if($member['id'] == $memberId){
                                        $memberName = $member['name'].(isset($member['last_name'])?" ".$member['last_name']: "");
                                        array_push($teamMemberDetails, $memberName);
                                    }
                                }
                            }
                            $oneAssignment['otherTeamMembers'] = $teamMemberDetails;
                            array_push($submittedAssignmentsOfTheUser, $oneAssignment);
                        }
                    }
                    if(count($submittedAssignmentsOfTheUser) > 0){
                        array_push($participantsData, array(
                            "id" => $participant['id'],
                            "name" => $participant['name'].(isset($participant['last_name'])?" ".$participant['last_name']: ""),
                            "assignments" => $submittedAssignmentsOfTheUser
                        ));
                    }
                }
                array_push($sessionwiseAssignmentData, array(
                    "session_id" => $session_id,
                    "session_index" => $session['session_index'],
                    "session_name" => $session['session_name'],
                    "participants" => $participantsData
                ));
            }
            return array(
                "participants_details" => $participants,
                "session_wise_assignment_data" => $sessionwiseAssignmentData
            );

        } catch (PDOException $e) {
            return null;
        }
    }

    public static function getSessionAssignments($course_id,$session_id,$user_id){
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_session_assignments`.`id`,
                                    `name`,
                                    `course_session_assignments`.`description` AS description,
                                    submissions.`id` AS submission_id,
                                    'COURSE_SESSION' AS assignment_type
                                FROM `course_session_assignments`
                                    LEFT JOIN (SELECT * FROM `assignment_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions ON (`course_session_assignments`.`id` = submissions.`assignment_id`)
                                WHERE `session_id` = :session_id
                                    AND `course_session_assignments`.`status` = :status
                                ORDER BY `course_session_assignments`.`id` ASC");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result8 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $assignments = array();
        foreach ($result8 as $oneAssignment) {
            $currentAssignment = array(
                "id" => $oneAssignment['id'],
                "name" => $oneAssignment['name'],
                "description" => $oneAssignment['description'],
                "assignmentType" => "COURSE_SESSION_ASSIGNMENT"
            );
            $stmt = $db->prepare("SELECT `document_name` , `document_link`
                                FROM `course_session_assignment_reference`
                                WHERE `assignment_id` = :assignment_id
                                    AND `status` = :status
                                ORDER BY `id` ASC ");
            $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $currentAssignment['reference_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //If assignment is already submitted, get the details of the submitted documents

            if (isset($oneAssignment['submission_id'])) {
                $currentAssignment['submission_status'] = "submitted";
                $submissionId = $oneAssignment['submission_id'];
                $stmt = $db->prepare("SELECT `id`, `file_name`, `file_type`, `file_size`, `file_path`
                                    FROM `course_assignment_files`
                                    WHERE `submission_id` = :submission_id
                                        AND `status` = :status ");

                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $fileDetails = array();

                foreach ($result as $oneFileDetails) {
                    $fileName = $oneFileDetails['file_name'];
                    $fileInternalName = $oneFileDetails['file_path'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                    array_push($fileDetails, array(
                        "name" => $fileName,
                        "url" => $filePath
                    ));
                }

                $currentAssignment['submission_details'] = array(
                    "submission_id" => $submissionId,
                    "uploaded_documents" => $fileDetails
                );

                $stmt = $db->prepare("SELECT  `user_id`, CONCAT(`name`, ' ', `last_name`) AS name
                                    FROM `assignment_submission_team`
                                        JOIN `users` ON (`assignment_submission_team`.`user_id` = `users`.`id`)
                                    WHERE `submission_id` = :submission_id
                                        AND `assignment_submission_team`.`status` = :status ");
                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($result) > 0){
                    $team = array();
                    foreach($result as $teamMember){
                        array_push(
                            $team,
                            array(
                                "name" => $teamMember['name']
                            )
                        );
                    }
                    $currentAssignment['submission_details']['submission_type'] = "TEAM";
                    $currentAssignment['submission_details']['other_team_members'] = $team;
                }
                else{
                    $currentAssignment['submission_details']['submission_type'] = "INDIVIDUAL";
                }
            }
            else {
                $stmt = $db->prepare("SELECT `assignment_submissions`.`id`, `users`.`id` AS submitted_user_id, CONCAT(`name`, ' ', `last_name`) AS submitted_user
                                        FROM `assignment_submissions`
                                        JOIN `assignment_submission_team` ON (`assignment_submissions`.`id` = `assignment_submission_team`.`submission_id`)
                                        JOIN `users` ON (`assignment_submissions`.`user_id` = `users`.`id`)
                                        WHERE `assignment_submissions`.`status` = :status
                                            AND `assignment_submission_team`.`status` = :status
                                            AND `assignment_id` = :assignment_id
                                            AND `assignment_submission_team`.`user_id` = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {

                    $resultRow = array_pop($result);
                    $submissionId = $resultRow['id'];
                    //this query will give the details of file
                    $stmt = $db->prepare("SELECT `id`, `file_name`, `file_type`, `file_size`, `file_path`
                                        FROM `course_assignment_files`
                                        WHERE `submission_id` = :submission_id
                                            AND `status` = :status ");
                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $fileDetails = array();
                    foreach ($result as $oneFileDetails) {
                        $fileName = $oneFileDetails['file_name'];
                        $fileInternalName = $oneFileDetails['file_path'];
                        $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                        array_push($fileDetails, array(
                            "name" => $fileName,
                            "url" => $filePath
                        ));
                    }
                    $currentAssignment['submission_status'] = "submitted";

                    $stmt = $db->prepare("SELECT `users`.`id`, CONCAT(`name`, ' ', `last_name`) AS name
                                        FROM `assignment_submission_team`
                                            JOIN `users` ON (`assignment_submission_team`.`user_id` = `users`.`id`)
                                        WHERE `assignment_submission_team`.`submission_id` = :submission_id
                                            AND `assignment_submission_team`.`status` = :status
                                            AND `user_id` != :user_id ");

                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $team = array();
                    if(count($result) > 0){
                        foreach($result as $teamMember){
                            array_push(
                                $team,
                                array(
                                    "name" => $teamMember['name']
                                )
                            );
                        }
                    }
                    array_push(
                        $team,array(
                            "name" => $resultRow['submitted_user']
                        )
                    );
                    $currentAssignment['submission_details'] = array(
                        "submission_id" => $submissionId,
                        "uploaded_documents" => $fileDetails,
                        "submission_type" => "TEAM",
                        "other_team_members" => $team
                    );
                }
                else{
                    $currentAssignment['submission_status'] = "not_submitted" ;
                    $currentAssignment['assignment_type'] = "COURSE_SESSION" ;
                    $currentAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);
                }

            }
            array_push($assignments, $currentAssignment);
        }

        return $assignments;
    }


    public static function getSessionAssignmentsOfSubject($course_id, $session_id, $user_id){

        $db = static::getDB();
        $stmt = $db->prepare("SELECT `subject_assignments`.`id`,
                                `course_session_to_topic_mapping`.`session_id`,
                                `subject_assignments`.`name`,
                                `subject_assignments`.`description`,
                                submissions.`id` AS submission_id,
                                submissions.`submission_status` AS submissionStatus

                            FROM `course_session_to_topic_mapping`
                                JOIN `subject_assignments` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_assignments`.`associated_id` )
                                LEFT JOIN (SELECT * FROM `submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions ON (`subject_assignments`.`id` = submissions.`reference_id`)
                            WHERE `subject_assignments`.`associated_to` = :associated_to
                                AND `course_session_to_topic_mapping`.`status` = :status
                                AND `course_session_to_topic_mapping`.`session_id` = :sessionId
                                AND `subject_assignments`.`status` = :status ");

        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':sessionId', $session_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $assignments = array();

        foreach($result as $oneAssignment){
            $currentAssignment = array(
                "id" => $oneAssignment['id'],
                "name" => $oneAssignment['name'],
                "description" => $oneAssignment['description'],
                "assignmentType" => "SUBJECT_ASSIGNMENT",
                "submissionStatus"=> $oneAssignment['submissionStatus'],
                "submission_id"=>$oneAssignment['submission_id']
            );
            $currentAssignment['reference_documents'] = static::getReferencesOfTheAssignment($oneAssignment['id']);

            if (isset($oneAssignment['submission_id'])) {
                $currentAssignment['submission_status'] = "submitted";
                $submissionId = $oneAssignment['submission_id'];
                $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                    `message_attachments`.`file_size`,
                                    `message_attachments`.`file_type`,
                                    `message_attachments`.`internal_file_name` AS file_path

                                    FROM `submissions`
                                        JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                        JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                    WHERE `submissions`.`id` = :submission_id
                                        AND `submissions`.`status` = :status ");

                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $fileDetails = array();

                foreach ($result as $oneFileDetails) {
                    $fileName = $oneFileDetails['file_name'];
                    $fileInternalName = $oneFileDetails['file_path'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                    array_push($fileDetails, array(
                        "name" => $fileName,
                        "url" => $filePath
                    ));
                }

                $currentAssignment['submission_id'] = $submissionId;
                $currentAssignment['uploaded_documents'] = $fileDetails;
                $currentAssignment['submission_details'] = array(
                    "submission_id" => $submissionId,
                    "uploaded_documents" => $fileDetails
                );

                $stmt = $db->prepare("SELECT  `user_id`, CONCAT(`name`, ' ', `last_name`) AS name
                                    FROM `submission_team`
                                        JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                    WHERE `submission_id` = :submission_id
                                        AND `submission_team`.`status` = :status ");
                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($result) > 0){
                    $team = array();
                    foreach($result as $teamMember){
                        array_push(
                            $team,
                            array(
                                "name" => $teamMember['name']
                            )
                        );
                    }
                    $currentAssignment['submission_details']['submission_type'] = "TEAM";
                    $currentAssignment['submission_type'] = "TEAM";
                    $currentAssignment['submission_details']['other_team_members'] = $team;
                }
                else{
                    $currentAssignment['submission_details']['submission_type'] = "INDIVIDUAL";
                    $currentAssignment['submission_type'] = "INDIVIDUAL";
                }
            }
            else {
                $stmt = $db->prepare("SELECT `submissions`.`id`, `users`.`id` AS submitted_user_id, CONCAT(`name`, ' ', `last_name`) AS submitted_user
                                        FROM `submissions`
                                        JOIN `submission_team` ON (`submissions`.`id` = `submission_team`.`submission_id`)
                                        JOIN `users` ON (`submissions`.`user_id` = `users`.`id`)
                                        WHERE `submissions`.`status` = :status
                                            AND `submission_team`.`status` = :status
                                            AND `reference_id` = :assignment_id
                                            AND `submissions`.`type` = :type
                                            AND `submission_team`.`user_id` = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {

                    $resultRow = array_pop($result);
                    $submissionId = $resultRow['id'];
                    //this query will give the details of file
                    $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                        `message_attachments`.`file_size`,
                                        `message_attachments`.`file_type`,
                                        `message_attachments`.`internal_file_name` AS file_path

                                        FROM `submissions`
                                        JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                        JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                        WHERE `submissions`.`id` = :submission_id
                                        AND `submissions`.`status` = :status ");
                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $fileDetails = array();
                    foreach ($result as $oneFileDetails) {
                        $fileName = $oneFileDetails['file_name'];
                        $fileInternalName = $oneFileDetails['file_path'];
                        $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                        array_push($fileDetails, array(
                            "name" => $fileName,
                            "url" => $filePath
                        ));
                    }
                    $currentAssignment['submission_status'] = "submitted";

                    $stmt = $db->prepare("SELECT `users`.`id`, CONCAT(`name`, ' ', `last_name`) AS name
                                        FROM `submission_team`
                                            JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                        WHERE `submission_team`.`submission_id` = :submission_id
                                            AND `submission_team`.`status` = :status
                                            AND `user_id` != :user_id ");

                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $team = array();
                    if(count($result) > 0){
                        foreach($result as $teamMember){
                            array_push(
                                $team,
                                array(
                                    "name" => $teamMember['name']
                                )
                            );
                        }
                    }
                    array_push(
                        $team,array(
                            "name" => $resultRow['submitted_user']
                        )
                    );
                    $currentAssignment['assignment_type'] = "SUBJECT" ;
                    // $currentAssignment['submission_type'] = "TEAM";
                    $currentAssignment['submission_details'] = array(
                        "submission_id" => $submissionId,
                        "uploaded_documents" => $fileDetails,
                        "submission_type" => "TEAM",
                        "other_team_members" => $team
                    );
                }
                else{
                    // $currentAssignment['submission_type'] = "INDIVIDUAL";
                    $currentAssignment['submission_status'] = "not_submitted" ;
                    $currentAssignment['assignment_type'] = "SUBJECT" ;
                    $currentAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);
                }

            }
            array_push($assignments, $currentAssignment);
        }
        // echo '<pre>'; var_dump($assignments); echo '</pre>';

        return $assignments;
        }


    public static function getModuleAndSubjectMappedAssignments($subject_id, $user_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `subject_assignments`.`id`,
                                    `subject_assignments`.`name`,
                                    `subject_assignments`.`description`,
                                    submissions.`id` AS submission_id,
                                    submissions.`submission_status` AS submissionStatus
                                FROM `subject_assignments`
                                LEFT JOIN (SELECT * FROM `submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions ON (`subject_assignments`.`id` = submissions.`reference_id`)
                            WHERE `subject_assignments`.`associated_to` != :associated_to
                                AND `subject_assignments`.`status` =:status
                                AND `subject_assignments`.`subject_id` =:subjectId");

        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subjectId', $subject_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();

        foreach($result as $oneAssignment){
            $currentAssignment = array(
                "id" => $oneAssignment['id'],
                "name" => $oneAssignment['name'],
                "description" => $oneAssignment['description'],
                "assignmentType" => "SUBJECT_ASSIGNMENT",
                "submissionStatus" => $oneAssignment['submissionStatus'],
                "submission_id"=>$oneAssignment['submission_id']
            );
            $currentAssignment['reference_documents'] = static::getReferencesOfTheAssignment($oneAssignment['id']);

            if (isset($oneAssignment['submission_id'])) {
                $currentAssignment['submission_status'] = "submitted";
                $submissionId = $oneAssignment['submission_id'];

                $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                    `message_attachments`.`file_size`,
                                    `message_attachments`.`file_type`,
                                    `message_attachments`.`internal_file_name` AS file_path

                                    FROM `submissions`
                                    JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                    JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                    WHERE `submissions`.`id` = :submission_id
                                    AND `submissions`.`status` = :status
                                     ");

                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $fileDetails = array();

                foreach ($result as $oneFileDetails) {
                    $fileName = $oneFileDetails['file_name'];
                    $fileInternalName = $oneFileDetails['file_path'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                    array_push($fileDetails, array(
                        "name" => $fileName,
                        "url" => $filePath
                    ));
                }

                $currentAssignment['submission_details'] = array(
                    "submission_id" => $submissionId,
                    "uploaded_documents" => $fileDetails
                );

                $stmt = $db->prepare("SELECT  `user_id`, CONCAT(`name`, ' ', `last_name`) AS name
                                    FROM `submission_team`
                                        JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                    WHERE `submission_id` = :submission_id
                                        AND `submission_team`.`status` = :status ");
                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($result) > 0){
                    $team = array();
                    foreach($result as $teamMember){
                        array_push(
                            $team,
                            array(
                                "name" => $teamMember['name']
                            )
                        );
                    }
                    $currentAssignment['submission_details']['submission_type'] = "TEAM";
                    $currentAssignment['submission_details']['other_team_members'] = $team;
                }
                else{
                    $currentAssignment['submission_details']['submission_type'] = "INDIVIDUAL";
                }
            }
            else {
                $stmt = $db->prepare("SELECT `submissions`.`id`, `users`.`id` AS submitted_user_id, CONCAT(`name`, ' ', `last_name`) AS submitted_user
                                        FROM `submissions`
                                        JOIN `submission_team` ON (`submissions`.`id` = `submission_team`.`submission_id`)
                                        JOIN `users` ON (`submissions`.`user_id` = `users`.`id`)
                                        WHERE `submissions`.`status` = :status
                                            AND `submission_team`.`status` = :status
                                            AND `reference_id` = :assignment_id
                                            AND `submissions`.`type` = :type
                                            AND `submission_team`.`user_id` = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {

                    $resultRow = array_pop($result);
                    $submissionId = $resultRow['id'];
                    //this query will give the details of file
                    $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                        `message_attachments`.`file_size`,
                                        `message_attachments`.`file_type`,
                                        `message_attachments`.`internal_file_name` AS file_path

                                        FROM `submissions`
                                        JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                        JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                        WHERE `submissions`.`id` = :submission_id
                                        AND `submissions`.`status` = :status ");
                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $fileDetails = array();
                    foreach ($result as $oneFileDetails) {
                        $fileName = $oneFileDetails['file_name'];
                        $fileInternalName = $oneFileDetails['file_path'];
                        $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                        array_push($fileDetails, array(
                            "name" => $fileName,
                            "url" => $filePath
                        ));
                    }
                    $currentAssignment['submission_status'] = "submitted";

                    $stmt = $db->prepare("SELECT `users`.`id`, CONCAT(`name`, ' ', `last_name`) AS name
                                        FROM `submission_team`
                                            JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                        WHERE `submission_team`.`submission_id` = :submission_id
                                            AND `submission_team`.`status` = :status
                                            AND `user_id` != :user_id ");

                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $team = array();
                    if(count($result) > 0){
                        foreach($result as $teamMember){
                            array_push(
                                $team,
                                array(
                                    "name" => $teamMember['name']
                                )
                            );
                        }
                    }
                    array_push(
                        $team,array(
                            "name" => $resultRow['submitted_user']
                        )
                    );
                    $currentAssignment['submission_details'] = array(
                        "submission_id" => $submissionId,
                        "uploaded_documents" => $fileDetails,
                        "submission_type" => "TEAM",
                        "other_team_members" => $team
                    );
                }
                else{
                    $currentAssignment['submission_status'] = "not_submitted" ;
                    $currentAssignment['assignment_type'] = "SUBJECT" ;
                    $currentAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);
                }

            }
            array_push($assignments, $currentAssignment);
        }
        return $assignments;
        }



    public static function getModuleAndTopicMappedAssignments($subject_id, $user_id, $module_index){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `subject_assignments`.`id`,
                                    `subject_assignments`.`name`,
                                    `subject_assignments`.`description`,
                                    topic_module.topic_module_index,
                                    modules.`module_index`,
                                    submissions.`id` AS submission_id,
                                    submissions.`submission_status` AS submissionStatus
                                FROM `subject_assignments`
                                LEFT JOIN (SELECT * FROM `submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions
                                ON (`subject_assignments`.`id` = submissions.`reference_id`)
                                LEFT JOIN(
                                SELECT
                                    `module_id`,
                                    `module_index`,
                                    `module_name`
                                FROM `subject_modules`
                                WHERE `subject_id` = :subjectId
                                    AND `status` = :status
                                ) AS modules ON ( `subject_assignments`.`associated_to` = 'MODULE' AND `subject_assignments`.`associated_id` = modules.module_id )

                                LEFT JOIN(
                                    SELECT  `name` AS module_topic,
                                    	`subject_topics`.`id` AS module_topic_id,
                                    	`module_index` AS topic_module_index,
                                    	`module_name` AS topic_module_name
                                    FROM `subject_topics`
                                    JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `subject_id` = :subjectId
                                    AND `subject_topics`.`status` =:status
                                    AND `subject_modules`.`status` =:status
                                ) AS topic_module ON ( `subject_assignments`.`associated_to` = 'TOPIC' AND `subject_assignments`.`associated_id` = topic_module.module_topic_id )

                            WHERE `subject_assignments`.`associated_to` != :associated_to
                                AND `subject_assignments`.`status` =:status
                                AND `subject_assignments`.`subject_id` =:subjectId");

        $stmt->bindValue(':associated_to', 'ENTIRE_SUBJECT', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subjectId', $subject_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $assignments = array();

        foreach($result as $oneAssignment){
            $currentAssignment = array(
                "id" => $oneAssignment['id'],
                "name" => $oneAssignment['name'],
                "description" => $oneAssignment['description'],
                "topic_module_index" => $oneAssignment['topic_module_index'],
                "module_index" => $oneAssignment['module_index'],
                "assignmentType" => "SUBJECT_ASSIGNMENT",
                "submissionStatus" => $oneAssignment['submissionStatus'],
                "submission_id"=>$oneAssignment['submission_id']
            );
            $currentAssignment['reference_documents'] = static::getReferencesOfTheAssignment($oneAssignment['id']);

            if (isset($oneAssignment['submission_id'])) {
                $currentAssignment['submission_status'] = "submitted";
                $submissionId = $oneAssignment['submission_id'];
                $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                    `message_attachments`.`file_size`,
                                    `message_attachments`.`file_type`,
                                    `message_attachments`.`internal_file_name` AS file_path

                                    FROM `submissions`
                                    JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                    JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                    WHERE `submissions`.`id` = :submission_id
                                    AND `submissions`.`status` = :status");

                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $fileDetails = array();

                foreach ($result as $oneFileDetails) {
                    $fileName = $oneFileDetails['file_name'];
                    $fileInternalName = $oneFileDetails['file_path'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                    array_push($fileDetails, array(
                        "name" => $fileName,
                        "url" => $filePath
                    ));
                }

                $currentAssignment['submission_details'] = array(
                    "submission_id" => $submissionId,
                    "uploaded_documents" => $fileDetails
                );

                $stmt = $db->prepare("SELECT  `user_id`, CONCAT(`name`, ' ', `last_name`) AS name
                                    FROM `submission_team`
                                        JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                    WHERE `submission_id` = :submission_id
                                        AND `submission_team`.`status` = :status ");
                $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if(count($result) > 0){
                    $team = array();
                    foreach($result as $teamMember){
                        array_push(
                            $team,
                            array(
                                "name" => $teamMember['name']
                            )
                        );
                    }
                    $currentAssignment['submission_details']['submission_type'] = "TEAM";
                    $currentAssignment['submission_details']['other_team_members'] = $team;
                }
                else{
                    $currentAssignment['submission_details']['submission_type'] = "INDIVIDUAL";
                }
            }
            else {
                $stmt = $db->prepare("SELECT `submissions`.`id`, `users`.`id` AS submitted_user_id, CONCAT(`name`, ' ', `last_name`) AS submitted_user
                                        FROM `submissions`
                                        JOIN `submission_team` ON (`submissions`.`id` = `submission_team`.`submission_id`)
                                        JOIN `users` ON (`submissions`.`user_id` = `users`.`id`)
                                        WHERE `submissions`.`status` = :status
                                            AND `submission_team`.`status` = :status
                                            AND `reference_id` = :assignment_id
                                            AND `submissions`.`type` = :type
                                            AND `submission_team`.`user_id` = :user_id");
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result) > 0) {

                    $resultRow = array_pop($result);
                    $submissionId = $resultRow['id'];
                    //this query will give the details of file
                    $stmt = $db->prepare("SELECT `message_attachments`.`file_name`,
                                        `message_attachments`.`file_size`,
                                        `message_attachments`.`file_type`,
                                        `message_attachments`.`internal_file_name` AS file_path

                                        FROM `submissions`
                                        JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id`)
                                        JOIN `message_attachments`ON (`messages`.`id`=`message_attachments`.`message_id`)

                                        WHERE `submissions`.`id` = :submission_id
                                        AND `submissions`.`status` = :status");
                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $fileDetails = array();
                    foreach ($result as $oneFileDetails) {
                        $fileName = $oneFileDetails['file_name'];
                        $fileInternalName = $oneFileDetails['file_path'];
                        $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
                        array_push($fileDetails, array(
                            "name" => $fileName,
                            "url" => $filePath
                        ));
                    }
                    $currentAssignment['submission_status'] = "submitted";

                    $stmt = $db->prepare("SELECT `users`.`id`, CONCAT(`name`, ' ', `last_name`) AS name
                                        FROM `submission_team`
                                            JOIN `users` ON (`submission_team`.`user_id` = `users`.`id`)
                                        WHERE `submission_team`.`submission_id` = :submission_id
                                            AND `submission_team`.`status` = :status
                                            AND `user_id` != :user_id");

                    $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $team = array();
                    if(count($result) > 0){
                        foreach($result as $teamMember){
                            array_push(
                                $team,
                                array(
                                    "name" => $teamMember['name']
                                )
                            );
                        }
                    }
                    array_push(
                        $team,array(
                            "name" => $resultRow['submitted_user']
                        )
                    );
                    $currentAssignment['submission_details'] = array(
                        "submission_id" => $submissionId,
                        "uploaded_documents" => $fileDetails,
                        "submission_type" => "TEAM",
                        "other_team_members" => $team
                    );
                }
                else{
                    $currentAssignment['submission_status'] = "not_submitted" ;
                    $currentAssignment['assignment_type'] = "SUBJECT" ;
                    $currentAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);
                }

            }
                array_push($assignments, $currentAssignment);

        }
            return $assignments;
        }



    /**
     * Get the list of sessions of the given course
     * Only those sessions whose scheduled date was < today is returned
     * @param type $course_id
     * @return array({session_id, session_index, session_name})
     */
    private static function getSessionsOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT  `course_sessions`.`session_id`,
                                `session_index`, `session_name`
                            FROM `course_sessions`
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :course_id
                                AND `course_session_schedules`.`date` < :today
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `session_index` ASC ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':today', date('Y-m-d'), PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $sessions;
    }

    /**
     * Get the list of Participants of the given course
     * @param type $course_id
     * @return array({id, name, last_name})
     */
    private static function getParticipantsOfTheCourse($course_id) {
        $db = static::getDB();

//Get the list participants of the course
        $stmt = $db->prepare("SELECT `users`.`id`,`name`, `last_name`, `profile_pic_binary`
                                FROM `user_to_course_mapping`
                                    JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                                WHERE `course_id` = :course_id
                                    AND `user_to_course_mapping`.`role` = :role
                                    AND `user_to_course_mapping`.`status` = :status
                                    AND `users`.`status` = :status
                                ORDER BY `users`.`name` ASC ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', "PARTICIPANT", PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $participants;
    }

    private static function getTheIndividualSubmissionsOfTheUser($user_id, $session_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                `subject_assignments`.`id` AS assignmentId,
                                `subject_assignments`.`name`,
                                `subject_assignments`.`description`,
                                `submissions`.`id` AS submission_id,
                                `submission_type`,
                                `thread_id`,
                                `submissions`.`user_id` AS learnersUserId,
                                `submissions`.`submission_status`,
                                `submissions`.`no_of_reviews`
                            FROM
                                `course_session_to_topic_mapping`
                            JOIN `subject_topics` ON(
                                    `course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`
                                )
                            JOIN `subject_assignments` ON(
                                    `subject_topics`.`id` = `subject_assignments`.`associated_id`
                                    AND `subject_assignments`.`associated_to` = 'TOPIC'
                                    AND `subject_assignments`.`status` = :status
                                )
                            JOIN `submissions` ON(
                                    `subject_assignments`.`id` = `submissions`.`reference_id`
                                    AND `submissions`.`type` = :type
                                    AND `submissions`.`submission_type` = :submission_type
                                    AND `submissions`.`status` = :status
                                    AND `submissions`.`user_id` = :user_id
                                )
                            WHERE
                                `course_session_to_topic_mapping`.`session_id` = :session_id
                                AND `course_session_to_topic_mapping`.`topic_type` = :topic_type
                                AND `course_session_to_topic_mapping`.`status` = :status
                                AND `subject_topics`.`status` = :status ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':topic_type', 'SUBJECT_TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':submission_type', 'INDIVIDUAL', PDO::PARAM_STR);
        $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $submissionsWithFileDetails = array();
        foreach ($submissions as $submission){

            $latestSubmission = static::getLatestSubmissionDetails($submission['submission_id']);
            $review_max_score = static::getMaxReviewPointOfAssignment($submission['assignmentId']);

            if(count($latestSubmission) > 0){

                array_push($submissionsWithFileDetails, array(
                    "assignmentId" => $submission['assignmentId'],
                    "submission_id"=> $submission['submission_id'],
                    "assignmentMessageThreadId" => $submission['thread_id'],
                    "learnersUserId" => $submission['learnersUserId'],
                    "submission_status" => $submission['submission_status'],
                    "no_of_reviews" => $submission['no_of_reviews'],
                    "review_max_score"=>$review_max_score,
                    "assignment_name" => $submission['name'],
                    "assignment_description" => $submission['description'],
                    "submission_type" => $submission['submission_type'],
                    "submitted_files" => $latestSubmission['submitted_files'],
                    "submitted_description"=>$latestSubmission['latestSubmission']
                ));
            }

        }
        return $submissionsWithFileDetails;
    }

    private static function getTeamSubmittedAssignmentsOfTheSession($session_id) {
        $db = static::getDB();
        //Get the list of submissions by the user

        $stmt = $db->prepare("SELECT
                                `subject_assignments`.`id` AS assignmentId,
                                `subject_assignments`.`name`,
                                `user_id`,
                                'subject_assignment' AS assignment_type,
                                `subject_assignments`.`description`,
                                `submissions`.`id` AS submission_id,
                                `thread_id`,
                                `submissions`.`user_id` AS learnersUserId,
                                `submissions`.`submission_status`,
                                `submissions`.`no_of_reviews`,
                                (
                                    SELECT
                                        GROUP_CONCAT(`user_id`)
                                    FROM
                                        `submission_team`
                                    WHERE
                                        `submission_id` = `submissions`.`id`
                                        AND `status` = :status
                                    GROUP BY
                                        `submission_id`
                                ) AS Team
                            FROM
                                `course_session_to_topic_mapping`
                            JOIN `subject_topics` ON
                                (
                                    `course_session_to_topic_mapping`.`topic_type` = :topic_type
                                    AND `course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`
                                )
                            JOIN `subject_assignments` ON(
                                    `subject_topics`.`id` = `subject_assignments`.`associated_id`
                                    AND `subject_assignments`.`associated_to` = :association_type
                                )

                            JOIN `submissions` ON(
                                    `subject_assignments`.`id` = `submissions`.`reference_id`
                                    AND `submissions`.`type` = :type
                                    AND `submissions`.`submission_type` = :submission_type
                                    AND `submissions`.`status` = :status
                                )
                            WHERE
                                `course_session_to_topic_mapping`.`session_id` = :session_id
                                AND `course_session_to_topic_mapping`.`status` = :status
                                AND `subject_topics`.`status` = :status
                                AND `subject_assignments`.`status` = :status
                                AND `submissions`.`status` = :status
                            ORDER BY
                                `submissions`.`id` ASC ");
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':topic_type', 'SUBJECT_TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':association_type', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
        $stmt->bindValue(':submission_type', 'TEAM', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userWiseSubmissions = array();
        foreach ($submissions as $submission){

            $latestSubmission = static::getLatestSubmissionDetails($submission['submission_id']);
            $review_max_score = static::getMaxReviewPointOfAssignment($submission['assignmentId']);

            $userId = $submission['user_id'];
            $team = explode(',', $submission['Team']);

            $submissionDetails = array(
                "assignmentId" => $submission['assignmentId'],
                "submission_id"=> $submission['submission_id'],
                "assignmentMessageThreadId" => $submission['thread_id'],
                "learnersUserId" => $submission['learnersUserId'],
                "submission_status" => $submission['submission_status'],
                "no_of_reviews" => $submission['no_of_reviews'],
                "review_max_score"=>$review_max_score,
                "assignment_name" => $submission['name'],
                "submission_type" => "TEAM",
                "otherTeamMembers" => $team,
                "submitted_files" => $latestSubmission['submitted_files'],
                "submitted_description"=>$latestSubmission['latestSubmission']
            );
            if(isset($userWiseSubmissions[$userId])){
                array_push($userWiseSubmissions[$userId], $submissionDetails);
            }else{
                $userWiseSubmissions[$userId][0] = $submissionDetails;
            }

            foreach ($team as $index => $teamMember){
                $copyOfTeam = $team;
                $copyOfTeam[$index] = $userId;
                $copyOfSubmission = $submissionDetails;
                $copyOfSubmission['otherTeamMembers'] = $copyOfTeam;

                if(isset($userWiseSubmissions[$teamMember])){
                    array_push($userWiseSubmissions[$teamMember], $copyOfSubmission);
                }else{
                    $userWiseSubmissions[$teamMember][0] = $copyOfSubmission;
                }
            }
        }
        return $userWiseSubmissions;
    }

    public static function loadModuleAndSubjectSubmittedAssignments($course_id,$subjectId){

        $participants = static::getParticipantsOfTheCourse($course_id);
        if(count($participants) === 0){
            return $participants;
        }

        $sessionwiseAssignmentData = array();

        $participantsData = array();
        foreach ($participants as $participant){

            $user_id = $participant['id'];
            $submittedAssignmentsOfTheUser = static::getTheIndividualSubmissionsOfModuleAndSubjectSubmittedAssignmentsOfTheUser($user_id);
            $teamSumissions = static::getTheTeamSubmissionsOfModuleAndSubjectSubmittedAssignmentsOfTheUser($user_id);

            if(isset($teamSumissions[$user_id])){
                foreach ($teamSumissions[$user_id] as $oneAssignment){
                    $team = $oneAssignment['otherTeamMembers'];
                    $teamMemberDetails = array();
                    foreach ($team as $memberId){
                        foreach ($participants as $member){
                            if($member['id'] == $memberId){
                                $memberName = $member['name'].(isset($member['last_name'])?" ".$member['last_name']: "");
                                array_push($teamMemberDetails, $memberName);
                            }
                        }
                    }
                    $oneAssignment['otherTeamMembers'] = $teamMemberDetails;
                    array_push($submittedAssignmentsOfTheUser, $oneAssignment);
                }
            }
            if(count($submittedAssignmentsOfTheUser) > 0){
                array_push($participantsData, array(
                    "id" => $participant['id'],
                    "name" => $participant['name'].(isset($participant['last_name'])?" ".$participant['last_name']: ""),
                    "assignments" => $submittedAssignmentsOfTheUser
                ));
            }
        }
        array_push($sessionwiseAssignmentData, array(
            "session_id" => 0,
            "session_index" => 0,
            "session_name" => 'PROGRAM',
            "participants" => $participantsData
        ));
        return array(
            "participants_details" => $participants,
            "session_wise_assignment_data" => $sessionwiseAssignmentData
        );


    }

    private static function getTheIndividualSubmissionsOfModuleAndSubjectSubmittedAssignmentsOfTheUser($user_id){
        $db = static::getDB();
        //Get the list of submission by the user
        $stmt = $db->prepare("SELECT `subject_assignments`.`id` AS assignmentId,
                                `name`,
                                `description`,
                                `submissions`.`submission_type`,
                                `submissions`.`id` AS submission_id,
                                `thread_id`,
                                `submissions`.`user_id` AS learnersUserId,
                                `submissions`.`submission_status`,
                                `submissions`.`no_of_reviews`
                                FROM `subject_assignments`
                                    JOIN `submissions` ON (`subject_assignments`.`id` = `submissions`.`reference_id` AND `submissions`.`user_id`=:user_id)
                                WHERE `submissions`.`type` = :type
                                    AND `subject_assignments`.`associated_to` != :associated_to
                                    AND `submissions`.`submission_type`=:submission_type
                                    AND `submissions`.`status` = :status
                                    AND `subject_assignments`.`status` = :status");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':submission_type', 'INDIVIDUAL', PDO::PARAM_STR);
        $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $submissionsWithFileDetails = array();
        foreach ($submissions as $submission){

            $latestSubmission = static::getLatestSubmissionDetails($submission['submission_id']);
            $review_max_score = static::getMaxReviewPointOfAssignment($submission['assignmentId']);

            if(count($latestSubmission) > 0){

                array_push($submissionsWithFileDetails, array(
                    "assignmentId" => $submission['assignmentId'],
                    "submission_id"=> $submission['submission_id'],
                    "assignmentMessageThreadId" => $submission['thread_id'],
                    "learnersUserId" => $submission['learnersUserId'],
                    "submission_status" => $submission['submission_status'],
                    "no_of_reviews" => $submission['no_of_reviews'],
                    "review_max_score"=>$review_max_score,
                    "assignment_name" => $submission['name'],
                    "assignment_description" => $submission['description'],
                    "submission_type" => $submission['submission_type'],
                    "submitted_files" => $latestSubmission['submitted_files'],
                    "submitted_description"=>$latestSubmission['latestSubmission']
                ));
            }

        }
        return $submissionsWithFileDetails;
    }


    private static function getTheTeamSubmissionsOfModuleAndSubjectSubmittedAssignmentsOfTheUser($user_id){
        $db = static::getDB();
        //Get the list of submission by the user
        $stmt = $db->prepare("SELECT `subject_assignments`.`id` AS assignmentId,
                            `name`,
                            `user_id`,
                            `description`,
                            `submissions`.`submission_type`,
                            `submissions`.`id` AS submission_id,
                            `thread_id`,
                            `submissions`.`user_id` AS learnersUserId,
                            `submissions`.`submission_status`,
                            `submissions`.`no_of_reviews`,
                                (
                                    SELECT
                                        GROUP_CONCAT(`user_id`)
                                    FROM
                                        `submission_team`
                                    WHERE
                                        `submission_id` = `submissions`.`id`
                                        AND `status` = :status
                                    GROUP BY
                                        `submission_id`
                                ) AS Team
                                FROM `subject_assignments`
                                    JOIN `submissions` ON (`subject_assignments`.`id` = `submissions`.`reference_id` AND `submissions`.`user_id`=:user_id)
                                WHERE `submissions`.`type` = :type
                                    AND `subject_assignments`.`associated_to` != :associated_to
                                    AND `submissions`.`submission_type`=:submission_type
                                    AND `submissions`.`status` = :status
                                    AND `subject_assignments`.`status` = :status");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':submission_type', 'TEAM', PDO::PARAM_STR);
        $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userWiseSubmissions = array();
        foreach ($submissions as $submission){

            $latestSubmission = static::getLatestSubmissionDetails($submission['submission_id']);
            $review_max_score = static::getMaxReviewPointOfAssignment($submission['assignmentId']);


            $userId = $submission['user_id'];
            $team = explode(',', $submission['Team']);

            $submissionDetails = array(
                "assignmentId" => $submission['assignmentId'],
                "submission_id"=> $submission['submission_id'],
                "assignmentMessageThreadId" => $submission['thread_id'],
                "learnersUserId" => $submission['learnersUserId'],
                "submission_status" => $submission['submission_status'],
                "no_of_reviews" => $submission['no_of_reviews'],
                "review_max_score"=>$review_max_score,
                "assignment_name" => $submission['name'],
                "submission_type" => "TEAM",
                "otherTeamMembers" => $team,
                "submitted_files" => $latestSubmission['submitted_files'],
                "submitted_description"=>$latestSubmission['latestSubmission']
            );
            if(isset($userWiseSubmissions[$userId])){
                array_push($userWiseSubmissions[$userId], $submissionDetails);
            }else{
                $userWiseSubmissions[$userId][0] = $submissionDetails;
            }

            foreach ($team as $index => $teamMember){
                $copyOfTeam = $team;
                $copyOfTeam[$index] = $userId;
                $copyOfSubmission = $submissionDetails;
                $copyOfSubmission['otherTeamMembers'] = $copyOfTeam;

                if(isset($userWiseSubmissions[$teamMember])){
                    array_push($userWiseSubmissions[$teamMember], $copyOfSubmission);
                }else{
                    $userWiseSubmissions[$teamMember][0] = $copyOfSubmission;
                }
            }
        }
        return $userWiseSubmissions;
    }

    private static function getMaxReviewPointOfAssignment($assignmentId) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `id`,`max_score`
                            FROM `reward_point_criterias`
                            WHERE `reference_id` = :reference_id
                                AND `criteria` = :criteria");

        $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindValue(':criteria', 'ASSIGNMENT_REVIEW', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $review_max_score = $results[0]['max_score'];
        return $review_max_score;
    }

    private static function getLatestSubmissionDetails($submissionId) {
        $db = static::getDB();

        // Get the list of submissions by the user
        $stmt = $db->prepare("SELECT `messages`.`id`,
                                `messages`.`message_body` AS message
                            FROM `submissions`
                                JOIN `messages` ON (`submissions`.`thread_id` = `messages`.`thread_id` )
                                JOIN `user_to_course_mapping` ON (`messages`.`sender_user_id` = `user_to_course_mapping`.`user_id`)
                            WHERE `submissions`.`id` = :submission_id
                                AND `submissions`.`status` = :status
                                AND `user_to_course_mapping`.`role` != 'FACILITATOR'
                            ORDER BY `messages`.`id` DESC

                            ");
        $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $latestSubmission = $result[0];

        $stmt = $db->prepare("SELECT
                                `file_name`,
                                `file_size`,
                                `file_type`,
                                `internal_file_name` AS file_path
                            FROM
                                `message_attachments`
                            WHERE
                                `message_id` = :message_id
                                AND `status` = :status ");

        $stmt->bindValue(':message_id', $latestSubmission['id'], PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $submittedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $submittedFilesArray = array();
        foreach ($submittedFiles as $submittedFile){
            $fileName = $submittedFile['file_name'];
            $fileInternalName = $submittedFile['file_path'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
            array_push($submittedFilesArray, array(
                "file_name" => $fileName,
                "file_path" => $filePath
            ));
        }
        return array(
            "submitted_files"=>$submittedFilesArray,
            "latestSubmission"=>$latestSubmission['message']
        );
    }

    public static function getSummaryDataOfUser($course_id,$user_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `course_sessions`.`session_id`, `session_index`, `session_name`,
                `name` AS `assignment`,`reward_point_criterias`.`max_score` AS `points_allocated`,
                (SELECT `score` FROM `reward_points_scored` WHERE `user_id` = :user_id AND `criteria_id` = `reward_point_criterias`.`id` LIMIT 0,1) AS `points_earned`
            FROM `course_sessions`
                JOIN `course_session_assignments` ON (`course_sessions`.`session_id` = `course_session_assignments`.`session_id`)
                JOIN `reward_point_criterias` ON (`course_session_assignments`.`id` = `reward_point_criterias`.`reference_id` )
            WHERE `course_sessions`.`course_id` = :course_id
                AND `reward_point_criterias`.`course_id` = :course_id
                AND `reward_point_criterias`.`criteria` = :criteria
                AND `course_sessions`.`status` = :status
                AND `course_session_assignments`. `status` = :status
                AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->bindValue(':criteria', 'ASSIGNMENT', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $assignments = array();

        $pendingAssignments =array();
        foreach($result as $oneAssignment) {

            $session_id = $oneAssignment['session_id'];
            if (!isset($assignments[$session_id])) {
                $assignments[$session_id] = array(
                    "session_index" => $oneAssignment['session_index'],
                    "session_name" => $oneAssignment['session_name'],
                    "points_earned" => 0,
                    "earnings" => array(),
                    "points_available" => 0,
                    "available_assignments" =>array()
                );
            }

            if($oneAssignment['points_earned']== NULL){

                array_push($assignments[$session_id]['available_assignments'],
                    array(
                        "Assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                    )
                );
                $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => $oneAssignment['session_index'],
                    "assignment" => $oneAssignment['assignment'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            }
            else{

                array_push($assignments[$session_id]['earnings'],
                    array(
                        "assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                        "points_earned" => $oneAssignment['points_earned']
                    )
                );
                $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];
            }
        }
        $dataForChart = array();
        foreach($assignments as $assignment){
            array_push($dataForChart, $assignment);
        }

        return array(
            "dataForChart" => $dataForChart,
            "pendingAssignments" => $pendingAssignments
        );

    }


    public static function getSubjectAssignmentSummaryOfUser($sessionId, $user_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT
                                `course_session_to_topic_mapping`.`session_id`,
                                `course_sessions`.`session_index`,
                                `course_sessions`.`session_name`,
                                `subject_assignments`.`name` AS `assignment`,
                                `reward_point_criterias`.`max_score` AS `points_allocated`,
                                (SELECT `score` FROM `reward_points_scored` WHERE `user_id` = :user_id AND `criteria_id` = `reward_point_criterias`.`id` LIMIT 0,1) AS `points_earned`

                            FROM `course_session_to_topic_mapping`
                                JOIN `subject_assignments` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_assignments`.`associated_id` )
                                JOIN `course_sessions` ON (`course_sessions`.`session_id` = `course_session_to_topic_mapping`.`session_id`)
                                JOIN `reward_point_criterias` ON (`subject_assignments`.`id` = `reward_point_criterias`.`reference_id` )
                            WHERE `subject_assignments`.`associated_to` = :associated_to
                                AND `course_session_to_topic_mapping`.`status` = :status
                                AND `course_session_to_topic_mapping`.`session_id` = :session_id
                                AND `reward_point_criterias`.`criteria` = :criteria
                                AND `subject_assignments`.`status` = :status");

        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->bindValue(':criteria', 'SUBJECT_ASSIGNMENT', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();

        $pendingAssignments =array();
        foreach($result as $oneAssignment) {

            $session_id = $oneAssignment['session_id'];
            if (!isset($assignments[$session_id])) {
                $assignments[$session_id] = array(
                    "session_index" => $oneAssignment['session_index'],
                    "session_name" => $oneAssignment['session_name'],
                    "points_earned" => 0,
                    "earnings" => array(),
                    "points_available" => 0,
                    "available_assignments" =>array()
                );
            }

            if($oneAssignment['points_earned']== NULL){

                array_push($assignments[$session_id]['available_assignments'],
                    array(
                        "Assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                    )
                );
                $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => $oneAssignment['session_index'],
                    "assignment" => $oneAssignment['assignment'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            }
            else{

                array_push($assignments[$session_id]['earnings'],
                    array(
                        "assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                        "points_earned" => $oneAssignment['points_earned']
                    )
                );
                $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];
            }
        }
        $dataForChart = array();
        foreach($assignments as $assignment){
            array_push($dataForChart, $assignment);
        }

        return array(
            "dataForChart" => $dataForChart,
            "pendingAssignments" => $pendingAssignments
        );

    }

    public static function getModuleAndSubjectAssignmentSummaryOfUser($subject_id, $user_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT
                                `subject_assignments`.`name` AS `assignment`,
                                `reward_point_criterias`.`max_score` AS `points_allocated`,
                                (SELECT `score` FROM `reward_points_scored` WHERE `user_id` = :user_id AND `criteria_id` = `reward_point_criterias`.`id` LIMIT 0,1) AS `points_earned`
                            FROM `subject_assignments`
                                JOIN `reward_point_criterias` ON (`subject_assignments`.`id` = `reward_point_criterias`.`reference_id` )
                            WHERE `subject_assignments`.`associated_to` != :associated_to
                                AND `subject_assignments`.`subject_id` = :subjectId
                                AND `reward_point_criterias`.`criteria` = :criteria
                                AND `subject_assignments`.`status` = :status");

        $stmt->bindValue(':associated_to', 'TOPIC', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subjectId', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->bindValue(':criteria', 'SUBJECT_ASSIGNMENT', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $assignments = array();
        $pendingAssignments =array();
        foreach($result as $oneAssignment) {

            $session_id = "PROGRAM";
            if (!isset($assignments[$session_id])) {
                $assignments[$session_id] = array(
                    "session_index" => 'PROGRAM',
                    "session_name" => "MODULE AND ENTIRE_SUBJECT",
                    "points_earned" => 0,
                    "earnings" => array(),
                    "points_available" => 0,
                    "available_assignments" =>array()
                );
            }

            if($oneAssignment['points_earned']== NULL){

                array_push($assignments[$session_id]['available_assignments'],
                    array(
                        "Assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                    )
                );
                $assignments[$session_id]['points_available'] += $oneAssignment['points_allocated'];

                array_push($pendingAssignments, array(
                    "session_index" => 'PROGRAM',
                    "assignment" => $oneAssignment['assignment'],
                    "points_allocated" => $oneAssignment['points_allocated']
                ));
            }
            else{

                array_push($assignments[$session_id]['earnings'],
                    array(
                        "assignment" => $oneAssignment['assignment'],
                        "points_allocated" => $oneAssignment['points_allocated'],
                        "points_earned" => $oneAssignment['points_earned']
                    )
                );
                $assignments[$session_id]['points_earned'] += $oneAssignment['points_earned'];
            }
        }
        $dataForChart = array();
        foreach($assignments as $assignment){
            array_push($dataForChart, $assignment);
        }

        return array(
            "dataForChart" => $dataForChart,
            "pendingAssignments" => $pendingAssignments
        );

    }

    public static function getAssignmentsOfSubject($subjectId){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `subject_assignments`.`id`,
                      `subject_assignments`.`name`,
                      `subject_assignments`.`description`,
                     `subject_assignments`.`associated_to`,
                     modules.`module_index`, `module_name`,
                     topic_module.topic_module_index, topic_module_name
                FROM `subject_assignments`
                JOIN `subjects` ON (`subject_assignments`.`subject_id` = `subjects`.`id`)
                LEFT JOIN(
                SELECT
                    `module_id`,
                    `module_index`,
                    `module_name`
                FROM `subject_modules`
                WHERE `subject_id` = :subject_id
                    AND `status` = :status
                ) AS modules ON ( `subject_assignments`.`associated_to` = 'MODULE' AND `subject_assignments`.`associated_id` = modules.module_id )

                LEFT JOIN(
                SELECT  `name` AS module_topic,
                	`subject_topics`.`id` AS module_topic_id,
                	`module_index` AS topic_module_index,
                	`module_name` AS topic_module_name
                FROM `subject_topics`
                JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                WHERE `subject_id` = :subject_id
                AND `subject_topics`.`status` =:status
                AND `subject_modules`.`status` =:status
            ) AS topic_module ON ( `subject_assignments`.`associated_to` = 'TOPIC' AND `subject_assignments`.`associated_id` = topic_module.module_topic_id )

                WHERE `subject_assignments`.`subject_id`= :subject_id
                AND `subject_assignments`.`status` = :status
                AND `subjects`.`status` = :status");

        $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array();
        foreach($result as $oneAssignment){
            $references = static::getReferencesOfTheAssignment($oneAssignment['id']);
            $assignment = array(
                'id'=>$oneAssignment['id'],
                'name'=>$oneAssignment['name'],
                'description'=>$oneAssignment['description'],
                'associated_to'=>$oneAssignment['associated_to'],
                'module_index'=>$oneAssignment['module_index'],
                'module_name'=>$oneAssignment['module_name'],
                'topic_module_index'=>$oneAssignment['topic_module_index'],
                'topic_module_name'=>$oneAssignment['topic_module_name'],
                'reference'=> $references
            );
            array_push($assignments,$assignment);
        }
        return $assignments;
    }

    private static function  getReferencesOfTheAssignment($assignmentId){

        $db = static::getDB();
        $stmt = $db->prepare("SELECT `subject_resource_details`.`file_name` AS `name`, `subject_resource_details`.`source` ,`subject_resource_details`.`link` AS `link`
                                FROM `subject_assignment_references`
                                JOIN `subject_resource_details` ON (`subject_resource_details`.`id` =  `subject_assignment_references`.`resource_id`)
                            WHERE `subject_assignment_references`.`assignment_id` = :assignmentId
                            AND `subject_resource_details`.`status` = :status
                            AND `subject_assignment_references`.`status` = :status");

        $stmt->bindValue(':assignmentId', $assignmentId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resource_list = array();
        foreach($result as $reference){
            if($reference['source'] === 'INTERNAL_FILE'){
                $resource_link = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $reference['link'], $reference['name']);
            }
            else{
                $resource_link = $reference['link'];
            }
            array_push($resource_list, array(
                "document_name" => $reference['name'],
                "document_link" => $resource_link
            ));
        }
        return $resource_list;

    }

    public static function addNewAssignment($data,$user_id){
        // echo "string";exit;

        $subject_id = $data['subject_id'];
        $assignmentName = $data['name'];
        $description = $data['description'];
        $available_to = $data['available_to'];
        $submission_max_score = $data['submission_max_score'];
        $review_max_score = $data['review_max_score'];

        if($data['associated_to'] === "Subject"){
            $resource_for = "PROGRAM";
            $assignment_associated_to = "ENTIRE_SUBJECT";
            $reference_id = 0;
        }
        elseif($data['associated_to'] === "Module"){
            $resource_for = "MODULE";
            $assignment_associated_to = "MODULE";
            $reference_id = $data['associated_module_id'];
        }
        else{
            $resource_for = "TOPIC";
            $assignment_associated_to = "TOPIC";
            $reference_id = $data['associated_topic_id'];
        }

        //Extract the detaild of the resource

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        //insert into resources table
        $stmt = $db->prepare("INSERT INTO `subject_assignments`
                            (`name`, `description`, `subject_id`, `associated_to`, `associated_id`, `available_to`, `facilitator_user_id`, `status`)
                            VALUES(
                                :name,
                                :description,
                                :subject_id,
                                :associated_to,
                                :associated_id,
                                :available_to,
                                :facilitator_user_id,
                                :status
                            )");
                            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
                            $stmt->bindValue(':name', $assignmentName, PDO::PARAM_STR);
                            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                            $stmt->bindValue(':associated_to', $assignment_associated_to, PDO::PARAM_STR);
                            $stmt->bindValue(':associated_id', $reference_id, PDO::PARAM_INT);
                            $stmt->bindValue(':available_to', $available_to, PDO::PARAM_STR);
                            $stmt->bindValue(':facilitator_user_id', $user_id, PDO::PARAM_INT);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                            if(!$stmt->execute()){
                                return array(
                                    "status" => "Error",
                                    "message" => "Error"
                                );
                            }
                            $assignment_id = $db->lastInsertId();


                            $stmt = $db->prepare("INSERT INTO `reward_point_criterias`
                                                (`course_id`, `criteria`, `reference_id`, `max_score`,`status`)
                                                VALUES(
                                                    :course_id,
                                                    :criteria,
                                                    :reference_id,
                                                    :max_score,
                                                    :status
                                                )");
                                                $stmt->bindValue(':course_id', 0, PDO::PARAM_INT);
                                                $stmt->bindValue(':criteria', "ASSIGNMENT_SUBMISSION", PDO::PARAM_STR);
                                                $stmt->bindValue(':reference_id', $assignment_id, PDO::PARAM_STR);
                                                $stmt->bindValue(':max_score', $submission_max_score, PDO::PARAM_STR);
                                                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                                                if(!$stmt->execute()){
                                                    return array(
                                                        "status" => "Error",
                                                        "message" => "Error"
                                                    );
                                                }

                            $stmt = $db->prepare("INSERT INTO `reward_point_criterias`
                                                (`course_id`, `criteria`, `reference_id`, `max_score`,`status`)
                                                VALUES(
                                                    :course_id,
                                                    :criteria,
                                                    :reference_id,
                                                    :max_score,
                                                    :status
                                                )");
                                                $stmt->bindValue(':course_id', 0, PDO::PARAM_INT);
                                                $stmt->bindValue(':criteria', "ASSIGNMENT_REVIEW", PDO::PARAM_STR);
                                                $stmt->bindValue(':reference_id', $assignment_id, PDO::PARAM_STR);
                                                $stmt->bindValue(':max_score', $review_max_score, PDO::PARAM_STR);
                                                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                                                if(!$stmt->execute()){
                                                    return array(
                                                        "status" => "Error",
                                                        "message" => "Error"
                                                    );
                                                }



                            if(isset($data['resource_details']) && count($data['resource_details'])>0){
                                foreach($data['resource_details'] as $resource_detail){

                                    $resource_name = $resource_detail['name'];
                                    $form = $resource_detail['form'];
                                    $category = $resource_detail['category'];
                                    $icon = $resource_detail['icon'];

                                    if($form === "File"){
                                        $file_details = $resource_detail['uploadedFileDetails'];
                                        $source = "INTERNAL_FILE";
                                        $file_name =  $file_details['fileName'];
                                        $link =  $file_details['internalFileName'];
                                        }
                                    else{
                                        $source = "EXTERNAL_LINK";
                                        $file_name = $resource_name;
                                        $link = $resource_detail['resourceLink'];
                                    }


                                    $stmt = $db->prepare("INSERT INTO `subject_resources`
                                                             (`subject_id`, `name`, `type`, `thumbnail`,`resource_for`, `reference_id`, `thumbnail_source`,`thumbnail_file_name`, `status`)
                                                            VALUES(
                                                                :subject_id,
                                                                :resource_name,
                                                                :category,
                                                                :icon,
                                                                :associated_to,
                                                                :associated_id,
                                                                :thumbnail_source,
                                                                :thumbnail_file_name,
                                                                :status
                                                             )");
                                         $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
                                         $stmt->bindValue(':resource_name', $resource_name, PDO::PARAM_STR);
                                         $stmt->bindValue(':category', $category, PDO::PARAM_STR);
                                         $stmt->bindValue(':icon', $icon, PDO::PARAM_STR);
                                         $stmt->bindValue(':associated_to', $resource_for, PDO::PARAM_STR);
                                         $stmt->bindValue(':associated_id', $reference_id, PDO::PARAM_INT);
                                         $stmt->bindValue(':thumbnail_source', "ICON", PDO::PARAM_STR);
                                         $stmt->bindValue(':thumbnail_file_name', "", PDO::PARAM_STR);
                                         $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                                         if(!$stmt->execute()){
                                             $db->rollBack();
                                             return array(
                                                 "status" => "Error",
                                                 "message" => "Could not insert the resource details"
                                             );
                                         }



                                    $resource_id = $db->lastInsertId();

                                    $stmt = $db->prepare("INSERT INTO `subject_resource_details`
                                                             (`resource_id`, `source`, `file_name`, `link`,`status`)
                                                            VALUES(
                                                                :resource_id,
                                                                :source,
                                                                :file_name,
                                                                :link,
                                                                :status
                                                             )");
                                         $stmt->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
                                         $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
                                         $stmt->bindValue(':source', $source, PDO::PARAM_STR);
                                         $stmt->bindValue(':link', $link, PDO::PARAM_STR);
                                         $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                                         if(!$stmt->execute()){
                                             $db->rollBack();
                                             return array(
                                                 "status" => "Error",
                                                 "message" => "Could not insert the resource details"
                                             );
                                         }


                                    $stmt = $db->prepare("INSERT INTO `subject_assignment_references`
                                                             (`assignment_id`, `resource_id`,`status`)
                                                            VALUES(
                                                                :assignment_id,
                                                                :resource_id,
                                                                :status
                                                             )");
                                         $stmt->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
                                         $stmt->bindValue(':assignment_id', $assignment_id, PDO::PARAM_INT);
                                         $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                                         if(!$stmt->execute()){
                                             $db->rollBack();
                                             return array(
                                                 "status" => "Error",
                                                 "message" => "Could not insert the resource details"
                                             );
                                         }



                                }
                            }


                            $db->commit();
                            return array(
                                "status" => "Success",
                                "message" => ""
                            );
    }

    public static function DeleteAssignment($assignmentId){

        $db = static::getDB();
        $stmt = $db->prepare("UPDATE `subject_assignments`
                            set `subject_assignments`.`status` = :status
                            WHERE `id` = :assignment_id ");
        $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);

        if($stmt->execute()){
            return array(
                "status"=>"Success"
            );
        }
        else{
            return array(
                "status"=>"Error",
                "error"=>"There was an error while removing this assignment. Please reload and try again."
            );
        }
    }

    /**
     * Get the reward points of the user for a chart in the learner's dashboard
     * @param type $subject_id
     * @param type $course_id
     * @param type $user_id
     * @return type
     */
    public static function getRewardPointsDataOfTheUser($subject_id, $course_id, $user_id) {
        $db = static::getDB();
        //Get matrics of session assignments of the user
        $stmt = $db->prepare("SELECT SUM(`reward_point_criterias`.`max_score`) AS pointsAvailable,
                                    SUM(`reward_points_scored`.`score`) AS pointsScored
                            FROM `course_sessions`
                                JOIN `course_session_assignments` ON(
                                    `course_sessions`.`session_id` = `course_session_assignments`.`session_id`
                                )
                                JOIN `reward_point_criterias` ON(
                                    `course_session_assignments`.`id` = `reward_point_criterias`.`reference_id`
                                    AND `reward_point_criterias`.`criteria` = 'ASSIGNMENT'
                                )
                                LEFT JOIN `reward_points_scored` ON(
                                    `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    AND `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                )
                            WHERE
                                `course_sessions`.`course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                                AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $session_assignment_data = array_pop($result);

        //Get the matrics of the subject assignments of the user
        $stmt = $db->prepare("SELECT SUM(`reward_point_criterias`.`max_score`) AS pointsAvailable,
                                SUM(`reward_points_scored`.`score`) AS pointsScored
                            FROM `subject_assignments`
                                JOIN `reward_point_criterias` ON(
                                    `subject_assignments`.`id` = `reward_point_criterias`.`reference_id`
                                    AND `reward_point_criterias`.`criteria` = 'SUBJECT_ASSIGNMENT'
                                )
                                LEFT JOIN `reward_points_scored` ON(
                                    `reward_point_criterias`.`id` = `reward_points_scored`.`criteria_id`
                                    AND `reward_points_scored`.`user_id` = :user_id
                                    AND `reward_points_scored`.`status` = :status
                                )
                            WHERE
                                `subject_assignments`.`subject_id` = :subject_id
                                AND `subject_assignments`.`status` = :status
                                AND `reward_point_criterias`.`status` = :status ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subject_assignment_data = array_pop($result);

        return array(
            "pointsAvailable" => $session_assignment_data['pointsAvailable'] + $subject_assignment_data['pointsAvailable'],
            "pointsScored" => $session_assignment_data['pointsScored'] + $subject_assignment_data['pointsScored']
        );
    }

    public static function resubmitAssignment($submission, $course_id, $user_id, $message_id, $thread_id){
        $assignmentId = $submission['assignmentId'];
        $description = $submission['description'];
        if(isset($submission['attachedFiles'])){
            $attachedFiles = $submission['attachedFiles'];
        }

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        $message_thread = $thread_id;
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `messages`
                            (`thread_id`,`previous_message_id`,`sent_time`,`message_body`,`sender_user_id`,`status`)
                            VALUES(
                                :thread_id,
                                :previous_message_id,
                                :sent_time,
                                :message_body,
                                :sender_user_id,
                                :status
                            )");
                            $stmt->bindValue(':thread_id', $message_thread, PDO::PARAM_INT);
                            $stmt->bindValue(':previous_message_id', $message_id, PDO::PARAM_INT);
                            $stmt->bindValue(':sender_user_id', $user_id, PDO::PARAM_INT);
                            $stmt->bindValue(':sent_time', $now, PDO::PARAM_STR);
                            $stmt->bindValue(':message_body', $description,PDO::PARAM_STR);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                            if(!$stmt->execute()){
                                $db->rollBack();
                                return array(
                                    "status" => "Error",
                                    "message" => "Error"
                                );
                            }

        $message_id = $db->lastInsertId();
        if(isset ($submission['attachedFiles'])){
            foreach($attachedFiles as $attachedFile){
                $internal_file_name = $attachedFile['internalFileName'] ;
                $file_name =  $attachedFile['fileName'];
                $file_size = $attachedFile['fileSize'];
                $file_type = $attachedFile['fileType'];

            $stmt = $db->prepare("INSERT INTO `message_attachments`
                                (`message_id`,`internal_file_name`,`file_name`,`file_size`,`file_type`,`status`)
                                VALUES(
                                    :message_id,
                                    :internal_file_name,
                                    :file_name,
                                    :file_size,
                                    :file_type,
                                    :status
                                )");
                                $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
                                $stmt->bindValue(':internal_file_name', $internal_file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
                                $stmt->bindValue(':file_type', $file_type, PDO::PARAM_STR);
                                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                if(!$stmt->execute()){
                                    $db->rollBack();
                                    return array(
                                        "status" => "Error",
                                        "message" => "Error"
                                    );
                                }
                }
            }
            $db->commit();
            return array(
                "status" => "Success",
                "message_id" => $message_id
            );


    }


    public static function saveAssignment($submission, $course_id, $user_id){

        $assignmentId = $submission['assignmentId'];
        $submissionType = $submission['submissionType'];
        if($submissionType == 'self'){
            $submission_type = 'INDIVIDUAL';
        }
        elseif($submissionType != 'self'){
            $submission_type = 'TEAM';
            $teamMembers = $submission['teamMembers'];
        }
        $description = $submission['description'];
        if(isset($submission['attachedFiles'])){
            $attachedFiles = $submission['attachedFiles'];
        }

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();



        $stmt = $db->prepare("SELECT `name`
                            FROM `subject_assignments`
                            WHERE `id` = :assignmentId");

        $stmt->bindValue(':assignmentId', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $assignment_name = $results[0]['name'];


        $stmt = $db->prepare("SELECT CONCAT(`name`,`last_name`) AS `name`
                                FROM `users`
                                WHERE `id` = :user_id");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $participant_name = $results[0]['name'];

        if(isset($teamMembers)){
            $subject = "Assignment \"$assignment_name\" by $participant_name and Team";
        }
        else{
            $subject = "Assignment \"$assignment_name\" by $participant_name";
        }

        //insert into resources table
        $stmt = $db->prepare("INSERT INTO `message_threads`
                            (`subject`, `course_id`, `type`, `status`)
                            VALUES(
                                :subject,
                                :course_id,
                                :type,
                                :status
                            )");
                            $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                            $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                            if(!$stmt->execute()){
                                $db->rollBack();
                                return array(
                                    "status" => "Error",
                                    "message" => "Error"
                                );
                            }
        $message_thread = $db->lastInsertId();
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `messages`
                            (`thread_id`,`previous_message_id`,`sent_time`,`message_body`,`sender_user_id`,`status`)
                            VALUES(
                                :thread_id,
                                :previous_message_id,
                                :sent_time,
                                :message_body,
                                :sender_user_id,
                                :status
                            )");
                            $stmt->bindValue(':thread_id', $message_thread, PDO::PARAM_INT);
                            $stmt->bindValue(':previous_message_id', 0, PDO::PARAM_INT);
                            $stmt->bindValue(':sender_user_id', $user_id, PDO::PARAM_INT);
                            $stmt->bindValue(':sent_time', $now, PDO::PARAM_STR);
                            $stmt->bindValue(':message_body', $description,PDO::PARAM_STR);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                            if(!$stmt->execute()){
                                $db->rollBack();
                                return array(
                                    "status" => "Error",
                                    "message" => "Error"
                                );
                            }

        $message_id = $db->lastInsertId();
        if(isset ($submission['attachedFiles'])){
            foreach($attachedFiles as $attachedFile){
                $internal_file_name = $attachedFile['internalFileName'] ;
                $file_name =  $attachedFile['fileName'];
                $file_size = $attachedFile['fileSize'];
                $file_type = $attachedFile['fileType'];

            $stmt = $db->prepare("INSERT INTO `message_attachments`
                                (`message_id`,`internal_file_name`,`file_name`,`file_size`,`file_type`,`status`)
                                VALUES(
                                    :message_id,
                                    :internal_file_name,
                                    :file_name,
                                    :file_size,
                                    :file_type,
                                    :status
                                )");
                                $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
                                $stmt->bindValue(':internal_file_name', $internal_file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
                                $stmt->bindValue(':file_type', $file_type, PDO::PARAM_STR);
                                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                if(!$stmt->execute()){
                                    $db->rollBack();
                                    return array(
                                        "status" => "Error",
                                        "message" => "Error"
                                    );
                                }
                }
            }

            $stmt = $db->prepare("INSERT INTO `submissions`
                                (`type`,`reference_id`,`user_id`,`submission_type`,`thread_id`,`submission_timestamp`,`submission_status`,`no_of_reviews`,`status`)
                                VALUES(
                                    :type,
                                    :reference_id,
                                    :user_id,
                                    :submission_type,
                                    :thread_id,
                                    :submission_timestamp,
                                    :submission_status,
                                    :no_of_reviews,
                                    :status
                                )");
                                $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                                $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
                                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                                $stmt->bindValue(':submission_type', $submission_type, PDO::PARAM_STR);
                                $stmt->bindValue(':thread_id', $message_thread, PDO::PARAM_INT);
                                $stmt->bindValue(':no_of_reviews', 0, PDO::PARAM_INT);
                                $stmt->bindValue(':submission_timestamp', $now,PDO::PARAM_STR);
                                $stmt->bindValue(':submission_status', 'SUBMITTED',PDO::PARAM_STR);
                                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                                if(!$stmt->execute()){
                                    $db->rollBack();
                                    return array(
                                        "status" => "Error",
                                        "message" => "Error"
                                    );
                                }
            $submission_id = $db->lastInsertId();

            if(isset($teamMembers)){
                foreach($teamMembers as $teamMember){
                $stmt = $db->prepare("INSERT INTO `submission_team`
                                    (`submission_id`,`user_id`,`status`)
                                    VALUES(
                                        :submission_id,
                                        :user_id,
                                        :status
                                    )");
                                    $stmt->bindValue(':submission_id', $submission_id, PDO::PARAM_INT);
                                    $stmt->bindValue(':user_id', $teamMember, PDO::PARAM_STR);
                                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                    if(!$stmt->execute()){
                                        $db->rollBack();
                                        return array(
                                            "status" => "Error",
                                            "message" => "Error"
                                        );
                                    }
                                }
            }


                        $stmt = $db->prepare("SELECT `id`,`max_score`
                                            FROM `reward_point_criterias`
                                            WHERE `reference_id` = :reference_id
                                                AND `criteria` = :criteria");

                        $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
                        $stmt->bindValue(':criteria', 'ASSIGNMENT_SUBMISSION', PDO::PARAM_STR);
                        $stmt->execute();
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $submission_points = $results[0]['max_score'];
                        $criteria_id = $results[0]['id'];
                        if(isset($teamMembers)){
                            array_push($teamMembers,$user_id);
                        }
                        if(isset($teamMembers)){
                        foreach($teamMembers as $oneMember){
                            $stmt = $db->prepare("INSERT INTO `reward_points_scored`
                                                (`user_id`,`criteria_id`,`score`,`status`)
                                                VALUES(
                                                    :user_id,
                                                    :criteria_id,
                                                    :score,
                                                    :status
                                                )");
                                                $stmt->bindValue(':criteria_id', $criteria_id, PDO::PARAM_INT);
                                                $stmt->bindValue(':score', $submission_points, PDO::PARAM_INT);
                                                $stmt->bindValue(':user_id', $oneMember, PDO::PARAM_STR);
                                                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                                if(!$stmt->execute()){
                                                    $db->rollBack();
                                                    return array(
                                                        "status" => "Error",
                                                        "message" => "Error"
                                                    );
                                                }
                            }
                        }
                        else{
                            $stmt = $db->prepare("INSERT INTO `reward_points_scored`
                                                (`user_id`,`criteria_id`,`score`,`status`)
                                                VALUES(
                                                    :user_id,
                                                    :criteria_id,
                                                    :score,
                                                    :status
                                                )");
                                                $stmt->bindValue(':criteria_id', $criteria_id, PDO::PARAM_INT);
                                                $stmt->bindValue(':score', $submission_points, PDO::PARAM_INT);
                                                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                                                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                                if(!$stmt->execute()){
                                                    $db->rollBack();
                                                    return array(
                                                        "status" => "Error",
                                                        "message" => "Error"
                                                    );
                                                }
                            }


            $db->commit();
            return array(
                "status" => "Success",
                "message_id" => $message_id
            );

    }

    public static function getThreadIdOfAssignment($assignmentId, $user_id, $message_type){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `thread_id`
                                FROM `submissions`
                                WHERE `reference_id` = :reference_id
                                    AND `type` = :type
                                    AND `user_id` = :user_id
                                    AND `status` = :status");

        $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $message_type, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() > 0){
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result[0]['thread_id'];
        }

        $stmt = $db->prepare("SELECT `thread_id`
                            FROM `submissions`
                            JOIN `submission_team` ON (`submissions`.`id` = `submission_team`.`submission_id`)
                            WHERE `reference_id` = :reference_id
                                AND `type` = :type
                                AND `submission_team`.`user_id` = :user_id
                                AND `submissions`.`status` = :status
                                AND `submission_team`.`status` = :status");

        $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $message_type, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result[0]['thread_id'];

    }

    public static function reviewAssignmentSubmission($submission, $submissionId, $thread_id, $user_id, $learnersUserId, $previous_message_id){
        $assignmentId = $submission['assignmentId'];
        $description = $submission['description'];
        $reviewAction = $submission['reviewAction'];
        if($reviewAction == "Accept"){
            $review_points = $submission['reviewPoint'];
        }
        if(isset($submission['attachedFiles'])){
            $attachedFiles = $submission['attachedFiles'];
        }

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        $message_thread = $thread_id;
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `messages`
                            (`thread_id`,`previous_message_id`,`sent_time`,`message_body`,`sender_user_id`,`status`)
                            VALUES(
                                :thread_id,
                                :previous_message_id,
                                :sent_time,
                                :message_body,
                                :sender_user_id,
                                :status
                            )");
                            $stmt->bindValue(':thread_id', $message_thread, PDO::PARAM_INT);
                            $stmt->bindValue(':previous_message_id', $previous_message_id, PDO::PARAM_INT);
                            $stmt->bindValue(':sender_user_id', $user_id, PDO::PARAM_INT);
                            $stmt->bindValue(':sent_time', $now, PDO::PARAM_STR);
                            $stmt->bindValue(':message_body', $description,PDO::PARAM_STR);
                            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                            if(!$stmt->execute()){
                                $db->rollBack();
                                return array(
                                    "status" => "Error",
                                    "message" => "Error"
                                );
                            }

        $message_id = $db->lastInsertId();
        if(isset ($submission['attachedFiles'])){
            foreach($attachedFiles as $attachedFile){
                $internal_file_name = $attachedFile['internalFileName'] ;
                $file_name =  $attachedFile['fileName'];
                $file_size = $attachedFile['fileSize'];
                $file_type = $attachedFile['fileType'];

            $stmt = $db->prepare("INSERT INTO `message_attachments`
                                (`message_id`,`internal_file_name`,`file_name`,`file_size`,`file_type`,`status`)
                                VALUES(
                                    :message_id,
                                    :internal_file_name,
                                    :file_name,
                                    :file_size,
                                    :file_type,
                                    :status
                                )");
                                $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
                                $stmt->bindValue(':internal_file_name', $internal_file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
                                $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
                                $stmt->bindValue(':file_type', $file_type, PDO::PARAM_STR);
                                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                if(!$stmt->execute()){
                                    $db->rollBack();
                                    return array(
                                        "status" => "Error",
                                        "message" => "Error"
                                    );
                                }
                }
            }

            $stmt = $db->prepare("SELECT `no_of_reviews`
                                FROM `submissions`
                                WHERE `id` = :submissionId
                                    AND `status` = :status ");

            $stmt->bindValue(':submissionId', $submissionId, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $no_of_reviews = ($results[0]['no_of_reviews'])+1;



            if($reviewAction === "Accept"){
                $stmt = $db->prepare("UPDATE `submissions`
                                        SET `submission_status` = :submission_status,
                                            `no_of_reviews` = :no_of_reviews
                                        where `id` = :submissionId
                                            AND `status` = :status
                                            AND `reference_id` = :reference_id
                                            AND `type` = :type
                                    ");
                                    $stmt->bindValue(':submission_status', 'ACCEPTED', PDO::PARAM_STR);
                                    $stmt->bindValue(':submissionId', $submissionId, PDO::PARAM_STR);
                                    $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
                                    $stmt->bindValue(':no_of_reviews', $no_of_reviews, PDO::PARAM_INT);
                                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                                    $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                        if(!$stmt->execute()){
                            $db->rollBack();
                            return array(
                                "status" => "Error",
                                "message" => "Error"
                            );
                        }

                $stmt = $db->prepare("SELECT `id`,`max_score`
                                    FROM `reward_point_criterias`
                                    WHERE `reference_id` = :reference_id
                                        AND `criteria` = :criteria");

                $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
                $stmt->bindValue(':criteria', 'ASSIGNMENT_REVIEW', PDO::PARAM_STR);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $review_max_score = $results[0]['max_score'];
                $criteria_id = $results[0]['id'];

                if($review_points < $review_max_score){
                    $stmt = $db->prepare("INSERT INTO `reward_points_scored`
                                        (`user_id`,`criteria_id`,`score`,`status`)
                                        VALUES(
                                            :user_id,
                                            :criteria_id,
                                            :score,
                                            :status
                                        )");
                                        $stmt->bindValue(':criteria_id', $criteria_id, PDO::PARAM_INT);
                                        $stmt->bindValue(':score', $review_points, PDO::PARAM_INT);
                                        $stmt->bindValue(':user_id', $learnersUserId, PDO::PARAM_STR);
                                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                        if(!$stmt->execute()){
                                            $db->rollBack();
                                            return array(
                                                "status" => "Error",
                                                "message" => "Error"
                                            );
                                        }
                }
                elseif($review_points >= $review_max_score){
                    $stmt = $db->prepare("INSERT INTO `reward_points_scored`
                                        (`user_id`,`criteria_id`,`score`,`status`)
                                        VALUES(
                                            :user_id,
                                            :criteria_id,
                                            :score,
                                            :status
                                        )");
                                        $stmt->bindValue(':criteria_id', $criteria_id, PDO::PARAM_INT);
                                        $stmt->bindValue(':score', $review_max_score, PDO::PARAM_INT);
                                        $stmt->bindValue(':user_id', $learnersUserId, PDO::PARAM_INT);
                                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                                        if(!$stmt->execute()){
                                            $db->rollBack();
                                            return array(
                                                "status" => "Error",
                                                "message" => "Error"
                                            );
                                        }
                }

            }


            elseif($reviewAction === "Resubmit"){
                $stmt = $db->prepare("UPDATE `submissions`
                                        SET `submission_status` = :submission_status,
                                            `no_of_reviews` = :no_of_reviews
                                        where `id` = :submissionId
                                            AND `status` = :status
                                            AND `reference_id` = :reference_id
                                            AND `type` = :type
                                    ");
                        $stmt->bindValue(':submission_status', 'RECOMMENDED_RESUBMISSION', PDO::PARAM_STR);
                        $stmt->bindValue(':submissionId', $submissionId, PDO::PARAM_STR);
                        $stmt->bindValue(':reference_id', $assignmentId, PDO::PARAM_INT);
                        $stmt->bindValue(':no_of_reviews', $no_of_reviews, PDO::PARAM_INT);
                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                        $stmt->bindValue(':type', 'ASSIGNMENT', PDO::PARAM_STR);
                        if(!$stmt->execute()){
                            $db->rollBack();
                            return array(
                                "status" => "Error",
                                "message" => "Error"
                            );
                        }

            }



            $db->commit();
            return array(
                "status" => "Success",
                "message_id" => $message_id
            );


    }

    public static function get_previous_message_id($thread_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `id`,`previous_message_id`
                                FROM `messages`
                            WHERE `thread_id` = :thread_id
                            ORDER BY `previous_message_id` DESC");

        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $previous_message_id = $results[0]['id'];
        return $previous_message_id;
    }

    public static function get_s3_details($user_id){
        return array(
            "s3Details4AssignmentUpload"=>s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1'),
            "attachment_file_prefix" => 'A-'.$user_id.date('y_m_d'),
        );
    }


}
