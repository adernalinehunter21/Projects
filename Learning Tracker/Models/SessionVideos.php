<?php

namespace App\Models;

use PDO;
use App\s3;

/**
 * SessionVideos model
 *
 * PHP version 5.4
 */
class SessionVideos extends \Core\Model {
    /**
     * Get all the session video details as an associative array
     *
     * @return array
     */

    /**
     * This function will take user_id as an argument and returns subjects
     */
    public static function getSubjectList($user_id) {

        try {

            //this query will give subject from subjects table based on facilitator user id
            $db = static::getDB();
            $stmt = $db->prepare('SELECT `user_to_course_mapping`.`course_id`,
                            `courses`.`subject_id` AS subject_id,
                            `subjects`.`subject`, `version`,
                            `courses`.`course_name`, `courses`.`course_id`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                            JOIN `subjects` ON (`courses`.`subject_id` = `subjects`.`id`)
                        WHERE  `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status
                            AND `subjects`.`status` = :status');

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $subjectsOfTheUser = array();
            foreach ($results as $value) {
                $subjectId = $value['subject_id'];
                if (!isset($subjectsOfTheUser[$subjectId])) {
                    $subjectsOfTheUser[$subjectId] = $value['subject'] . ' v' . $value['version'];
                }
            }
            return $subjectsOfTheUser;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * this function will take subjectId as parameter and gives the programList
     */
    public static function getProgramList($subjectId) {
        $user_id = $_SESSION['user_id'];
        try {
            $programs = array();
            $db = static::getDB();
            //this query will give course names from courses table
            $stmt = $db->prepare('SELECT `user_to_course_mapping`.`course_id`,
                            `courses`.`course_name`
                        FROM `user_to_course_mapping`
                            JOIN `courses` ON (`user_to_course_mapping`.`course_id` = `courses`.`course_id`)
                        WHERE  `courses`.`subject_id` = :subject_id
                            AND `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`status` = :status
                            AND `courses`.`status` = :status');
            $stmt->bindValue(':subject_id', $subjectId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            array_push($programs, $results);
            return array(
                "status" => "Success",
                "programs" => $programs
            );
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    /**
     * this function will take courseId as parameter and gives the sessionList
     * this function also give course session video details and corresponding topics
     */
    public static function getSessionList($courseId) {
        $user_id = $_SESSION['user_id'];
        try {
            $db = static::getDB();
            $sessions = array();
            $course_session_videos = array();
            $session_videos = array();
            //this query will give subject from subjects table based on facilitator user id
            $stmt = $db->prepare('SELECT `user_to_course_mapping`.`course_id`,
                            `course_sessions`.`session_id`,
                            `session_index`,
                            `course_sessions`.`session_name`
                        FROM `user_to_course_mapping`
                            JOIN `course_sessions` ON (`user_to_course_mapping`.`course_id` = `course_sessions`.`course_id`)
                        WHERE `course_sessions`.`course_id` = :course_id
                            AND `user_to_course_mapping`.`user_id` = :user_id
                            AND `user_to_course_mapping`.`status` = :status
                            AND `course_sessions`.`status` = :status
                        ORDER BY `course_sessions`.`session_id` ASC');
            $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            array_push($sessions, $results);

            foreach ($results as $value1) {
                $sessionVideoDetails = array();
                //this query will give id, link_type, thumbnail_link, video_link and transcript_link from course_session_videos table based on  session_id
                $stmt = $db->prepare('SELECT `id`, 
                            `link_type`, 
                            `thumbnail_link`, 
                            `video_link`, 
                            `transcript_link`, 
                            `upload_log_id`,
                            `name`
                        FROM `course_session_videos` 
                        WHERE `session_id` = :session_id 
                            AND `status` = :status');
                $stmt->bindValue(':session_id', $value1['session_id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return array(
                        "status" => "Error",
                        "message" => "error while getting data"
                    );
                }
                $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($result2 as $oneVideo) {
                    $linkType = $oneVideo['link_type'];
                    if ($linkType === "NEW") {
                        $thumbnail = $oneVideo['thumbnail_link'];
                        if ($thumbnail !== "") {
                            $thumbnailLink = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $thumbnail);
                        } else {
                            $thumbnailLink = "";
                        }

                        $video = $oneVideo['video_link'];
                        $videoLink = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $video);
                        $transcript = $oneVideo['transcript_link'];
                        if ($transcript === "") {
                            $transcriptLink = "";
                        } else {
                            $transcriptLink = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $transcript);
                        }
                        //this query will give topics from subject_topics table based on  session_id and user_id
                        $stmt = $db->prepare('SELECT `session_video_upload_log`.`id`, `session_video_upload_details`.`session_topic_mapping_id`, `subject_topics`.`name`
                                    FROM `session_video_upload_log`
                                        JOIN `session_video_upload_details` ON (`session_video_upload_log`.`id` = `session_video_upload_details`.`upload_log_id`)
                                        JOIN `course_session_to_topic_mapping` ON (`session_video_upload_details`.`session_topic_mapping_id` = `course_session_to_topic_mapping`.`id`)
                                        JOIN `subject_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`)
                                    WHERE `session_video_upload_log`.`id` = :id
                                        AND `course_session_to_topic_mapping`.`topic_type` = :topic_type
                                        AND `session_video_upload_log`.`status` = :status
                                        AND `session_video_upload_details`.`status` = :status
                                        AND `subject_topics`.`status` = :status
                                        AND `course_session_to_topic_mapping`.`status` = :status');
                        $stmt->bindValue(':id', $oneVideo['upload_log_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':topic_type', "SUBJECT_TOPIC", PDO::PARAM_STR);
                        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                        if (!$stmt->execute()) {
                            return array(
                                "status" => "Error",
                                "message" => "error while getting data"
                            );
                        }
                        $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        array_push($sessionVideoDetails, array(
                            "sessionVideoId" => $oneVideo['id'],
                            "name" => $oneVideo['name'],
                            "thumbnailLink" => $thumbnailLink,
                            "videoLink" => $videoLink,
                            "transcriptLink" => $transcriptLink,
                            "topics" => $result3
                        ));
                    } else {

                        array_push($sessionVideoDetails, array(
                            "sessionVideoId" => $oneVideo['id'],
                            "name" => $oneVideo['name'],
                            "thumbnailLink" => $oneVideo['thumbnail_link'],
                            "videoLink" => $oneVideo['video_link'],
                            "transcriptLink" => $oneVideo['transcript_link'],
                            "topics" => ""
                        ));
                    }
                }

                array_push($course_session_videos, array(
                    "session_id" => $value1['session_id'],
                    "session_name" => $value1['session_name'],
                    "session_index" => $value1['session_index'],
                    "session_videos" => $sessionVideoDetails,
                ));
            }

            return array(
                "status" => "Success",
                "sessions" => $sessions,
                "course_session_videos" => $course_session_videos
            );
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    /**
     * this function will take sessionId as parameter and gives the topicsList
     */
    public static function getTopicList($sessionId) {
        try {
            $db = static::getDB();
            $topics = array();
            //this query will give topics from subject_topics table based on topic_id from course_session_to_topic_mapping table
            $stmt = $db->prepare('SELECT `course_session_to_topic_mapping`.`topic_id`, 
                            `course_session_to_topic_mapping`.`id`, 
                            `subject_topics`.`name` 
                        FROM `course_session_to_topic_mapping`
                            JOIN `subject_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`)
                        WHERE `course_session_to_topic_mapping`.`session_id` = :session_id
                            AND `course_session_to_topic_mapping`.`status` = :status
                            AND `subject_topics`.`status` = :status
                        ORDER BY `subject_topics`.`id` ASC');
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return array(
                    "status" => "Error",
                    "message" => "error while getting data"
                );
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array(
                "status" => "Success",
                "topics" => $results
            );
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return array(
                "status" => "Error",
                "message" => "Could not connect to database! Please report and try after some time " . $error
            );
        }
    }

    /**
     * This function will update video details into session_video_upload_log, session_video_upload_details and course_session_videos
     */
    public static function updateVideoDetails($sessionVideoUploadDetails) {
        $myJSON = json_encode($sessionVideoUploadDetails);
        try {
            if (!isset($sessionVideoUploadDetails['session_id'])) {
                return array(
                    "status" => "Error",
                    "error" => "You seem to have not got the latest updates. Please hard-reload the page and try again"
                );
            }
            $sessionId = $sessionVideoUploadDetails['session_id'];
            $user_id = $_SESSION['user_id'];

            $db = static::getDB();

            //Start the transaction
            $db->beginTransaction();

            //Inserting sessionId, detailed_json and userId into session_video_upload_log table
            $stmt = $db->prepare("INSERT INTO `session_video_upload_log` 
                                    (`session_id`, `detailed_json`, `user_id`, `status`) 
                                VALUES (:session_id, :detailed_json, :user_id, :status) ");
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->bindValue(':detailed_json', $myJSON, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Error while storing data"
                );
            }
            $uploadLogId = $db->lastInsertId();

            if (isset($sessionVideoUploadDetails['topics']) && count($sessionVideoUploadDetails['topics']) > 0) {
                $topics = $sessionVideoUploadDetails['topics'];
                foreach ($topics as $oneTopic) {
                    $session_topic_mspping_id = $oneTopic['id'];
                    $startTime = static::secondsToFormattedTime($oneTopic['start']);
                    $endTime = static::secondsToFormattedTime($oneTopic['end']);

                    $stmt = $db->prepare("INSERT INTO `session_video_upload_details` 
                                        (`upload_log_id`, `session_topic_mapping_id`, `start_time`, `end_time`, `status`) 
                                    VALUES(
                                        :upload_log_id, 
                                        :session_topic_mapping_id, 
                                        :start_time, 
                                        :end_time, 
                                        :status ) ");

                    $stmt->bindValue(':upload_log_id', $uploadLogId, PDO::PARAM_INT);
                    $stmt->bindValue(':session_topic_mapping_id', $session_topic_mspping_id, PDO::PARAM_INT);
                    $stmt->bindValue(':start_time', $startTime, PDO::PARAM_STR);
                    $stmt->bindValue(':end_time', $endTime, PDO::PARAM_STR);
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
            //Video file details
//            $videoFileType = $sessionVideoUploadDetails['video_file_details']['fileType'];
//            $videoFileSize = $sessionVideoUploadDetails['video_file_details']['fileSize'];
//            $videoUploadedFileName = $sessionVideoUploadDetails['video_file_details']['fileName'];
            $videoInternalFileName = $sessionVideoUploadDetails['video_file_details']['internalFileName'];

            //Video name
            if (isset($sessionVideoUploadDetails['video_name'])) {
                $videoName = $sessionVideoUploadDetails['video_name'];
            } else {
                $videoName = null;
            }

            //Thumbnail image file details
            if (isset($sessionVideoUploadDetails['thumnail_file_details']) && is_array($sessionVideoUploadDetails['thumnail_file_details'])) {
//                $thumbnailFileType = $sessionVideoUploadDetails['thumnail_file_details']['fileType'];
//                $thumbnailFileSize = $sessionVideoUploadDetails['thumnail_file_details']['fileSize'];
//                $thumbnailUploadedFileName = $sessionVideoUploadDetails['thumnail_file_details']['fileName'];
                $thumbnailInternalFileName = $sessionVideoUploadDetails['thumnail_file_details']['internalFileName'];
            } else {
                $thumbnailInternalFileName = "";
            }

            //Transcript vtt file details
            if (isset($sessionVideoUploadDetails['transcript_file_details']) && is_array($sessionVideoUploadDetails['transcript_file_details'])) {
//                $transcriptFileType = $sessionVideoUploadDetails['transcript_file_details']['fileType'];
//                $transcriptFileSize = $sessionVideoUploadDetails['transcript_file_details']['fileSize'];
//                $transcriptUploadedFileName = $sessionVideoUploadDetails['transcript_file_details']['fileName'];
                $transcriptInternalFileName = $sessionVideoUploadDetails['transcript_file_details']['internalFileName'];
            } else {
                $transcriptInternalFileName = "";
            }
            //Inserting session_id, link_type, thumbnail_link, video_link, transcript_link and upload_log_id into course_session_videos table
            $stmt = $db->prepare("INSERT INTO `course_session_videos` 
                                    (`session_id`, `link_type`, `thumbnail_link`, `video_link`, `transcript_link`, `name`, `upload_log_id`, `status`) 
                                VALUES (
                                    :session_id, 
                                    :link_type, 
                                    :thumbnail_link, 
                                    :video_link, 
                                    :transcript_link, 
                                    :videoName,
                                    :upload_log_id, 
                                    :status
                                ) ");
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->bindValue(':link_type', "NEW", PDO::PARAM_STR);
            $stmt->bindValue(':thumbnail_link', $thumbnailInternalFileName, PDO::PARAM_STR);
            $stmt->bindValue(':video_link', $videoInternalFileName, PDO::PARAM_STR);
            $stmt->bindValue(':transcript_link', $transcriptInternalFileName, PDO::PARAM_STR);
            $stmt->bindValue(':videoName', $videoName, PDO::PARAM_STR);
            $stmt->bindValue(':upload_log_id', $uploadLogId, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "message" => "Error while storing data"
                );
            }

            //Finally commit and response success to client
            $db->commit();

            return array(
                "status" => "Success"
            );
        } catch (PDOException $e) {
            return array(
                "status" => "Error",
                "message" => $e->getMessage()
            );
        }
    }

    private static function secondsToFormattedTime($seconds) {
        return gmdate('H:i:s', $seconds);
    }

    public static function removeVideo($video_id, $session_id, $user_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                `link_type`,
                                `thumbnail_link`,
                                `video_link`,
                                `transcript_link`,
                                `name`,
                                `upload_log_id`
                            FROM
                                `course_session_videos`
                            WHERE
                                `session_id` = :session_id 
                                AND `id` = :video_id 
                                AND `status` = :status ");
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':video_id', $video_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return [
                "status" => "Error",
                "error" => "Error while getting the details of the video to remove"
            ];
        }
        if ($stmt->rowCount() === 0) {
            return [
                "status" => "Error",
                "error" => "Either video has already been removed or it is an invalid video"
            ];
        }
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $video_details = $results[0];

        if ($video_details['link_type'] != 'NEW') {
            return [
                "status" => "Error",
                "error" => "Removal of this type of video not supported"
            ];
        }

        //Start the transaction
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE `course_session_videos` 
                                SET `status` = :status
                            WHERE `session_id` = :session_id
                                AND `id` = :video_id ");
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':video_id', $video_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollBack();
            return [
                "status" => "Error",
                "error" => "Error while removing session video"
            ];
        }

        $video_file = $video_details['video_link'];
        if (!static::queueFileForRemoval($db, $video_file, $user_id)) {
            $db->rollBack();
            return [
                "status" => "Error",
                "error" => "Error while enqueuing video file for removal"
            ];
        }

        $thumbnail_file = trim($video_details['thumbnail_link']);
        if ($thumbnail_file) {
            if (!static::queueFileForRemoval($db, $thumbnail_file, $user_id)) {
                $db->rollBack();
                return [
                    "status" => "Error",
                    "error" => "Error while enqueuing thumbnail file for removal"
                ];
            }
        }

        $transcription_file = trim($video_details['transcript_link']);
        if ($transcription_file) {
            if (!static::queueFileForRemoval($db, $transcription_file, $user_id)) {
                $db->rollBack();
                return [
                    "status" => "Error",
                    "error" => "Error while enqueuing transcription file for removal"
                ];
            }
        }
        //Finally commit and response success to client
        $db->commit();

        return array(
            "status" => "Success"
        );
    }

    private static function queueFileForRemoval($db, $file, $user_id) {
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO `files_to_be_deleted`(
                                `id`,
                                `uploaded_purpose`,
                                `internal_file_name`,
                                `user_id`,
                                `time_stamp`,
                                `status`
                            )
                            VALUES(
                                null,
                                :uploaded_purpose,
                                :internal_file_name,
                                :user_id,
                                :time_stamp,
                                :status
                            ) ");
        $stmt->bindValue(':uploaded_purpose', 'SESSION_VIDEO', PDO::PARAM_STR);
        $stmt->bindValue(':internal_file_name', $file, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':time_stamp', $now, PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return false;
        }
        return true;
    }

}
