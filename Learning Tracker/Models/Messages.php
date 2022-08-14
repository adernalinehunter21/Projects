<?php

namespace App\Models;

use App\Models\User;
use App\s3;
use PDO;
use \App\Mail;

/**
 * messages model
 *
 * PHP version 5.4
 */
class Messages extends \Core\Model {

    /**
     * Get all the values as an associative array
     *
     * @return array called message details
     */
    public static function getMessageDetails($user_id, $course_id) {
        $message_details = array();
        try {


            //this query will get short_code, role_name, subject, message body from interaction messages  table
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `target_role`,
                                  `short_code`,
                                  `role_name`,
                                  `subject`,
                                  `message_body`,
                                  `sent_date`
                              FROM `interaction_messages`
                                  JOIN `user_roles` ON(`interaction_messages`.`target_role` = `user_roles`.`role`)
                              WHERE `user_id` = :user_id
                                  AND `course_id` = :course_id
                              ORDER BY `interaction_messages`.`id` ASC");

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $message_details = $results;
            return $message_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getFacilitatorMessageDetails($user_id) {
        $message_details = array();
        try {

            //this query will get short_code, role_name, subject, message body from interaction messages  table
            $db = static::getDB();

            $stmt = $db->prepare("SELECT
              `interaction_messages`.`id`,
              `users`.`name`,
              `users`.`last_name`,
              `courses`.`course_name`,
              `subject`,
              `message_body`,
              `sent_date`
              FROM `interaction_messages`
              JOIN `user_roles` ON    (`interaction_messages`.`target_role` = `user_roles`.`role` )
              JOIN `courses` ON (`interaction_messages`.`course_id` = `courses`.`course_id` )
              JOIN `user_to_course_mapping` ON (`courses`.`course_id` = `user_to_course_mapping`.`course_id` )
              JOIN `users` ON ( `users`.`id` = `interaction_messages`.`user_id`)
              WHERE  `interaction_messages`.`target_role` = :user_role
              AND `user_to_course_mapping`.`user_id` = :user_id
              AND `user_to_course_mapping`.`role` =  :user_role
              AND `interaction_messages`.`status` = :status
              AND `user_roles`.`status` = :status
              AND `users`.`status` = :status
              AND `user_to_course_mapping`.`status` = :status
              AND `courses`.`status` = :status
              ORDER BY `interaction_messages`.`id` ASC");

            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_role', 'FACILITATOR', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


            $message_details = $results;
            return $message_details;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getDetailsOfTheMessage($message_id, $user_id) {
        $message_details = array();
        try {
            //this query will get a message , depending upon the user id
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `interaction_messages`.`subject`,
                                        `interaction_messages`.`message_body`,
                                        `interaction_messages`.`sent_date`,
                                        `interaction_messages`.`status`,
                                        `interaction_messages`.`course_id`,
                                        `interaction_messages`.`message_type`,
                                        `interaction_messages`.`target_role`,
                                        `users`.`name`,
                                        `users`.`last_name`,
                                        `users`.`profile_pic_binary`,
                                        `courses`.`course_name`
                                FROM `interaction_messages`
                                JOIN `courses` ON (`interaction_messages`.`course_id` = `courses`.`course_id` )
                                JOIN `users` ON ( `users`.`id` = `interaction_messages`.`user_id`)
                                WHERE `interaction_messages`.`id` = :message_id
                                ORDER BY `interaction_messages`.`id` ASC");

            $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }


/*
Logs new message for given arguments which come from the user .
Used in composing a new message for Learners.
Data logged into message threads table , then all participants of the thread are inserted in
message participants table and lastly , the message is logged into the messages table.
*/

    public static function logNewMessage($to, $subject, $message, $course_id ,$type, $attachments = []) {
        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();


        $users = Courses::getUsersOfTheCourse($course_id);
            $message_participants = array();

            foreach ($to as $receiver) {
              if ($receiver === "all_facilitators" || $receiver === "co_facilitators" ) {
                  if (isset($users['FACILITATOR'])) {
                      foreach ($users['FACILITATOR'] as $facilitator) {
                          $user_id = $facilitator['user_id'];
                          if (!in_array($user_id, $message_participants)) {
                            array_push($message_participants, $user_id);
                          }
                      }
                  }
              }
              elseif ($receiver === "all_learners" ) {
                  if (isset($users['PARTICIPANT'])) {
                      foreach ($users['PARTICIPANT'] as $participant) {
                        $user_id = $participant['user_id'];
                        if (!in_array($user_id, $message_participants)) {
                          array_push($message_participants, $user_id);
                        }
                      }
                  }
              }
              else {
                if (!in_array($receiver, $message_participants)) {
                  array_push($message_participants, $receiver);
                }
              }

            }

            $user_id = $_SESSION['user_id'];
            if (!in_array($user_id, $message_participants)) {
              array_push($message_participants, $user_id);
            }
        $sql = "INSERT INTO `message_threads`
          (`id`, `subject`,`course_id`,`type`,`status`)
          VALUES
            (NULL,
            :subject,
            :course_id,
            :type,
            :status
            )";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Encountered an error during creation of new thread"
            );
        }
        $thread_id = $db->lastInsertId();
        foreach ($message_participants as $participant) {
          // query to insert all participants of the thread into the table
            $sql = "INSERT INTO `message_thread_participant`
            (`id`, `thread_id`,`user_id`,`status`)
            VALUES
              (NULL,
              :thread_id,
              :user_id,
              :status
              )";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $participant, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error during addition of participant"
                );
            }
        }


        $now = gmdate('Y-m-d H:i:s');
        // query to insert message into the table
        $sql = "INSERT INTO `messages`
          (`id`, `thread_id`,`previous_message_id`,`sent_time`,`message_body`,`sender_user_id`,`status`)
          VALUES
            (NULL,
            :thread_id,
            :previous_message_id,
            :sent_time,
            :message_body,
            :sender_user_id,
            :status
            )";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_STR);
        $stmt->bindValue(':previous_message_id', 0, PDO::PARAM_INT);
        $stmt->bindValue(':sent_time', $now, PDO::PARAM_STR);
        $stmt->bindValue(':message_body', $message, PDO::PARAM_STR);
        $stmt->bindValue(':sender_user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollBack();
            return array(
                "status" => "Error",
                "error" => "Encountered an error while sending the message"
            );
        }

        $message_id = $db->lastInsertId();

        if(count($attachments) > 0 ) {
          for ($i=0; $i < sizeof($attachments) ; $i++) {
            $internal_file_name = $attachments[$i]['internalFileName'] ;
            $file_name =  $attachments[$i]['fileName'];
            $file_size = $attachments[$i]['fileSize'];
            $file_type = $attachments[$i]['fileType'];
          $sql = "INSERT INTO `message_attachments`
            (`id`, `message_id`,`internal_file_name`,`file_name`,`file_size`,`file_type`,`status`)
            VALUES
              (NULL,
              :message_id,
              :internal_file_name,
              :file_name,
              :file_size,
              :file_type,
              :status
              )";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
            $stmt->bindValue(':internal_file_name', $internal_file_name, PDO::PARAM_STR);
            $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
            $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
            $stmt->bindValue(':file_type', $file_type, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error during addition of attachment"
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

/*
function to reply to a message
used both in learner and facilitator messages
the thread id is selected with given arguments and the data then is inserted into the
message threads table
*/
    public static function replyMessage($message_id, $message, $user_id ,  $attachments = []) {
        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT `thread_id`
      FROM `messages`
      WHERE `messages`.`id`= :message_id
      AND `messages`.`status` = :status
                                                        ");

        $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Encountered an error during creation of new course"
            );
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $thread_id = $results[0]["thread_id"];
        $now = gmdate('Y-m-d H:i:s');

        $sql = "INSERT INTO `messages`
         (`id`, `thread_id`, `previous_message_id`, `sent_time`, `message_body`, `sender_user_id`, `status`)
         VALUES
           (NULL,
           :thread_id,
           :previous_message_id,
           :sent_time,
           :message_body,
           :sender_user_id,
           :status
         )";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt->bindValue(':previous_message_id', $message_id, PDO::PARAM_INT);
        $stmt->bindValue(':sent_time', $now, PDO::PARAM_STR);
        $stmt->bindValue(':message_body', $message, PDO::PARAM_STR);
        $stmt->bindValue(':sender_user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollBack();
            return array(
                "status" => "Error",
                "error" => "Encountered an error during creation of new course"
            );
        }
        $current_message_id = $db->lastInsertId();

        if(count($attachments) > 0 ) {
          for ($i=0; $i < sizeof($attachments) ; $i++) {
            $internal_file_name = $attachments[$i]['internalFileName'] ;
            $file_name =  $attachments[$i]['fileName'];
            $file_size = $attachments[$i]['fileSize'];
            $file_type = $attachments[$i]['fileType'];
            $sql = "INSERT INTO `message_attachments`
              (`id`, `message_id`,`internal_file_name`,`file_name`,`file_size`,`file_type`,`status`)
                VALUES
                  (NULL,
                  :message_id,
                  :internal_file_name,
                  :file_name,
                  :file_size,
                  :file_type,
                  :status
                  )";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':message_id', $current_message_id, PDO::PARAM_INT);
            $stmt->bindValue(':internal_file_name', $internal_file_name, PDO::PARAM_STR);
            $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
            $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
            $stmt->bindValue(':file_type', $file_type, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $db->rollBack();
                return array(
                    "status" => "Error",
                    "error" => "Encountered an error during addition of attachment"
                );
            }
          }

      }
        $db->commit();
        return array(
            "status" => "Success",
            "message_id" => $current_message_id
        );

    }

    public static function notify($message_id, $message, $attachments= [] ,$message_type) {
      $from_email = static::getCourseOrgNotificationEmail($message_id, $message_type);

        $db = static::getDB();
        $stmt = $db->prepare(" SELECT
                                    `users`.`name`,
                                    `users`.`last_name`,
                                    `users`.`email`,
                                    `users`.`id` as user_id ,
                                    `messages`.`id`,
                                    `message_threads`.`subject`,
                                    `messages`.`message_body`
                                FROM `messages`
                                JOIN `message_threads` ON
                                    (
                                        `message_threads`.`id` = `messages`.`thread_id`
                                    )
                                JOIN `message_thread_participant` ON
                                    (
                                        `message_threads`.`id` = `message_thread_participant`.`thread_id`
                                    )
                                JOIN `users` ON
                                    (
                                        `message_thread_participant`.`user_id` = `users`.`id`
                                    )
                                WHERE `messages`.`id` = :message_id
                                    AND `messages`.`status` = :status
                                    AND `message_threads`.`type`=:message_type
                                    AND `users`.`status` = :status
                                                      ");

        $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
        $stmt->bindValue(':message_type', $message_type, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $user_id =  $_SESSION['user_id'];
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $errors = [];
        foreach ($results as $result) {
          if ($result["user_id"] != $user_id) {
            $name = $result["name"];
            $last_name = $result["last_name"];
            $full_name = $name.$last_name;
            $email = $result["email"];
            $emailSubject = $result["subject"];
            $emailBody = $result["message_body"] ;
            $response = Mail::send(
                    array(
                        "name" => $full_name,
                        "email_id" => $email
                    ),
                    $emailSubject,
                    "",
                    $emailBody,
                    "",
                    [],
                    [],
                    $from_email,
                    $attachments
            );
            if ($response['status'] == "error"){
              array_push($errors, $result);
            }
          }  
      }
      if(count($errors) == 0){
        return array(
            "status" => "Success"
        );
      }
      else{
        return array(
          "status" => "Error",
          "error" => "Error in sending email",
          "failures" => $errors
        );
      }
    }

    private static function getCourseOrgNotificationEmail($message_id, $message_type){

      $db = static::getDB();
      $stmt = $db->prepare(" SELECT `organisation`.`name`,
                                `organisation`.`notification_email_id`
                            FROM `messages`
                              JOIN `message_threads` ON (`messages`.`thread_id` = `message_threads`.`id`)
                              JOIN `courses` ON (`message_threads`.`course_id` = `courses`.`course_id`)
                              JOIN `organisation` ON (`courses`.`org_id` = `organisation`.`id`)
                            WHERE `messages`.`id` = :message_id
                              AND `messages`.`status`= :status
                              AND `message_threads`.`type`= :message_type
                              AND `message_threads`.`status`= :status
                              AND `organisation`.`status`= :status
                              AND `courses`.`status`= :status
                                ");

      $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
      $stmt->bindValue(':message_type', $message_type, PDO::PARAM_STR);
      $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
      $stmt->execute();
      $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if(count($results) > 0){
        $course_org_details = $results[0];
        $email_id = $course_org_details['notification_email_id'];
        $email_id = trim($email_id);
        if($email_id != ""){
          return [
            'name' => $course_org_details['name'],
            'email' =>  $email_id
          ];
        }
      }
      return [];
    }

    public static function getMessages($user_id, $message_type) {
        $db = static::getDB();
        $stmt = $db->prepare(" SELECT
            `thread_id`,
            `subject`,
            (
            SELECT `id`
            FROM  `messages`
            WHERE `thread_id` = `message_threads`.`id`
            ORDER BY  `id`  DESC
            LIMIT 0,
            1) AS message_id,
            (
           SELECT COUNT(`id`)
           FROM  `messages`
           WHERE `thread_id` = `message_threads`.`id`
           ORDER BY  `id`  DESC
               ) AS message_count
        FROM `message_thread_participant`
        JOIN `message_threads` ON (`message_thread_participant`.`thread_id` = `message_threads`.`id`  )
        WHERE `user_id` = :user_id
        AND `message_thread_participant`.`status` = :status
        AND `message_threads`.`type` = :message_type
        AND `message_threads`.`status` = :status
        ORDER BY message_id DESC
                                  ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':message_type', $message_type, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $message_list = array();
        foreach ($results as $result) {
            $stmt = $db->prepare(" SELECT
                            DATE_FORMAT(`messages`.`sent_time`, '%d %b') as sent_time,
                                `message_body`,
                                `messages`.`sender_user_id`AS sender_user_id,
                                 CONCAT(`name`, ' ', `last_name`) AS sender,
                                 `profile_pic_binary`,
                                (SELECT `id` FROM `message_read_status` WHERE `message_id` = `messages`.`id` AND `user_id` = :user_id AND `status` = :status) AS read_status
                              FROM `messages`
                                JOIN `users`  ON (`messages`.`sender_user_id` = `users`.`id`)
                              WHERE `messages`.`id` = :message_id
                                ");

            $stmt->bindValue(':message_id', $result['message_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $threadId = $result['thread_id'];
            $attachments =  Messages::getMessageAttachmentsOfTheThread($threadId);
            $participants = Messages::getUsersOfTheThread($threadId);
            if (count($result2) > 0) {
                array_push(
                        $message_list,
                        array(
                            "thread_id" => $result['thread_id'],
                            "sender_name" => $result2[0]['sender'],
                            "sender_profile_pic" => $result2[0]['profile_pic_binary'],
                            "subject" => $result['subject'],
                            "message_body" => $result2[0]['message_body'],
                            "sent_time" => $result2[0]['sent_time'],
                            "read_status" => $result2[0]['read_status'],
                            "message_id" => $result['message_id'],
                            "sender_user_id" =>$result2[0]['sender_user_id'],
                            "message_count" =>$result['message_count'],
                            "attachments" =>$attachments,
                            "participants" =>$participants
                        )
                );
            }
        }
        return array(
            "status" => "Success",
            "data" => $message_list
        );
    }

    private static function getMessageAttachmentsOfTheThread($thread_id){
      $db = static::getDB();

      $stmt = $db->prepare("SELECT  `message_attachments`.`id`, `file_name`,`message_attachments`.`internal_file_name`
                            FROM `message_attachments`
                              JOIN `messages` ON ( `message_attachments`.`message_id` = `messages`.`id`)
                            WHERE `messages`.`thread_id` = :thread_id
                              AND `message_attachments`.`status` = :status ");
      $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
      $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

      $stmt->execute();
      $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $uploadedAttachments = array();
      foreach ($attachments as $attachment){
          $fileName = $attachment['file_name'];
          $fileInternalName = $attachment['internal_file_name'];
          $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
          array_push($uploadedAttachments, array(
              "file_name" => $fileName,
              "file_path" => $filePath
          ));
      }
      return $uploadedAttachments;
    }

    private static function getMessageAttachments($messageId) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `message_attachments`.`id`, `file_name`, `message_attachments`.`internal_file_name`
                                  FROM`message_attachments`
                                  JOIN `messages` ON( `message_attachments`.`message_id` = `messages`.`id`)
                                  WHERE `messages`.`id` = :message_id AND `message_attachments`.`status` = :status ");
        $stmt->bindValue(':message_id', $messageId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $uploadedAttachments = array();
        foreach ($attachments as $attachment){
            $fileName = $attachment['file_name'];
            $fileInternalName = $attachment['internal_file_name'];
            $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $fileInternalName, $fileName);
            array_push($uploadedAttachments, array(
                "file_name" => $fileName,
                "file_path" => $filePath
            ));
        }
        return $uploadedAttachments;
    }
    public static function getUsersOfTheThread($thread_id){

      $db = static::getDB();
        $stmt = $db->prepare("SELECT  `message_thread_participant`.`user_id`,
                                        CONCAT(`users`.`name`, ' ', `users`.`last_name`) AS participant
                              FROM `users`
                               JOIN `message_thread_participant` ON (`message_thread_participant`.`user_id` = `users`.`id`)
                              WHERE `message_thread_participant`.`thread_id` = :thread_id
                                AND `message_thread_participant`.`status` = :status
                                AND `users`.`status` = :status
                            ");

        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $user_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $user_details;
    }
    public static function getMessagesOfTheThread($user_id, $thread_id, $message_type) {
        $latest_message = static::getTheLatestMessageOfTheThread($user_id, $thread_id, $message_type);
        if ($latest_message['previous_message_id'] != 0) {
            $latest_message['previous_message'] = static::getThePrevoiusMessagesOfTheThread($latest_message['previous_message_id']);
        } else {
            $latest_message['previous_message'] = NULL;
        }

        $normalized_message_array = array();
        do {
            $previous_message = $latest_message['previous_message'];
            unset($latest_message['previous_message']);
            if($latest_message['sender_user_id']===$user_id){
                $latest_message['sender_type'] = "self";
            }
            else{
                $latest_message['sender_type'] = "other";
            }
            array_push($normalized_message_array, $latest_message);
            $latest_message = $previous_message;
        } while (is_array($latest_message));
        return array(
            "status" => "Success",
            "messages" => $normalized_message_array,
            "thread_id" => $normalized_message_array[0]['thread_id'],
            "subject" => $normalized_message_array[0]['subject'],
            "latest_sender" => $normalized_message_array[0]['sender']
        );
    }

    private static function getTheLatestMessageOfTheThread($user_id, $thread_id, $message_type) {

        $db = static::getDB();
        $stmt = $db->prepare(" SELECT
          `messages`.`id` AS message_id,
          `messages`.`previous_message_id`,
          `messages`.`thread_id` as thread_id,
          `messages`.`sender_user_id`,
          DATE_FORMAT(`messages`.`sent_time`, '%d %b %y %H:%i') as sent_time,
          `messages`.`message_body`,
          `messages`.`status`,
          `message_threads`.`subject`,
          CONCAT(`name`, ' ', `last_name`) AS sender,
          `profile_pic_binary`
      FROM `messages`
        JOIN `message_threads` ON (`messages`.`thread_id` = `message_threads`.`id`)
        JOIN `users` ON (`messages`.`sender_user_id` = `users`.`id`)
      WHERE `thread_id` = :thread_id
        AND `message_threads`.`status` = :status
        AND `message_threads`.`type` = :message_type
        AND `messages`.`status` = :status
      ORDER BY `messages`.`id` DESC LIMIT 0,1");

        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt->bindValue(':message_type', $message_type, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $message_id = $results[0]['message_id'];
        $attachments =  Messages::getMessageAttachments($message_id);
        $results[0]['attachments'] = $attachments;
        return $results[0];


    }

    private static function getThePrevoiusMessagesOfTheThread($message_id) {
        $db = static::getDB();
        $stmt = $db->prepare(" SELECT
          `messages`.`id` AS message_id,
          `messages`.`previous_message_id`,
          `messages`.`sender_user_id`,
          DATE_FORMAT(`messages`.`sent_time`, '%d %b %y %H:%i') as sent_time,
          `messages`.`message_body`,
          `messages`.`status`,
          CONCAT(`name`, ' ', `last_name`) AS sender,
          `profile_pic_binary`
      FROM `messages`
        JOIN `users` ON (`messages`.`sender_user_id` = `users`.`id`)
      WHERE `messages`.`id` = :message_id
        AND `messages`.`status` = :status ");

        $stmt->bindValue(':message_id', $message_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $message_details = $results[0];
        $attachments =  Messages::getMessageAttachments($message_id);
        $message_details['attachments'] = $attachments;
        if ($message_details['previous_message_id'] != 0) {
            $message_details['previous_message'] = static::getThePrevoiusMessagesOfTheThread($message_details['previous_message_id']);
        } else {
            $message_details['previous_message'] = NULL;
        }
        return $message_details;
    }


    public static function markThreadAsRead($user_id , $thread_id) {
        $db = static::getDB();
        $stmt = $db->prepare(" SELECT `id`
                    FROM `messages`
                    WHERE `thread_id` = :thread_id
                      AND `sender_user_id` != :user_id
                      AND `status` = :status
                      AND `id` NOT IN (SELECT `message_id`
                          FROM `message_read_status`
                          WHERE `user_id`= :user_id
                          AND `status` = :status) ");

        $stmt->bindValue(':thread_id', $thread_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($results);
        $now = gmdate('Y-m-d H:i:s');
        foreach ($results as $one_message) {

            $sql = "INSERT INTO `message_read_status`
              (`id`, `message_id`, `user_id`, `read_time`,  `status`)
                VALUES
                (NULL,
                :message_id,
                :user_id,
                :read_time,
                :status
              )";

              $stmt = $db->prepare($sql);
              $stmt->bindValue(':message_id', $one_message['id'], PDO::PARAM_INT);
              $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
              $stmt->bindValue(':read_time', $now, PDO::PARAM_STR);
              $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
              $stmt->execute();
            }
            $_SESSION['unread_messages'] = $_SESSION['unread_messages'] - $count;
    }

    public static function unreadMessages($user_id ) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT COUNT(`messages`.`id`) AS unread_messages
                                FROM `messages`
                                JOIN `message_thread_participant`
                                ON (`messages`.`thread_id` = `message_thread_participant`.`thread_id`)
                                WHERE `messages`.`status` = :status
                                AND `messages`.`sender_user_id` != :user_id
                                AND `message_thread_participant`.`user_id` = :user_id
                                AND `message_thread_participant`.`status` = :status
                                AND `messages`.`id` NOT IN
                                (SELECT `message_id`
                                  FROM `message_read_status`
                                  WHERE `user_id`= :user_id
                                    AND `status` = :status)
                              ");

        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = $results[0];
        return $count;

    }

}
