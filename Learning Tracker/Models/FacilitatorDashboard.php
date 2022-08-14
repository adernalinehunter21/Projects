<?php

namespace App\Models;

use PDO;
use App\s3;

/**
 * FacilitatorDashboard model
 *
 * PHP version 5.4
 */
class FacilitatorDashboard extends \Core\Model {

    /**
     * Get some values as an associative array
     * This query will take userId as argument 
     *this function will return course details
     */
    public static function getFacilitatorSubjects($userId) {
        try {

            $db = static::getDB();
            
            //this query will give course_name, logo, subject_id and course_id from courses table and subjects table
            $stmt = $db->prepare('SELECT `subjects`.`logo`, `subjects`.`subject`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                        WHERE  `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`role` = :role
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status
                            AND `subjects`.`status` = :status');
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role', "FACILITATOR", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            return array();
        }
    }
    
    /**
     * 
     * This function will take userId as a parameter
     * this will return learnedCourses
     */
    public static function getLearnedCourses($userId) {
        try {

            $db = static::getDB();
            
            //this query will give course_name, logo, subject_id and course_id from courses table and subjects table
            $stmt = $db->prepare('SELECT `subjects`.`logo`, `subjects`.`subject`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                        WHERE  `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`role` = :role
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status
                            AND `subjects`.`status` = :status');
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role', "PARTICIPANT", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            return array();
        }
    }
    
    /**
     * This function will take userId as an argument 
     * This function will return an array called overview courses
     */
    public static function get30DayOverviewCourses($userId) {
        try {

            $db = static::getDB();
            
            //this query will give all the courses of the facilitator user
            $stmt = $db->prepare('SELECT `user_to_course_mapping`.`course_id`,
                            `courses`.`course_name`, `courses`.`subject_id`,
                            `subjects`.`short_name`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                        WHERE  `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`role` = :role
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status
                            AND `subjects`.`status` = :status');
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role', "FACILITATOR", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $numberOfParicipants = 0;
            $programsClosedInLast30Days = array();
            foreach ($results as $oneProgram) {
               
                //This query will give schedule dates from course sessions
                $stmt = $db->prepare('SELECT `date` 
                            FROM `course_sessions` 
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :courseId
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `start_timestamp` DESC LIMIT 0, 1');
                $stmt->bindValue(':courseId', $oneProgram['course_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $endDate = array_pop($result1);
                $today = date('Y-m-d');
                $lastMonthThisDay = date('Y-m-d', strtotime("-30days"));
                if (($endDate >= $lastMonthThisDay) && ($endDate <= $today)) {
                    array_push($programsClosedInLast30Days, $oneProgram);
                }
                  
            }
            $subjects = array();
            $totalNumberOfPrograms = 0;
            $totalNumberOfParticipants = 0;
            foreach ($programsClosedInLast30Days as $oneProgram) {
                $totalNumberOfPrograms++;
                //this query will give how many participants are there for particular course id
                $stmt = $db->prepare('SELECT COUNT(`mapping_id`) AS numberOfParticipants 
                            FROM `user_to_course_mapping` 
                            WHERE `course_id` = :course_id 
                                AND `role` = :role
                                AND `status` = :status');
                $stmt->bindValue(':course_id', $oneProgram['course_id'], PDO::PARAM_INT);
                $stmt->bindValue(':role', "PARTICIPANT", PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result2 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $numberOfParicipants = $result2[0];
                $totalNumberOfParticipants += $numberOfParicipants;
                $subjectId = $oneProgram['subject_id'];
                if (isset($subjects[$subjectId])) {
                    $subjects[$subjectId] = array(
                        "subject" => $oneProgram['short_name'],
                        "programCount" => $subjects[$subjectId]['programCount'] + 1,
                        "participantCount" => $subjects[$subjectId]['participantCount'] + $numberOfParicipants
                    );
                } else {
                    $subjects[$subjectId] = array(
                        "subject" => $oneProgram['short_name'],
                        "programCount" => 1,
                        "participantCount" => $numberOfParicipants
                    );
                }
            }
             $courseOverViewOf30Days = array();
            array_push($courseOverViewOf30Days, array(
                "totalNumberOfParticipants" => $totalNumberOfParticipants,
                "totalNumberOfPrograms" => $totalNumberOfPrograms,
                "subjects" => $subjects
            ));
           
            return $courseOverViewOf30Days;
        } catch (PDOException $e) {
            return array();
        }
    }
    
    /**
     * this function will take userId as a parameter 
     * returns array of current and upcoming programs
     */
     public static function getCurrentAndUpcomingPrograms($userId) {
        try {

            $db = static::getDB();
            
            //this query will give all the current and upcoming programs 
            $stmt = $db->prepare('SELECT `user_to_course_mapping`.`course_id`,
                            `subjects`.`subject`, `subjects`.`logo`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                        WHERE  `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`role` = :role
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status
                            AND `subjects`.`status` = :status');
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':role', "FACILITATOR", PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $currentPrograms = array();
            foreach ($results as $value) {
                 //This query will give start schedule timestamp from course sessions
                $stmt = $db->prepare('SELECT `start_timestamp` FROM `course_sessions` 
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :courseId
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `start_timestamp` ASC LIMIT 1');
                $stmt->bindValue(':courseId', $value['course_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                if($stmt->rowCount() === 0){
                    continue;//Course is not having sessions so skip
                }
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $timeStamp = array_pop($result1);
                $scheduleStartTimeStamp = $timeStamp['start_timestamp'];
                $startTimeStamp = date('Y-m-d h:i:s',  strtotime($scheduleStartTimeStamp));
                
                //This query will give end schedule timestamp from course sessions
                $stmt = $db->prepare('SELECT `start_timestamp` FROM `course_sessions` 
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :courseId
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `start_timestamp` DESC LIMIT 1');
                $stmt->bindValue(':courseId', $value['course_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $timeStamp = array_pop($result2);
                $scheduleEndTimeStamp = $timeStamp['start_timestamp'];
                $endTimeStamp = date('Y-m-d h:i:s',  strtotime($scheduleEndTimeStamp));
                $currentTimeStamp = date('Y-m-d h:i:s', strtotime("now"));
                if ($endTimeStamp >= $currentTimeStamp) {
                    array_push($currentPrograms, array(
                        "subject" => $value['subject'],
                        "logo" => $value['logo'],
                        "startDate" => date('d M',  strtotime($scheduleStartTimeStamp)),
                        "endDate" => date('d M',  strtotime($scheduleEndTimeStamp))
                        
                    ));
                }
                
            }
            return $currentPrograms;
        } catch (PDOException $e) {
            return array();
        }
    }
}
