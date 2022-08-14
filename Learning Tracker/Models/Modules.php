<?php

namespace App\Models;

use PDO;
use App\s3;
use App\Models\Assignments;

/**
 * modules model
 *
 * PHP version 5.4
 */
class Modules extends \Core\Model {

    /**
     * Get some values as an associative array, some as column, some as int
     *
     * @return array called module details
     */
    public static function getModuleDetails($course_id, $module_index) {
        $user_id = $_SESSION['user_id'];
        try {

            $db = static::getDB();

            //this query will give module index, module id, module name from course modules table
            $stmt = $db->prepare('SELECT `module_id`, `module_index`, `module_name`
                                FROM `course_modules`
                                WHERE course_id = :course_id
                                    AND module_index = :module_index
                                    AND `status` = :status
                                ORDER BY `module_id` ASC');
            $stmt->bindValue(':module_index', $module_index, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $value = array_pop($results);

            //this query will give module name, module index from course modules table
            $stmt = $db->prepare('SELECT `module_name`, `module_index`
                                FROM `course_modules`
                                WHERE course_id = :course_id
                                    AND `status` = :status
                                ORDER BY `module_id` ASC');
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result5 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $videos_section = array();

            //this query will gives the video details
            $stmt = $db->prepare('SELECT `id`, `thumbnail_link`, `video_link`, `transcript_link`
                                FROM `course_module_videos`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
            $stmt->bindValue(':module_id', $value['module_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //this query will gives presentation details
            $stmt = $db->prepare('SELECT `presentation_link`, `thumbnail_link`, `name`
                                FROM `course_module_presentations`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
            $stmt->bindValue(':module_id', $value['module_id'], PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $videos_section['video_parts'] = $result1;
            $videos_section['presentation_parts'] = $result2;

            //this query will gives exam prep details
            $stmt = $db->prepare('SELECT `id`, `question_index`,
                                    `short_description`, `question_body`
                                FROM `exam_prep`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
            $stmt->bindValue(':module_id', $value['module_id'], PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result6 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $exam_prep_details = array();

            foreach ($result6 as $value2) {
                //this query will give exam prep options
                $stmt = $db->prepare('SELECT `id`, `option_type`, `option_description`
                                FROM `exam_prep_options`
                                WHERE `question_id` = :question_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
                $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                //this query will give exam prep answers
                $stmt = $db->prepare('SELECT `option_type`, `score`
                                FROM `exam_prep_answers`
                                WHERE `question_id` = :question_id
                                    AND `user_id` = :user_id
                                    AND `status` = :status');
                $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result12 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result12) != 0) {

                    //this query will give exam prep options details
                    $stmt = $db->prepare("SELECT `option_type`, `correctness`, `score`, `rationale`
                                        FROM `exam_prep_options`
                                        WHERE `question_id` = :question_id AND `status` = :status
                                        ORDER BY `id` ASC");
                    $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                    $result13 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    array_push($exam_prep_details, array(
                        "question_details" => $value2,
                        "options" => $result7,
                        "answer_status" => "answered",
                        "answer_details" => $result12[0],
                        "all_answers" => $result13
                    ));
                } else {
                    array_push($exam_prep_details, array(
                        "question_details" => $value2,
                        "options" => $result7,
                        "answer_status" => "not answered"
                    ));
                }
            }

            //this query will gives sub session id from course sub sessions table
            $stmt = $db->prepare('SELECT `sub_session_id`
                                FROM `course_sub_sessions`
                                WHERE module_id = :module_id
                                    AND `status` = :status
                                ORDER BY `sub_session_id` ASC');
            $stmt->bindValue(':module_id', $value['module_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $moduleTopics = array();
            foreach ($result3 as $value1) {
                //this query will gives the topics from course sub session topics
                $stmt = $db->prepare('SELECT `topic`
                                FROM `course_sub_session_topics`
                                WHERE sub_session_id = :sub_session_id
                                    AND `status` = :status
                                ORDER BY `sub_session_id` ASC');
                $stmt->bindValue(':sub_session_id', $value1['sub_session_id'], PDO::PARAM_STR);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result4 = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $moduleTopics = array_merge($moduleTopics, $result4);
            }
            $videos_section['topics'] = $moduleTopics;

            //returns an array
            return array(
                "module_index" => $value['module_index'],
                "module_name" => $value['module_name'],
                "all_modules" => $result5,
                "videos_section" => $videos_section,
                "exam_prep_details" => $exam_prep_details
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Get some values as an associative array, some as column, some as int
     *
     * @return array called module details
     */
    public static function getModuleDetailsNew($course_id, $module_index, $moduleList) {
        $user_id = $_SESSION['user_id'];
        foreach ($moduleList as $module) {
            if ($module['module_index'] == $module_index) {
                $module_id = $module['module_id'];
                $module_name = $module['module_name'];
            }
        }
        if (!isset($module_id)) {
            return array();
        }
        try {

            $db = static::getDB();

//            Get the list of topics of the module
            $stmt = $db->prepare("SELECT `id`, `name`
                                FROM `subject_topics`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC "
            );
            $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $topicList = $stmt->fetchAll(PDO::FETCH_ASSOC);

//            Get the first image of the module
            $presentationImageDetails = array();
            foreach ($topicList as $topic) {
                $stmt = $db->prepare("SELECT `id`,`topic_id`, `order`, `image_link`
                                    FROM `subject_topic_presentation_images`
                                    WHERE `topic_id` = :topic_id
                                        AND `status` = :status
                                    ORDER BY `order` ASC LIMIT 0,1 "
                );
                $stmt->bindValue(':topic_id', $topic['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $presentationImageDetails = $stmt->fetch(PDO::FETCH_ASSOC);
                    $presentationImageDetails['topic_name'] = $topic['name'];
                    $subject_version = $_SESSION['subjectVersion'];
                    $subjectTopicName = $presentationImageDetails['topic_name'];
                    $fileName = $presentationImageDetails['image_link'];
                    $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'], "Module$module_index/v$subject_version/$subjectTopicName/$fileName");
                    $presentationImageDetails['file_path'] = $filePath;

                    //            Get count of total images
                    $stmt = $db->prepare("SELECT COUNT(`subject_topic_presentation_images`.`id`) as Count
                                        FROM `subject_topic_presentation_images`
                                            JOIN `subject_topics` ON(`subject_topic_presentation_images`.`topic_id` = `subject_topics`.`id`)
                                        WHERE `subject_topics`.`module_id` = :module_id
                                            AND `subject_topics`.`status` = :status
                                            AND `subject_topic_presentation_images`.`status` = :status "
                    );
                    $stmt->bindValue(':module_id', $module_id, PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $presentationImageDetails['count_of_slides'] = $result[0];

                    break;
                }
            }

            //this query will gives exam prep details
            $stmt = $db->prepare('SELECT `id`, `question_index`,
                                    `short_description`, `question_body`
                                FROM `exam_prep`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
            $stmt->bindValue(':module_id', $module_id, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result6 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $exam_prep_details = array();

            foreach ($result6 as $value2) {
                //this query will give exam prep options
                $stmt = $db->prepare('SELECT `id`, `option_type`, `option_description`
                                FROM `exam_prep_options`
                                WHERE `question_id` = :question_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
                $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result7 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                //this query will give exam prep answers
                $stmt = $db->prepare('SELECT `option_type`, `score`
                                FROM `exam_prep_answers`
                                WHERE `question_id` = :question_id
                                    AND `user_id` = :user_id
                                    AND `status` = :status');
                $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result12 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result12) != 0) {

                    //this query will give exam prep options details
                    $stmt = $db->prepare("SELECT `option_type`, `correctness`, `score`, `rationale`
                                        FROM `exam_prep_options`
                                        WHERE `question_id` = :question_id AND `status` = :status
                                        ORDER BY `id` ASC");
                    $stmt->bindValue(':question_id', $value2['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                    $result13 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    array_push($exam_prep_details, array(
                        "question_details" => $value2,
                        "options" => $result7,
                        "answer_status" => "answered",
                        "answer_details" => $result12[0],
                        "all_answers" => $result13
                    ));
                } else {
                    array_push($exam_prep_details, array(
                        "question_details" => $value2,
                        "options" => $result7,
                        "answer_status" => "not answered"
                    ));
                }
            }


            //returns an array
            return array(
                "module_id" => $module_id,
                "module_index" => $module_index,
                "module_name" => $module_name,
                "topic_list" => $topicList,
                "presentation_image" => $presentationImageDetails,
                "exam_prep_details" => $exam_prep_details
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Get the list of modules to show in nav-bar drop-down
     * @param type $subject_id
     * @return type array of
     */
    public static function getModuleList($subject_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `module_id`, `module_index`, `module_name`
                            FROM `subject_modules`
                            WHERE `subject_id` = :subject_id
                                AND `status` = :status
                            ORDER by `module_index` ASC ");
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     *  For a given Course_id and Module_index get all the exam Preparation activities configured
      Also get its answered status
     */
    public static function getExamPrepDetails($course_id, $module_id) {
        $user_id = $_SESSION['user_id'];

        try {

            $db = static::getDB();


            //this query will gives exam prep details
            $stmt = $db->prepare('SELECT `id`, `question_index`,
                                    `short_description`, `question_body`
                                FROM `exam_prep`
                                WHERE `module_id` = :module_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
            $stmt->bindValue(':module_id', $module_id, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $exam_prep_details = array();

            foreach ($result as $question) {
                //this query will give exam prep options
                $stmt = $db->prepare('SELECT `id`, `option_type`, `option_description`
                                FROM `exam_prep_options`
                                WHERE `question_id` = :question_id
                                    AND `status` = :status
                                ORDER BY `id` ASC');
                $stmt->bindValue(':question_id', $question['id'], PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

                //this query will give exam prep answers
                $stmt = $db->prepare('SELECT `option_type`, `score`
                                FROM `exam_prep_answers`
                                WHERE `question_id` = :question_id
                                    AND `user_id` = :user_id
                                    AND `status` = :status');
                $stmt->bindValue(':question_id', $question['id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                $stmt->execute();
                $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($result2) != 0) {

                    //this query will give exam prep options details
                    $stmt = $db->prepare("SELECT `option_type`, `correctness`, `score`, `rationale`
                                        FROM `exam_prep_options`
                                        WHERE `question_id` = :question_id AND `status` = :status
                                        ORDER BY `id` ASC");
                    $stmt->bindValue(':question_id', $question['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
                    $stmt->execute();
                    $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    array_push($exam_prep_details, array(
                        "question_details" => $question,
                        "options" => $result1,
                        "answer_status" => "answered",
                        "answer_details" => $result2[0],
                        "all_answers" => $result3
                    ));
                } else {
                    array_push($exam_prep_details, array(
                        "question_details" => $question,
                        "options" => $result1,
                        "answer_status" => "not answered"
                    ));
                }
            }


            //returns an array
            return $exam_prep_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

}
