<?php

namespace App;

use PDO;

// open syslog, include the process ID and also send
// the log to standard error, and use a user defined
// 
// logging mechanism

class EventLoger extends \Core\Model {

    /**
     * Get all the posts as an associative array
     *
     * @return array
     */
    public static function logEvent($event_type, $event_details, $user_id = null) {
        //Get the user id for logging
        if ($user_id === null) {
            if(isset($_SESSION['user_id'])){
                $user_id = $_SESSION['user_id'];
            }else{
                $user_id = 0;
            }
        }
        
        try {

            //this query will get id of event_type from log_event_types table
            $db = static::getDB();
            $stmt = $db->prepare("SELECT `id` FROM `log_event_types` WHERE `event_type` = :event_type");

            $stmt->bindValue(':event_type', $event_type, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (count($result) == 0) {
                $event_type_id = 25; //id of type unknown
            } else {
                $event_type_id = array_pop($result);
            }

            //this query will insert the event_type_id ,user_id, time_stamp, event_details in event_log table
            $stmt = $db->prepare("INSERT INTO `event_log` 
                                    (`id`, `user_id`, `time_stamp`, `event_type_id`, `event_details`)
                                    VALUES (NULL, :user_id, current_timestamp(), :event_type_id, :event_details)");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':event_type_id', $event_type_id, PDO::PARAM_INT);
            $stmt->bindValue(':event_details', $event_details, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                $this->logDbError("event_logging_error", "$event_type, $event_details");
            }
            
        } catch (PDOException $e) {
            $this->logDbError("event_logging_error", "$event_type, $event_details");
        }
        return "";
    }

    public static function logDbError($error_type, $error_details) {
        $fhandle = fopen('dbErrorLog', 'a');
        fwrite($fhandle, "\n $error_type, $error_details");
        fclose($fhandle);
    }

}
