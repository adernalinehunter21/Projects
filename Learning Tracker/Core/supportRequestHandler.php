<?php

use \App\Mail;
use \Core\View;
use \App\EventLoger;

class supportRequestHandler extends UpdateHandlers {

    /**
     * Log the support request into db and send notification email to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return array containing status and message
     */
    public function handleRequest($data) {

        $data['user_id'] = $_SESSION['user_id'];
        $data['course_id'] = $_SESSION['course_id'];
        $target_roles = array(
            "Facilitators" => "FACILITATOR",
            "Operational Support Team" => "OPERATIONAL_SUPPORT",
            "Technical Support Team" => "TECHNICAL_SUPPORT",
            "DASA Team" => "DASA_TEAM"
        );
        $data['target_role'] = $target_roles[trim($data['target_role'])];

        //Log the request and return error if there is any issue
        if (!$this->logRequest($data)) {
            return array(
                "status" => "Error",
                "message" => "Issue in updating database"
            );
        }

        //Send email and return error if there is any issue
        if (!$this->notify($data)) {
            return array(
                "status" => "Error",
                "message" => "Issue in sending notification email but request is logged successfully"
            );
        }

        return array(
            "status" => "Success",
            "message" => ""
        );
    }

    /**
     * Insert support request data received from client into table interaction_messages
     * @param type $data is an array containing target_role, subject & message
     * @return boolean. True if successful and False otherwise
     */
    private function logRequest($data) {
        $user_id = $data['user_id'];
        $course_id = $data['course_id'];
        $target_role = $data['target_role'];
        $subject = $data['subject'];
        $message = $data['message'];
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        try {
            $db = static::getDB();
            $result = $db->exec("INSERT INTO `interaction_messages` "
                    . "(`user_id`, `course_id`, `message_type`,`previous_message_id`, `target_role`, `subject`, `message_body`, `sent_date`, `sent_time`, `status`) "
                    . "VALUES ('$user_id', '$course_id', 'NEW', 0, '$target_role', '$subject', '$message', '$today', '$now', 'ACTIVE')");

            if (!$result) {
                return false;
            }

            //eventlogging for Support Request Submission
            $logDetails = array(
                "receipient_type" => $target_role,
                "subject" => $subject,
                "message" => $message
            );
            EventLoger::logEvent('Submit support request', json_encode($logDetails));

            return true;
        } catch (PDOException $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * Send notification message to users of target_role
     * @param type $data is an array containing target_role, subject & message
     * @return boolean. True if successful and False otherwise
     */
    private function notify($data) {
        $course_id = $data['course_id'];
        $target_role = $data['target_role'];
        $subject = $data['subject'];
        $message = $data['message'];
        $user_id = $data['user_id'];

        $db = static::getDB();

        //Get the Name and email id of the message sender
        $stmt = $db->prepare("SELECT `name`, `email` FROM `users` WHERE `id` = :user_id ");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userDetails = array_pop($result);
        $senderName = $userDetails['name'];
        $senderEmailId = $userDetails['email'];

        //Compose email body
        $text = View::getTemplate('SupportRequest/notification_email.txt', ["sender_name" => $senderName, "message" => $message]);
        $html = View::getTemplate('SupportRequest/notification_email.html', ["sender_name" => $senderName, "message" => $message]);

        //Get the email IDs of users to be notified
        $emailIDetails = $this->getEmailDetailss($target_role, $course_id);

        //Send notification email to each of the user to be notified
        foreach ($emailIDetails as $oneEmailIDetails) {
            Mail::send($oneEmailIDetails['email'], $subject, $text, $html, $senderEmailId);
        }

        return true;
    }

    private function getEmailDetailss($role, $courseId) {
        $db = static::getDB();
        if ($role != "TECHNICAL_SUPPORT") {
            $stmt = $db->prepare("SELECT   `id`, `name`, `last_name`, `email`
                    FROM `user_to_course_mapping`
                        JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                    WHERE `course_id` = :course_id
                        AND `user_to_course_mapping`.`role` = :role
                        AND `user_to_course_mapping`.`status` = :status
                        AND `users`.`status` = :status
                    ");
            $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindValue(':role', $role, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        } else {
            $stmt = $db->prepare("SELECT `id`, `name`, `last_name`, `email`
                                FROM `users`
                                WHERE `role` = :role
                                    AND `status` = :status "
            );
            $stmt->bindValue(':role', $role, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
