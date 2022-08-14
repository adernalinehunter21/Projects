<?php

namespace App\Models;

use PDO;
use App\s3;
use App\Models\Reflections;
use App\Models\Assignments;
use App\Models\Quizzes;
use App\Models\Notes;
use App\Models\Topics;

/**
 * sessions model
 *
 * PHP version 5.4
 */
class Sessions extends \Core\Model {

    /**
     *
     * @param type $course_id
     * @param type $user_id
     * @return type Associative array of data required to display table under Schedule Tab in Participant login
     */
    public static function getSessionDetails($course_id, $user_id, $subject_id = null, $subject_version = null) {
        if ($course_id <= 3) {
            return self::getSessionDetailsOld($course_id, $user_id);
        } else {
//            return self::getSessionDetailsOld($course_id, $user_id);
            return self::getHighlevelDetailsOfSessions($course_id, $user_id, $subject_id, $subject_version);
        }
    }

    /**
     *
     * @param type $course_id
     * @param type $user_id
     * @return type Associative array of data required to display table under Schedule Tab in Participant login
     */
    private static function getHighlevelDetailsOfSessions($course_id, $user_id, $subject_id, $subject_version) {
        $sessions = array();
        try {
            // this query will give session_name and session_id
            $db = static::getDB();

            $stmt = $db->prepare('SELECT `session_id`, `session_index`, `session_name`
                                    FROM `course_sessions`
                                    WHERE course_id = :course_id
                                        AND `status` = :status
                                    ORDER BY `session_index`,`session_id` ASC');
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (isset($_SESSION['user_timezone_configured'])) {
                $userTimezone = $_SESSION['user_timezone_configured'];
            } else {
                $userTimezone = "UTC";
            }

            foreach ($results as $session) {
//                $oneSessionDetails = $session;
                // this query will give date and start time and end time
                $stmt = $db->prepare('SELECT `meeting_link`, CONVERT_TZ(`start_timestamp`, :tz_utc, :user_tz) as start_timestamp, `duration`
                                    FROM `course_session_schedules`
                                    WHERE session_id = :session_id
                                        AND `status` = :status');
                $stmt->bindValue(':session_id', $session['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':tz_utc', "UTC", PDO::PARAM_STR);
                $stmt->bindValue(':user_tz', $userTimezone, PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $schedule = array_pop($result1);
//                $oneSessionDetails['schedule'] = $schedule;
                //this query will give sub session details
                $stmt = $db->prepare("SELECT `course_session_to_topic_mapping`.`id` as mapping_id,
                                        `module_id`, `module_index`, `course_session_to_topic_mapping`.topic_id,
                                        `topic_type`, `general_topic`, `order`, topic_name, `module_name`
                                    FROM `course_session_to_topic_mapping`
                                        LEFT JOIN (SELECT `subject_topics`.`id` AS topic_id, `name` AS topic_name,
                                                `subject_topics`.`module_id`, `subject_id`, `module_index`, `module_name`
                                            FROM `subject_topics`
                                            JOIN `subject_modules` ON(`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                            WHERE `subject_topics`.`status` = :status
                                            AND `subject_modules`.`status` = :status
                                            AND `subject_id` = :subject_id) AS module_topics ON(`course_session_to_topic_mapping`.`topic_id` = module_topics.`topic_id`)
                                    WHERE `course_session_to_topic_mapping`.`status` = :status
                                        AND `session_id` = :session_id
                                    ORDER BY `order` ASC");
                $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
                $stmt->bindValue(':session_id', $session['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

//                $oneSessionDetails['topics'] = $topics;

                $session_resources_and_recordings = array();
                //this query will give video details
                $stmt = $db->prepare('SELECT `id`, `thumbnail_link`, `video_link`, `transcript_link`, `link_type`, `name`
                                            FROM `course_session_videos`
                                            WHERE session_id = :session_id
                                                AND `status` = :status
                                                ORDER BY `id` ASC');
                $stmt->bindValue(':session_id', $session['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $videoQueryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $videos = array();
                foreach ($videoQueryResult as $oneVideoDetails) {
                    if ($oneVideoDetails['link_type'] === "OLD") {
                        array_push($videos, array(
                            "id" => $oneVideoDetails['id'],
                            "thumbnail_link" => $oneVideoDetails['thumbnail_link'],
                            "video_link" => $oneVideoDetails['video_link'],
                            "transcript_link" => $oneVideoDetails['transcript_link']
                        ));
                    } else {

                        $video_thumbnail = "";
                        if ($oneVideoDetails['thumbnail_link'] !== "") {
                            $video_thumbnail = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $oneVideoDetails['thumbnail_link']);
                        }

                        $video_link = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $oneVideoDetails['video_link']);

                        $video_transcript = "";
                        if (isset($oneVideoDetails['transcript_link']) && $oneVideoDetails['transcript_link'] != "") {
                            $video_transcript = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $oneVideoDetails['transcript_link']);
                        }

                        $oneVideo = array(
                            "id" => $oneVideoDetails['id'],
                            "thumbnail_link" => $video_thumbnail,
                            "video_link" => $video_link,
                            "transcript_link" => $video_transcript,
                            "name" => $oneVideoDetails['name']
                        );
                        array_push($videos, $oneVideo);
                    }
                }
                $session_resources_and_recordings['video_parts'] = $videos;


                $oneImageDetails = null;
                $countOfImages = 0;
                $refinedTopics = array();
                foreach ($topics as $subjectTopicDetails) {
                    if ($subjectTopicDetails['topic_type'] === "SUBJECT_TOPIC") {
                        //There is a topic associated to this session lets get an image of the presentation loaded
                        $subjectTopicId = $subjectTopicDetails['topic_id'];
                        $subjectTopicName = $subjectTopicDetails['topic_name'];
                        $subjectModuleIndex = $subjectTopicDetails['module_index'];
                        $stmt = $db->prepare("SELECT `subject_topic_presentation_images`.`id`,
                                            `order`,
                                            `image_link`,
                                            `note`
                                        FROM `subject_topic_presentation_images`
                                            LEFT JOIN `learner_slide_notes` ON (`subject_topic_presentation_images`.`id` = `learner_slide_notes`.`slide_id`)
                                        WHERE `topic_id` = :topic_id
                                            AND `subject_topic_presentation_images`.`status` = :status
                                        ORDER BY `order` ASC ");
                        $stmt->bindValue(':topic_id', $subjectTopicId, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                        $stmt->execute();
                        $result62 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result62) > 0) {
                            $countOfImages += count($result62);
                            if ($oneImageDetails == null) {//This would be true only once. So, we will load details of single image
                                $image = $result62[0];
                                $fileName = $image['image_link'];
                                $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'], "Module$subjectModuleIndex/v$subject_version/$subjectTopicName/$fileName");

                                $oneImageDetails = array(
                                    "slide_id" => $image['id'],
                                    "topic_id" => $subjectTopicId,
                                    "topic_name" => $subjectTopicName,
                                    "order" => $image['order'],
                                    "filePath" => $filePath,
                                    "note" => $image['note']
                                );
                            }
                        }
                        array_push($refinedTopics, array(
                            "session_to_topic_mapping_id" => $subjectTopicDetails['mapping_id'],
                            "topic_type" => $subjectTopicDetails['topic_type'],
                            "subject_module_id" => $subjectTopicDetails['module_id'],
                            "subject_module_index" => $subjectTopicDetails['module_index'],
                            "subject_module_name" => $subjectTopicDetails['module_name'],
                            "subject_topic_id" => $subjectTopicDetails['topic_id'],
                            "order" => $subjectTopicDetails['order'],
                            "topic_name" => $subjectTopicDetails['topic_name']
                        ));
                    } else {
                        array_push($refinedTopics, array(
                            "session_to_topic_mapping_id" => $subjectTopicDetails['mapping_id'],
                            "topic_type" => $subjectTopicDetails['topic_type'],
                            "subject_module_id" => $subjectTopicDetails['module_id'],
                            "subject_module_index" => $subjectTopicDetails['module_index'],
                            "subject_module_name" => $subjectTopicDetails['module_name'],
                            "subject_topic_id" => $subjectTopicDetails['topic_id'],
                            "order" => $subjectTopicDetails['order'],
                            "topic_name" => $subjectTopicDetails['general_topic']
                        ));
                    }
                }
                $oneImageDetails['countOfSlides'] = $countOfImages;
                $session_resources_and_recordings['presentationImage'] = $oneImageDetails;

                $session_notes = Notes::getSessionNotes($session['session_index'], $course_id);

                $session_resources_and_recordings['notes'] = $session_notes;

                $stmt = $db->prepare("SELECT `course_session_assignments`.`id`,
                                            `name`,
                                            `course_session_assignments`.`description` AS description,
                                            submissions.`id` AS submission_id,
                                            'COURSE_SESSION' AS assignment_type,
                                            'COURSE_SESSION_ASSIGNMENT' AS assignmentType
                                        FROM `course_session_assignments`
                                            LEFT JOIN (SELECT * FROM `assignment_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions ON (`course_session_assignments`.`id` = submissions.`assignment_id`)
                                        WHERE `session_id` = :session_id
                                            AND `course_session_assignments`.`status` = :status
                                        ORDER BY `course_session_assignments`.`id` ASC");

                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':session_id', $session['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                $stmt->execute();
                $result8 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $assignments = array();
                foreach ($result8 as $oneAssignment) {
                    $stmt = $db->prepare('SELECT `document_name`, `document_link` FROM `course_session_assignment_reference` WHERE `assignment_id` = :assignment_id AND `status` = :status ORDER BY `id` ASC');
                    $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $oneAssignment['reference_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    //If assignment is already submitted, get the details of the submitted documents

                    if (isset($oneAssignment['submission_id'])) {
                        $oneAssignment['submission_status'] = "submitted";
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
                        $oneAssignment['uploaded_documents'] = $fileDetails;

                        $stmt = $db->prepare("SELECT  `user_id`, CONCAT(`name`, ' ', `last_name`) AS name
                                            FROM `assignment_submission_team`
                                                JOIN `users` ON (`assignment_submission_team`.`user_id` = `users`.`id`)
                                            WHERE `submission_id` = :submission_id
                                                AND `assignment_submission_team`.`status` = :status ");
                        $stmt->bindValue(':submission_id', $submissionId, PDO::PARAM_INT);
                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($result) > 0) {
                            $team = array();
                            foreach ($result as $teamMember) {
                                array_push(
                                        $team,
                                        array(
                                            "name" => $teamMember['name']
                                        )
                                );
                            }
                            $oneAssignment['submission_type'] = "TEAM";
                            $oneAssignment['other_team_members'] = $team;
                        } else {
                            $oneAssignment['submission_type'] = "INDIVIDUAL";
                        }
                    } else {
                        $stmt = $db->prepare("SELECT `assignment_submissions`.`id`
                                                FROM `assignment_submissions`
                                                JOIN `assignment_submission_team` ON (`assignment_submissions`.`id` = `assignment_submission_team`.`submission_id`)
                                                WHERE `assignment_submissions`.`status` = :status
                                                    AND `assignment_submission_team`.`status` = :status
                                                    AND `assignment_id` = :assignment_id
                                                    AND `assignment_submission_team`.`user_id` = :user_id");
                        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        if (count($result) > 0) {
                            $submissionId = array_pop($result);
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
                            $oneAssignment['submission_id'] = $submissionId;
                            $oneAssignment['uploaded_documents'] = $fileDetails;
                        }
                    }
                    $oneAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);

                    array_push($assignments, $oneAssignment);
                }
                $subject_assignments = Assignments::getSessionAssignmentsOfSubject($course_id, $session['session_id'], $user_id);

                $session_resources_and_recordings['assignments'] = array_merge($assignments, $subject_assignments);
                $quizGroups = Quizzes::getSessionQuiz($session['session_id'], $user_id, $subject_id);
                $session_resources_and_recordings['quiz_question_groups'] = $quizGroups;

                $reflectionDetails = Reflections::getSessionReflectionsOfTheUser($user_id, $subject_id, $session['session_id']);
                if ($reflectionDetails['status'] === "Success") {
                    $session_resources_and_recordings['reflectionDetails'] = $reflectionDetails['data'];
                } else {
                    $session_resources_and_recordings['reflectionDetails'] = array();
                }


                $scheduleStartDate = date('d M y', strtotime($schedule['start_timestamp']));
                $scheduleStartTime = date('H:i', strtotime($schedule['start_timestamp']));
                $scheduleEndTime = date('H:i', strtotime($scheduleStartTime) - strtotime("00:00:00") + strtotime($schedule['duration']));
                array_push($sessions, array(
                    "session_name" => $session['session_name'],
                    "session_id" => $session['session_id'],
                    "session_index" => $session['session_index'],
                    "session_schedule_date" => $scheduleStartDate,
                    "session_schedule_start_time" => $scheduleStartTime,
                    "session_schedule_end_time" => $scheduleEndTime,
                    "session_schedule_meeting_link" => $schedule['meeting_link'],
                    "session_recordings" => $session_resources_and_recordings,
                    "topics" => $refinedTopics
//                    "presentationImages" => $presentationImages
                ));
            }

            return $sessions;
        } catch (Throwable $e) {

            echo $e->getMessage();
        }
    }

//https://regal-icf-acc-coach.s3.ap-southeast-1.amazonaws.com/Module1/v1/Program%20Introduction   /slide1.jpeg ?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&amp;X-Amz-Algorithm=AWS4-HMAC-SHA256&amp;X-Amz-Credential=AKIAJDARPR77H4M5UZLA%2F20200905%2Fap-southeast-1%2Fs3%2Faws4_request&amp;X-Amz-Date=20200905T152124Z&amp;X-Amz-SignedHeaders=host&amp;X-Amz-Expires=1200&amp;X-Amz-Signature=9b828f6450eb2d4ecd9e7dbdb89cf395f774ccce93515d3ca796905cbf08986b
//https://regal-icf-acc-coach.s3.ap-southeast-1.amazonaws.com/Module1/v1/On%20Becoming%20a%20Coach/slide047.jpg?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&amp;X-Amz-Algorithm=AWS4-HMAC-SHA256&amp;X-Amz-Credential=AKIAJDARPR77H4M5UZLA%2F20200905%2Fap-southeast-1%2Fs3%2Faws4_request&amp;X-Amz-Date=20200905T162909Z&amp;X-Amz-SignedHeaders=host&amp;X-Amz-Expires=1200&amp;X-Amz-Signature=d81256913b1b7e1d6da73451996a9fae22223e053a479e76a0367aacd5024cc9

    /**
     * Get the list of sessions of the given course
     * @param type $course_id
     * @return array({session_id, session_index, session_name})
     */
    public static function getSessionsOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT  `course_sessions`.`session_id`,
                                `session_index`, `session_name`
                            FROM `course_sessions`
                            WHERE `course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                            ORDER BY `session_index` ASC ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $sessions;
    }

    /**
     * Get all the details of each session of the course
     * Required to load Schedule page in Facilitator login
     * @param type $course_id
     * @return array
     */
    public static function getSessionsWithDetails($course_id) {

        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }

        $db = static::getDB();

        $stmt = $db->prepare("SELECT  `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                CONVERT_TZ(`start_timestamp`, :tz_utc, :user_tz) as start_timestamp,
                                `duration`,
                                `meeting_link`
                            FROM `course_sessions`
                            JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :course_id
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `session_index` ASC ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':tz_utc', "UTC", PDO::PARAM_STR);
        $stmt->bindValue(':user_tz', $userTimezone, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sessions = array();
        foreach ($result as $one_session) {
            $session_id = $one_session['session_id'];
            $session_topics = Topics::getTopicsOfTheSession($session_id);

            array_push($sessions, array(
                "session_id" => $one_session['session_id'],
                "session_index" => $one_session['session_index'],
                "session_name" => $one_session['session_name'],
                "date" => date('d M y', strtotime($one_session['start_timestamp'])),
                "time" => date('H:i', strtotime($one_session['start_timestamp'])),
                "duration" => date('H:i', strtotime($one_session['duration'])),
                "meeting_link" => $one_session['meeting_link'],
                "topics" => $session_topics
            ));
        }

        return $sessions;
    }

    /**
     * Delete/Disable the specific session by its Session ID
     * @param type $course_id
     * @param type $session_index
     * @return type
     */
    public static function deleteSessionByIndex($course_id, $session_index) {
        $db = static::getDB();

        $stmt = $db->prepare("UPDATE `course_sessions`
                                SET `status` = 'INACTIVE'
                            WHERE `course_id` = :course_id
                                AND `session_index` = :session_index ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_index', $session_index, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $result = array(
                "status" => "Error",
                "error" => "Encountered an error while diabling the session. Please try again"
            );
        }

        return array(
            "status" => "Success"
        );
    }

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    private static function getSessionDetailsOld($course_id, $user_id) {
        $sessions = array();

        try {
            // this query will give session_name and session_id
            $db = static::getDB();

            $stmt = $db->prepare('SELECT `session_id`, `session_index`, `session_name` FROM `course_sessions` WHERE course_id = :course_id ORDER BY `session_index`,`session_id` ASC');
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $value) {
                // this query will give date and start time and end time
                $stmt = $db->prepare('SELECT `date`,`start_time`,`end_time`, `meeting_link` FROM `course_session_schedules` WHERE session_id = :session_id ');
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);

                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $sub_sessions = array();

                $value1 = array_pop($result1);

                //this query will give sub session details
                $stmt = $db->prepare('SELECT `sub_session_name`, `sub_session_id`,`type`,`module_id` FROM `course_sub_sessions` WHERE session_id = :session_id ORDER BY `session_id` ASC');
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);

                $stmt->execute();
                $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);


                $sub_session_details = array();
                foreach ($result2 as $value2) {
                    if ($value2['module_id'] != 0) {
                        $stmt = $db->prepare('SELECT `module_index`, `module_name` FROM `course_modules` WHERE module_id = :module_id  ORDER BY `module_id` ASC');
                        $stmt->bindValue(':module_id', $value2['module_id'], PDO::PARAM_INT);

                        $stmt->execute();
                        $result11 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $module_details = array_pop($result11);

                        $module_index = $module_details['module_index'];
                        $module_name = $module_details['module_name'];
                    } else {
                        $module_index = "";
                        $module_name = "";
                    }

                    //this query will gives sub_session_topics
                    $sub_session_details['sub_session_name'] = $value2['sub_session_name'];
                    $sub_session_details['sub_session_type'] = $value2['type'];
                    $sub_session_details['module_index'] = $module_index;
                    $sub_session_details['module_name'] = $module_name;

                    //this query will give topics
                    $stmt = $db->prepare('SELECT `topic` FROM `course_sub_session_topics` WHERE sub_session_id = :sub_session_id ORDER BY `sub_session_id` ASC');
                    $stmt->bindValue(':sub_session_id', $value2['sub_session_id'], PDO::PARAM_INT);

                    $stmt->execute();
                    $result3 = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $sub_session_details['topics'] = $result3;
                    //this query will give objectives
                    $stmt = $db->prepare('SELECT `objective` FROM `course_sub_session_objectives` WHERE sub_session_id = :sub_session_id ORDER BY `sub_session_id` ASC');
                    $stmt->bindValue(':sub_session_id', $value2['sub_session_id'], PDO::PARAM_INT);

                    $stmt->execute();
                    $result4 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $sub_session_details['objectives'] = $result4;
                    //this query will give learning methods
                    $stmt = $db->prepare('SELECT `learning_method` FROM `course_sub_session_learning_methods` WHERE sub_session_id= :sub_session_id  ORDER BY `sub_session_id` ASC');
                    $stmt->bindValue(':sub_session_id', $value2['sub_session_id'], PDO::PARAM_INT);

                    $stmt->execute();
                    $result5 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $sub_session_details['learning_methods'] = $result5;


                    array_push($sub_sessions, $sub_session_details);
                }

                $session_recordings = array();
                //this query will give video details
                $stmt = $db->prepare('SELECT `id`, `thumbnail_link`, `video_link`, `transcript_link` FROM `course_session_videos` WHERE session_id = :session_id AND `status` = :status ORDER BY `id` ASC');
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $result6 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $session_recordings['video_parts'] = $result6;

                //Experimental image presentations code starts here
//                echo "<br>**********Start*******************<br>";
                //Check if subject presentations are there
                $subjectId = isset($_SESSION['subject_id']) ? $_SESSION['subject_id'] : 0;
                $stmt = $db->prepare("SELECT `topic_id`, `subject_topics`.`module_id`, `module_index`,
                                        `name` AS topic_name, `objective`, `images_bucket`
                                    FROM `course_session_to_topic_mapping`
                                        JOIN `subject_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`)
                                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                                    WHERE `course_session_to_topic_mapping`.`session_id` = :session_id
                                        AND `subject_modules`.`subject_id` = :subject_id
                                        AND `course_session_to_topic_mapping`.`status` = :status
                                        AND `subject_topics`.`status` = :status
                                    ORDER BY `subject_topics`.`id` ASC ");
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                $stmt->execute();
                $result61 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $oneImageDetails = null;
                $countOfImages = 0;
                if (count($result61) > 0) {
                    foreach ($result61 as $subjectTopicDetails) {
                        //There is a topic associated to this session lets get an image of the presentation loaded
                        $subjectTopicId = $subjectTopicDetails['topic_id'];
                        $subjectTopicName = $subjectTopicDetails['topic_name'];
                        $subjectModuleIndex = $subjectTopicDetails['module_index'];
                        $subjectVersion = $_SESSION['subjectVersion'];
                        $stmt = $db->prepare("SELECT `order`, `image_link`
                                        FROM `subject_topic_presentation_images`
                                        WHERE `topic_id` = :topic_id
                                            AND `status` = :status
                                        ORDER BY `order` ASC ");
                        $stmt->bindValue(':topic_id', $subjectTopicId, PDO::PARAM_INT);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);

                        $stmt->execute();
                        $result62 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($result62) > 0) {
                            $countOfImages += count($result62);
                            if ($oneImageDetails == null) {//This would be true only once. So, we will load details of single image
                                $image = $result62[0];
                                $fileName = $image['image_link'];
                                $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-presentations', "Module$subjectModuleIndex/v$subjectVersion/$subjectTopicName/$fileName");

                                $oneImageDetails = array(
                                    "topic_id" => $subjectTopicId,
                                    "topic_name" => $subjectTopicName,
                                    "topic_objective" => $subjectTopicDetails['objective'],
                                    "order" => $image['order'],
                                    "filePath" => $filePath
                                );
                            }
                        }
                    }
                }
                $oneImageDetails['countOfSlides'] = $countOfImages;
                $session_recordings['presentationImage'] = $oneImageDetails;
                //Experimental image presentation code end here
//                echo "<br>**********End*******************<br>";
                //this query will give presentation details
                $stmt = $db->prepare('SELECT `presentation_link`, `thumbnail_link`, `name` FROM `course_session_presentations` WHERE session_id = :session_id ORDER BY `id` ASC');
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);

                $stmt->execute();
                $result7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $session_recordings['presentations'] = $result7;

                $stmt = $db->prepare("SELECT `course_session_assignments`.`id`,
                                        `session_id`,
                                        `name`,
                                        `course_session_assignments`.`description` AS description,
                                        submissions.`id` AS submission_id,
                                        submissions.`Description` AS submissionDescription
                                    FROM `course_session_assignments`
                                        LEFT JOIN (SELECT * FROM `assignment_submissions` WHERE `user_id` = :user_id AND `status` = :status) AS submissions ON (`course_session_assignments`.`id` = submissions.`assignment_id`)
                                    WHERE `session_id` = :session_id
                                        AND `course_session_assignments`.`status` = :status
                                    ORDER BY `course_session_assignments`.`id` ASC");

                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

                $stmt->execute();
                $result8 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $assignments = array();
                foreach ($result8 as $oneAssignment) {
                    $stmt = $db->prepare('SELECT `document_name`, `document_link` FROM `course_session_assignment_reference` WHERE `assignment_id` = :assignment_id AND `status` = :status ORDER BY `id` ASC');
                    $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                    $stmt->execute();
                    $oneAssignment['reference_documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    //If assignment is already submitted, get the details of the submitted documents

                    if (isset($oneAssignment['submission_id'])) {
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
                            $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileName);
                            array_push($fileDetails, array(
                                "name" => $fileName,
                                "url" => $filePath
                            ));
                        }
                        $oneAssignment['uploaded_documents'] = $fileDetails;
                    } else {
                        $stmt = $db->prepare("SELECT `assignment_submissions`.`id`
                                                FROM `assignment_submissions`
                                                JOIN `assignment_submission_team` ON (`assignment_submissions`.`id` = `assignment_submission_team`.`submission_id`)
                                                WHERE `assignment_submissions`.`status` = :status
                                                    AND `assignment_submission_team`.`status` = :status
                                                    AND `assignment_id` = :assignment_id
                                                    AND `assignment_submission_team`.`user_id` = :user_id");
                        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(':assignment_id', $oneAssignment['id'], PDO::PARAM_INT);
                        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        if (count($result) > 0) {
                            $submissionId = array_pop($result);
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
                                $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileName);
                                array_push($fileDetails, array(
                                    "name" => $fileName,
                                    "url" => $filePath
                                ));
                            }
                            $oneAssignment['submission_id'] = $submissionId;
                            $oneAssignment['uploaded_documents'] = $fileDetails;
                        }
                    }
                    $oneAssignment['internalFileName'] = "AS" . $oneAssignment['id'] . random_int(11111, 99999);

                    array_push($assignments, $oneAssignment);
                }
                $session_recordings['assignments'] = $assignments;

                //this query will give quiz_question_group details and answer_id from quiz_group_answers table to know whether quiz is answered by the user or not
                $stmt = $db->prepare('SELECT `quiz_question_group`.`id`, `quiz_group_name`, answers.`id` AS answer_id
                                    FROM `quiz_question_group`
                                    LEFT JOIN (SELECT `id`, `quiz_group_id` FROM `quiz_group_answers` WHERE `user_id` = :user_id AND `status` = :status) AS answers ON (`quiz_question_group`.`id` = answers.`quiz_group_id`)
                                    WHERE `session_id` = :session_id
                                    AND `quiz_question_group`.`status` = :status
                                    ORDER BY `quiz_question_group`.`id` ASC');
                $stmt->bindValue(':session_id', $value['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result9 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $oneQuizGroup = array();
                foreach ($result9 as $value4) {
                    if ($value4['answer_id'] === null) {
                        $value4['answer_status'] = "UNANSWERED";
                    } else {
                        $value4['answer_status'] = "ANSWERED";
                    }
                    array_push($oneQuizGroup, $value4);
                }
                $session_recordings['quiz_question_groups'] = $oneQuizGroup;
                $reflectionDetails = Reflections::getReflectionsOfSessions($value['session_id']);
                $session_recordings['reflectionDetails'] = $reflectionDetails;
                array_push($sessions, array(
                    "session_name" => $value['session_name'],
                    "session_id" => $value['session_id'],
                    "session_index" => $value['session_index'],
                    "session_schedule_date" => date('d M y', strtotime($value1['date'])),
                    "session_schedule_start_time" => date('H:i', strtotime($value1['start_time'])),
                    "session_schedule_end_time" => date('H:i', strtotime($value1['end_time'])),
                    "session_schedule_meeting_link" => $value1['meeting_link'],
                    "sub_sessions" => $sub_sessions,
                    "session_recordings" => $session_recordings
//                    "presentationImages" => $presentationImages
                ));
            }

            return $sessions;
        } catch (PDOException $e) {

            echo $e->getMessage();
        }
    }

    public static function getSessionDetailsForProgressChart($course_id, $userTimezone) {

        $result = Sessions::getHighlevelSchedule($course_id, $userTimezone);
        if ($result['status'] == "Error") {
            return array(
                "sessionsJson" => json_encode(array()),
                "noOfSessionsCompleted" => 0,
                "toolTipsJson" => json_encode(array())
            );
        }
        $schedulesOfSessions = $result['data'];
        $now = date('Y-m-d H:i:s');
        $numberOfSessionsCompleted = 0;
        $sessionArray = array();
        $tooltipArray = array();
        foreach ($schedulesOfSessions as $session) {
            if (strtotime($now) > strtotime($session['start_timestamp'])) {
                $numberOfSessionsCompleted++;
            }
            array_push($sessionArray, $session['session_short_name']);
            array_push($tooltipArray, date('d M', strtotime($session['start_timestamp'])) . ", " . date('H:i', strtotime($session['start_timestamp'])) . " for " . date('H:i', strtotime($session['duration'])));
        }

        return array(
            "sessionsJson" => json_encode($sessionArray),
            "noOfSessionsCompleted" => $numberOfSessionsCompleted,
            "toolTipsJson" => json_encode($tooltipArray)
        );
    }

    public static function getSessionSchedules($course_id) {
        try {
            // this query will give schedule dates details
            $db = static::getDB();

            $stmt = $db->prepare("SELECT
                                    `course_sessions`.`session_id`,
                                    `session_index`,
                                    `session_short_name`,
                                    `date`,
                                    `start_time`,
                                    `end_time`,
                                    `start_timestamp`,
                                    `duration`
                                FROM `course_sessions`
                                    JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                                WHERE `course_id` = :course_id
                                    AND `course_sessions`.`status` = 'ACTIVE'
                                    AND `course_session_schedules`.`status` = 'ACTIVE'
                                ORDER BY `course_session_schedules`.`date`");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array(
                "status" => "Success",
                "data" => $results
            );
        } catch (PDOException $e) {
            return array(
                "status" => "Error",
                "data" => $e->getMessage()
            );
        }
    }

    /*     * *
     * Get highlevel schedule details of a course for sending calendar invites
     */

    public static function getHighlevelDetailsOfFutureSessions($course_id, $userTimezone) {
        try {
            // this query will give schedule dates details
            $results = Sessions::getHighlevelSchedule($course_id, $userTimezone);
            if ($results['status'] !== "Success") {
                return $results;
            }

            $currentTime = date('Y-m-d H:i:s');
            $futureScheduleSet = array();
            foreach ($results['data'] as $oneSchedule) {
                if (strtotime($oneSchedule['start_timestamp']) > strtotime($currentTime)) {
                    array_push($futureScheduleSet, $oneSchedule);
                }
            }
            return array(
                "status" => "Success",
                "data" => $futureScheduleSet
            );
        } catch (PDOException $e) {
            return array(
                "status" => "Error",
                "data" => $e->getMessage()
            );
        }
    }

    /**
     * Get the high level schedule details for generating pdf
     * @param type $course_id
     * @param type $userTimezone
     * @return type array('status' => 'Success/Error',
     *                  'course_start_date' => Start date of the course
     *                  'course_end_date' => End date of the course
     *                  'schedules' => One array of schedules. each having schedule index, name, start date, start time & end time )
     */
    public static function getHighlevelScheduleForPdf($course_id, $userTimezone) {
        $results = Sessions::getHighlevelSchedule($course_id, $userTimezone);
        if ($results['status'] !== "Success") {
            return $results;
        }
        $scheduleDataForPdf = array();
        $courseStartDate = '';
        $courseEndDate = '';
        foreach ($results['data'] as $oneSchedule) {

            $startDate = date('d M y', strtotime($oneSchedule['start_timestamp']));
            $startTime = date('H:i', strtotime($oneSchedule['start_timestamp']));
            $endTime = date('H:i', strtotime($oneSchedule['start_timestamp']) + strtotime($oneSchedule['duration']) - strtotime("00:00:00"));

            array_push($scheduleDataForPdf, array(
                "session_index" => $oneSchedule['session_index'],
                "session_name" => $oneSchedule['session_name'],
                "start_date" => $startDate,
                "start_time" => $startTime,
                "end_time" => $endTime
            ));

            if ($courseStartDate === '') {
                $courseStartDate = $startDate;
            }
            $courseEndDate = $startDate;
        }
        return array(
            "status" => "Success",
            "courseStartDate" => $courseStartDate,
            "courseEndDate" => $courseEndDate,
            "schedules" => $scheduleDataForPdf
        );
    }

    /**
     * Get the highlevel schedule of the schedule required for showing in UI or adding to calender or to generate pdf
     * @param type $course_id
     * @param type $userTimezone
     * @return type
     */
    public static function getHighlevelSchedule($course_id, $userTimezone) {
        try {
            // this query will give schedule dates details
            $db = static::getDB();
            $stmt = $db->prepare("SELECT
                                    `course_sessions`.`session_id`,
                                    `session_index`,
                                    `session_short_name`,
                                    `session_name`,
                                    CONVERT_TZ(`start_timestamp`, 'UTC', :user_timezone) as start_timestamp,
                                    `duration`,
                                    `meeting_link`
                                FROM `course_sessions`
                                    JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                                WHERE `course_id` = :course_id
                                    AND `course_sessions`.`status` = 'ACTIVE'
                                    AND `course_session_schedules`.`status` = 'ACTIVE'
                                ORDER BY `course_session_schedules`.`start_timestamp` ");
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_timezone', $userTimezone, PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array(
                "status" => "Success",
                "data" => $results
            );
        } catch (PDOException $e) {
            return array(
                "status" => "Error",
                "data" => $e->getMessage()
            );
        }
    }

    /**
     * Create a new Session for the given course with all the details given under data
     * This is meant to serve the ajax request to create new session under Facilitator
     * @param type $data
     * @return type
     */
    public static function createNewSession($data) {

        $course_id = $data['course_id'];

        $vidation = static ::validateCourseToTopicMapping($course_id, $data['topics']);

        if ($vidation['status'] === "Error") {
            return $vidation;
        }
        $topics = $vidation['topics'];

        $db = static::getDB();
        //Start the transaction to make sure all queries ahead are executed successfully before the commit
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO `course_sessions`
                                (`session_id`, `course_id`, `session_index`, `session_name`, `session_short_name`, `status`)
                                VALUES(
                                    null,
                                    :course_id,
                                    :session_index,
                                    :session_name,
                                    :session_short_name,
                                    :status
                                ) ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_index', $data['session_index'], PDO::PARAM_INT);
        $stmt->bindValue(':session_name', $data['session_name'], PDO::PARAM_STR);
        $stmt->bindValue(':session_short_name', "", PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while adding new session"
            );
        }

        $session_id = $db->lastInsertId();

        $session_date_time_user = $data['session_start_date_time'];

        $session_start_time_utc = gmdate('Y-m-d H:i:s', strtotime($session_date_time_user));

        $session_date_utc = date('Y-m-d', strtotime($session_start_time_utc));
        $meeting_link = "";
        if ($data['remote_meeting_availability'] === "Yes") {
            $meeting_link = trim($data['remote_meeting_link']);
        }
        $duration = $data['session_duration'];

        $stmt = $db->prepare("INSERT INTO `course_session_schedules`
                            (`schedule_id`, `session_id`, `date`, `start_time`, `end_time`, `start_timestamp`, `temp_timestamp`, `duration`, `meeting_link`, `status`)
                            VALUES(
                                null,
                                :session_id,
                                :date,
                                '00:00:00',
                                '00:00:00',
                                :start_timestamp,
                                '0000-00-00 00:00:00',
                                :duration,
                                :meeting_link,
                                :status
                            ) ");
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $session_date_utc, PDO::PARAM_STR);
        $stmt->bindValue(':start_timestamp', $session_start_time_utc, PDO::PARAM_STR);
        $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
        $stmt->bindValue(':meeting_link', $meeting_link, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while adding new schedule"
            );
        }

        $insert_query_value_array = array();
        foreach ($topics as $order => $topic) {

            if ($topic['type'] === "subject_topic") {
                $topic_id = $topic['id'];
                array_push($insert_query_value_array, array(NULL, $session_id, 'SUBJECT_TOPIC', $topic_id, '', $order, 'ACTIVE'));
            } elseif ($topic['type'] === "general") {
                $general_topic = $topic['name'];
                array_push($insert_query_value_array, array(NULL, $session_id, 'GENERAL_TOPIC', 0, $general_topic, $order, 'ACTIVE'));
            }
        }

        $stmt = $db->prepare("INSERT INTO `course_session_to_topic_mapping`
                        (`id`, `session_id`, `topic_type`, `topic_id`, `general_topic`, `order`, `status`)
                        VALUES (?,?,?,?,?,?,?) ");

        try {
            foreach ($insert_query_value_array as $row) {
                $result = $stmt->execute($row);
            }
        } catch (Exception $e) {
            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while mapping topics to session"
            );
        }
        $db->commit();

        return array(
            "status" => "Success",
        );
    }

    /**
     * Validate all the data parameters for creating new Session
     * It will either return an error indicating which specific param has a problem or Success with validated data
     * @param type $course_id
     * @param type $topic_ids
     * @return type
     */
    private static function validateCourseToTopicMapping($course_id, $topics) {

        $topic_ids = [];
        foreach ($topics as $topic) {
            if ($topic['type'] === "subject_topic") {
                array_push($topic_ids, $topic['id']);
            }
        }
        if (count($topic_ids) > 0) {
            $topic_ids_in_query = implode(',', $topic_ids);

            $db = static::getDB();

            $stmt = $db->prepare("SELECT  `subject_topics`.`id` AS topic_id
                                FROM `courses`
                                    JOIN `subject_modules` ON (`courses`.`subject_id` = `subject_modules`.`subject_id`)
                                    JOIN `subject_topics` ON (`subject_modules`.`module_id` = `subject_topics`.`module_id`)
                                WHERE `course_id` = :course_id
                                    AND `subject_topics`.`id` IN ($topic_ids_in_query)
                                    AND `courses`.`status` = :status
                                    AND `subject_modules`.`status` = :status
                                    AND `subject_topics`.`status` = :status ");

            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error while validating given topics with the couse. Please try again and report if issue repeats"
                );
            }

            $valid_subject_topics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $valid_subject_topics = [];
        }


        $validated_topics = [];
        foreach ($topics as $topic) {
            if (in_array($topic['id'], $valid_subject_topics) || $topic['type'] === "general") {
                array_push($validated_topics, $topic);
            }
        }

        return array(
            "status" => "Success",
            "topics" => $validated_topics
        );
    }

    /**
     * Get the details of one session
     * @param type $course_id
     * @param type $session_index
     */
    public static function getDetailsOfTheSession($course_id, $session_index) {

        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }

        $db = static::getDB();

        $stmt = $db->prepare("SELECT `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                `schedule_id`,
                                CONVERT_TZ(`start_timestamp`, :tz_utc, :user_tz) as start_timestamp,
                                DATE_FORMAT(`duration`, '%H:%i') AS duration,
                                `meeting_link`
                            FROM `course_sessions`
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                            WHERE `course_id` = :course_id
                                AND `session_index` = :session_index
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_index', $session_index, PDO::PARAM_INT);
        $stmt->bindValue(':tz_utc', "UTC", PDO::PARAM_STR);
        $stmt->bindValue(':user_tz', $userTimezone, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $result = $stmt->execute();
        $course_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$result) {
            return array(
                "status" => "Error",
                "error" => "Couldn't get the details of the session"
            );
        } elseif (count($course_data) === 0) {
            return array(
                "status" => "Error",
                "error" => "Session not found"
            );
        }
        $data = $course_data[0];
        $data['date'] = date('j M Y', strtotime($data['start_timestamp']));
        $data['time'] = date('H:i', strtotime($data['start_timestamp']));

        $topics_of_session = Topics::getTopicsOfTheSession($data['session_id']);
        $data['topics'] = $topics_of_session;

        return array(
            "status" => "Success",
            "data" => $data
        );
    }

    private static function getTopicsOfTheSession($session_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                `topic_id`,
                                `topic_type`,
                                `general_topic`,
                                `name`,
                                `module_id`,
                                `module_index`,
                                `module_name`
                            FROM
                                `course_session_to_topic_mapping`
                            LEFT JOIN(
                                SELECT
                                    `subject_topics`.`id`,
                                    `subject_topics`.`name`,
                                    `subject_modules`.`module_id`,
                                    `module_index`,
                                    `module_name`
                                FROM
                                    `subject_topics`
                                JOIN `subject_modules` ON(
                                        `subject_topics`.`module_id` = `subject_modules`.`module_id`
                                    )
                                WHERE `subject_topics`.`status` = :status
                                    AND `subject_modules`.`status` = :status
                            ) AS subject_topics ON
                                (
                                    `course_session_to_topic_mapping`.`topic_type` = 'SUBJECT_TOPIC'
                                    AND `course_session_to_topic_mapping`.`topic_id` = subject_topics.id
                                )
                            WHERE `session_id` = :session_id
                                AND `course_session_to_topic_mapping`.`status` = :status
                            ORDER BY `course_session_to_topic_mapping`.`order` ASC ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $result = $stmt->execute();
        $topics_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$result) {
            return array(
                "status" => "Error",
                "error" => "Couldn't get the details of the session"
            );
        }
        return array(
            "status" => "Success",
            "data" => $topics_data
        );
    }

    public static function updateSession($data) {

        $validation = static::validateSessionUpdateData($data);
        if ($validation['status'] === "Error") {
            return $validation;
        }
        $topics = $validation['topics'];
        $course_id = $data['course_id'];
        $session_id = $data['session_id'];
        $session_index = $data['session_index'];
        $session_name = $data['session_name'];

        $db = static::getDB();
        //Start the transaction to make sure all queries ahead are executed successfully before the commit
        $db->beginTransaction();

        //First update Session index and Name
        $stmt = $db->prepare("UPDATE `course_sessions`
                            SET `session_index` = :session_index,
                                `session_name` = :session_name
                            WHERE `course_id` = :course_id
                                AND `session_id` = :session_id
                                AND `status` = :status ");

        $stmt->bindValue(':session_index', $session_index, PDO::PARAM_INT);
        $stmt->bindValue(':session_name', $session_name, PDO::PARAM_STR);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
//            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while updating session name and index"
            );
        }

        //Then update Session's schedule
        $session_date_time_user = $data['session_start_date_time'];

        $session_start_time_utc = gmdate('Y-m-d H:i:s', strtotime($session_date_time_user));

        $session_date_utc = date('Y-m-d', strtotime($session_start_time_utc));
        $duration = $data['session_duration'];
        $meeting_link = "";
        if ($data['remote_meeting_availability'] === "Yes") {
            $meeting_link = trim($data['remote_meeting_link']);
        }

        $stmt = $db->prepare("UPDATE `course_session_schedules`
                            SET `date` = :date,
                                `start_timestamp` = :start_timestamp,
                                `duration` = :duration,
                                `meeting_link` = :meeting_link
                            WHERE `session_id` = :session_id
                            AND `status` =  :status ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $session_date_utc, PDO::PARAM_STR);
        $stmt->bindValue(':start_timestamp', $session_start_time_utc, PDO::PARAM_STR);
        $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
        $stmt->bindValue(':meeting_link', $meeting_link, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while updating session's schedule and meeting link"
            );
        }

        //Disable previously mapped topics of the session
        $stmt = $db->prepare("UPDATE `course_session_to_topic_mapping`
                            SET `status` = :status
                            WHERE `session_id` = :session_id ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollback();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while updating session's current topics"
            );
        }

        foreach ($topics as $order => $topic) {

            if ($topic['type'] === "subject_topic") {
                $topic_id = $topic['id'];
                $stmt = $db->prepare("INSERT INTO `course_session_to_topic_mapping`
                        (`session_id`, `topic_type`, `topic_id`, `general_topic`, `order`, `status`)
                        VALUES ('$session_id', 'SUBJECT_TOPIC', '$topic_id', '', '$order', 'ACTIVE') ");
            } elseif ($topic['type'] === "general") {
                $general_topic = $topic['name'];
                $stmt = $db->prepare("INSERT INTO `course_session_to_topic_mapping`
                        (`session_id`, `topic_type`, `topic_id`, `general_topic`, `order`, `status`)
                        VALUES ('$session_id', 'GENERAL_TOPIC', '0', '$general_topic', '$order', 'ACTIVE') ");
            } else {
                $db->rollback();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error while adding topics. invalid topic type"
                );
            }
            if (!$stmt->execute()) {
                $db->rollback();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error while adding new topics"
                );
            }
        }

        //Everything is over, commit and respond success
        $db->commit();

        return array(
            "status" => "Success"
        );
    }

    private static function validateSessionUpdateData($data) {
        $course_id = $data['course_id'];
        $session_id = $data['session_id'];
        $session_index = $data['session_index'];
        if (!static::IsSessionIndexUnique($course_id, $session_index, $session_id)) {
            return array(
                "status" => "Error",
                "error" => "Session index already exists"
            );
        }
        $vidation = static ::validateCourseToTopicMapping($course_id, $data['topics']);

        if ($vidation['status'] === "Error") {
            return $vidation;
        }
        $new_topics = $vidation['topics'];

        return array(
            "status" => "Success",
            "topics" => $new_topics
        );
    }

    private static function IsSessionIndexUnique($course_id, $session_index, $session_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT COUNT(`session_id`) session_count
                            FROM `course_sessions`
                            WHERE `course_id` = :course_id
                                AND `session_index` = :session_index
                                AND `status` = :status
                                AND `session_id` != :session_id");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_index', $session_index, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($result[0] > 0) {
            return false;
        }
        return true;
    }

    private static function disableSession($course_id, $session_id) {
        $db = static::getDB();

        $stmt = $db->prepare("UPDATE `course_sessions`
                                SET `status` = :status
                            WHERE `course_id` = :course_id
                                AND `session_id` = :session_id ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);
        $result = $stmt->execute();

        if (!$result) {
            return array(
                "status" => "Error",
                "error" => "Couldn't update the session"
            );
        }
        return array(
            "status" => "Success"
        );
    }

    public static function isSessionBelongsToCourse($session_id, $course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT COUNT(`session_id`) as session_count
                            FROM `course_sessions`
                            WHERE `session_id` = :session_id
                                AND `course_id` = :course_id
                                AND `status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if ($stmt->execute()) {

            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($result[0] == 1) {
                return true;
            }
        }
        return false;
    }

}
