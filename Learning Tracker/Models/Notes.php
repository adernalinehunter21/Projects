<?php

namespace App\Models;

use PDO;
use App\s3;

class Notes extends \Core\Model {

    public static function saveNote($topic_id, $order, $notes) {
        $user_id = $_SESSION['user_id'];


        $sql = "SELECT `id`
				FROM `subject_topic_presentation_images` 
				WHERE `topic_id` = :topic_id 
					AND `order` = :order 
					AND `status` = :status ";

        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindValue(':order', $order, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $slide_id = $results[0];

        $now = gmdate('Y-m-d H:i:s');

        $sql = "SELECT *
				FROM `learner_slide_notes` 
				WHERE `slide_id`= :slide_id 
					AND `user_id`= :user_id";

        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':slide_id', $slide_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) == 0) {
            $sql = "INSERT INTO `learner_slide_notes` 
					(`id`, `slide_id`, `user_id`, `note`, `creation_time`, `update_time`) 
					VALUES 
						(NULL,
						:slide_id, 
						:user_id,
						:notes, 
						:now,
						'0000-00-00 00:00:00.000000'
						)";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':slide_id', $slide_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->bindValue(':now', $now, PDO::PARAM_STR);
            $stmt->execute();
            return $db->lastInsertId();
        } else {
            return false;
        }
    }

    public static function getModuleNotes($module_index, $subject_id) {

        $user_id = $_SESSION['user_id'];

        $db = static::getDB();

        $sql = " SELECT `slide_id`, `note`,`topic_id`,`subject_topic_presentation_images`.`order`,`subject_topic_presentation_images`.`image_link`,`subject_topics`.`name` AS topic_name
			FROM `subject_modules`
				JOIN `subject_topics` ON (`subject_modules`.`module_id`=`subject_topics`.`module_id`)
				JOIN `subject_topic_presentation_images` ON (`subject_topics`.`id` = `subject_topic_presentation_images`.`topic_id`)
				JOIN `learner_slide_notes` ON (`subject_topic_presentation_images`.`id` = `learner_slide_notes`.`slide_id`)
			WHERE `subject_modules`.`subject_id`=:subject_id
				AND `subject_modules`.`module_index`=:module_index
				AND `subject_modules`.`status`=:status
				AND `subject_topics`.`status`=:status
				AND `subject_topic_presentation_images`.`status`=:status
				AND `learner_slide_notes`.`user_id`=:user_id
			ORDER BY `slide_id` ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->bindValue(':module_index', $module_index, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $notes = array();

        $subjectVersion = $_SESSION['subjectVersion'];

        foreach ($results as $row) {
            $subjectTopicName = $row['topic_name'];
            $fileName = $row['image_link'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'],
                            "Module$module_index/v$subjectVersion/$subjectTopicName/$fileName");
            array_push($notes, array(
                "topic_id" => $row["topic_id"],
                "topic_name" => $row['topic_name'],
                "order" => $row["order"],
                "slide_link" => $filePath,
                "note" => $row['note']
            ));
        }

        return $notes;
    }
  
    public static function getSessionNotes($session_index, $course_id) {

        $user_id = $_SESSION['user_id'];
        $notes = array();

        $db = static::getDB();

        $user_id = $_SESSION['user_id'];

        $sql = " SELECT `learner_slide_notes`.`note`,
                    `learner_slide_notes`.`slide_id`,
                    `subject_topic_presentation_images`.`topic_id`,
                    `subject_topic_presentation_images`.`order`,
                    `subject_topic_presentation_images`.`image_link`,
                    `subject_topics`.`name` AS topic_name,
                    `subject_modules`.`module_index`
                FROM `course_sessions` 
                    JOIN `course_session_to_topic_mapping` ON (`course_sessions`.`session_id` = `course_session_to_topic_mapping`.`session_id`)
                    JOIN `subject_topics` ON (`course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`)
                    JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                    JOIN `subject_topic_presentation_images` ON (`subject_topics`.`id` = `subject_topic_presentation_images`.`topic_id`)
                    JOIN `learner_slide_notes` ON (`subject_topic_presentation_images`.`id` = `learner_slide_notes`.`slide_id`)
                WHERE `course_sessions`.`course_id` = :course_id
                    AND `course_sessions`.`session_index` = :session_index
                    AND `learner_slide_notes`.`user_id` = :user_id
                    AND `course_sessions`.`status` = :status
                    AND `course_session_to_topic_mapping`.`status` = :status
                    AND `subject_topics`.`status` = :status
                    AND `subject_modules`.`status` = :status
                    AND `subject_topic_presentation_images`.`status` = :status
                ORDER BY `slide_id` ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':session_index', $session_index, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subjectVersion = $_SESSION['subjectVersion'];

        foreach ($results as $row) {
            $subjectTopicName = $row['topic_name'];
            $fileName = $row['image_link'];
            $module_index = $row['module_index'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', $_SESSION['content_s3_bucket'],
                            "Module$module_index/v$subjectVersion/$subjectTopicName/$fileName");
            array_push($notes, array(
                "topic_id" => $row["topic_id"],
                "order" => $row["order"],
                "slide_id" => $row["slide_id"],
                "slide_link" => $filePath,
                "note" => $row['note']
            ));
        }

        return $notes;
    }

    public static function updateNote($topic_id, $order, $notes) {
        $user_id = $_SESSION['user_id'];


        $sql = "SELECT `id`
				FROM `subject_topic_presentation_images` 
				WHERE `topic_id` = :topic_id 
					AND `order` = :order 
					AND `status` = :status ";

        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindValue(':order', $order, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $slide_id = $results[0];

        $now = gmdate('Y-m-d H:i:s');

        $sql = "UPDATE `learner_slide_notes` 
				SET `note`= :note, `update_time`= :update_time  
				WHERE `slide_id` = :slide_id
					AND `user_id` = :user_id";


        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':note', $notes, PDO::PARAM_STR);
        $stmt->bindValue(':update_time', $now, PDO::PARAM_STR);
        $stmt->bindValue(':slide_id', $slide_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        return $slide_id;
    }

    public static function deleteNote($topic_id, $order) {
        $user_id = $_SESSION['user_id'];


        $sql = "SELECT `id`
                FROM `subject_topic_presentation_images` 
                WHERE `topic_id` = :topic_id 
                        AND `order` = :order 
                        AND `status` = :status ";

        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
        $stmt->bindValue(':order', $order, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if(!$stmt->execute()){
            return array(
                'status' => "Error",
                'error' => 'Encountered an issue while reading this slide to delete'
            );
        }

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if(count($results) <= 0){
            return array(
                'status' => "Error",
                'error' => 'Slide seem to be deleted already!'
            );
        }

        $slide_id = $results[0];

        $now = gmdate('Y-m-d H:i:s');

        $sql = "DELETE FROM `learner_slide_notes` 
                WHERE `slide_id` = :slide_id 
                    AND `user_id` = :user_id";

        $db = static::getDB();

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':slide_id', $slide_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        if(!$stmt->execute()){
            return array(
                'status' => "Error",
                'error' => 'Encountered an issue while dleting this note! Request you to kindly try again and report if the issue repeats'
            );
        }
        
        return array(
            'status' => "Success"
        );
    }

}
