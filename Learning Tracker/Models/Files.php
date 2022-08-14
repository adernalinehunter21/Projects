<?php

namespace App\Models;

use App\Models\User;
use App\s3;
use PDO;
use \App\Mail;

/**
 * messages model
 *
 * PHP version 7.0
 */
class Files extends \Core\Model {

     public static function removeFileBeingAttached($internal_name, $uploaded_purpose, $user_id ) {

        if (!preg_match('/^ATTACH/', $internal_name)) {
            return array(
                "status" => "Error",
                "error" => "Seem to be not an attachment file"
            );
        }

       $db = static::getDB();

        $now = gmdate('Y-m-d H:i:s');

       $sql = "INSERT INTO `files_to_be_deleted`
       (`id`, `uploaded_purpose`, `internal_file_name`, `user_id`, `time_stamp`, `status`)
         VALUES
           (NULL,
           :uploaded_purpose,
           :internal_file_name,
           :user_id,
           :time_stamp,
           :status
         )";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uploaded_purpose', $uploaded_purpose, PDO::PARAM_STR);
        $stmt->bindValue(':internal_file_name', $internal_name, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':time_stamp', $now, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Encountered an error during creation of new course"
            );
        }
        
      
        return array(
            "status" => "Success",
        );

    }

}
