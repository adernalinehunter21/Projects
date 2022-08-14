<?php

namespace App\Models;

use PDO;
use App\Models\Sessions;
use \App\Models\Subjects;


/**
 * modules model
 *
 * PHP version 5.4
 */
class CourseConfigurations extends \Core\Model {

    /**
     * Get the list of Alerts supported
     */
    public static function getSupportedAlerts() {
        return array(
            [
                "id" => 1,
                "alert_service" => "alertAboutUpcomingSession"
            ]
        );
    }

    public static function getSupportedNotifications() {
        $notifications_classes = array('notificationOfSessionCreation','notificationOnAssignmentCreation');

        $supportedNotificationsArray = array();
        foreach($notifications_classes as $notifications_class){
            $db = static::getDB();
            $stmt = $db->prepare("SELECT `id`
                                FROM `micro_services`
                                WHERE `class` = :class
                                    AND `status` = :status ");
            $stmt->bindValue(':class', $notifications_class, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
            $stmt->execute();
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if($notifications_class === "notificationOfSessionCreation"){
                array_push($supportedNotificationsArray,[
                    'id'=>(int)$alerts[0]['id'],    //typeCasting from string to int
                    'notification_service'=> "Notification on Session Creation"

                ]);
            }

            elseif($notifications_class === "notificationOnAssignmentCreation"){
                array_push($supportedNotificationsArray,[
                    'id'=>(int)$alerts[0]['id'],     //typeCasting from string to int
                    'notification_service'=> "Notification on Assignment Creation"

                ]);
            }
        }
        return $supportedNotificationsArray;

    }

    public static function getVariablesOfAlert($course_id, $alert_id) {
        $configurationSchema = static::getConfigurationSchema($alert_id);
        if($configurationSchema === null || $configurationSchema === ""){
            return [
                'status' => 'Error',
                'error' => 'Alert service seem to be disabled'
            ];
        }

        $upcoming_session_details = static::getTheDetailsOfTheUpcomingSession($course_id);
        if(count($upcoming_session_details) == 0){
            return [
                'status' => 'Error',
                'error' => 'There seem to be no more upcoming sessions in the course'
            ];
        }

        $subject_id = $upcoming_session_details[0]['subject_id'];
        $subjectDetails = static::getTheSubjectDetails($subject_id);
        $session_id = $upcoming_session_details[0]['session_id'];
        $topics = static::getTopicsOfTheSession($session_id);
        $upcoming_session_details[0]['session_topic_list'] = $topics;

        $oneLearner = static::getOneLearnersOfTheCourse($course_id);

        if(count($oneLearner) === 0){
            return [
                'status' => 'Error',
                'error' => 'There seem to be no learners added to this course yet'
            ];
        }

        $facilitators = static::getFacilitatorsOfTheCourse($course_id);

        $notificationEmailOfCourseOrg = static::getNotificationEmailOfCourseOrg($course_id);

        $data = array_merge($upcoming_session_details[0], $subjectDetails[0], $oneLearner[0]);
        $data['facilitators'] = $facilitators;

        $schema_with_data = [];
        $schema = json_decode($configurationSchema, true);
        foreach ($schema as $key => $value) {
            $source = $value['source'];
            if(isset($data[$source])){
                $example_value = $data[$source];
                switch ($value['type']){
                    case 'String':
                    case 'Link':
                    case 'List':
                        array_push($schema_with_data, [
                            "type" => $value['type'],
                            "source" => $source,
                            "name" => $value['name'],
                            "example_value" => $example_value
                        ]);
                        break;

                    case 'Logo':
                        $tooltip = "";
                        $tooltip_source = $value['tooltip'];
                        if(isset($data[$tooltip_source])){
                            $tooltip = $data[$tooltip_source];
                        }

                        array_push($schema_with_data, [
                            "type" => "Logo",
                            "source" => $source,
                            "name" => $value['name'],
                            "tooltip_source" => $tooltip_source,
                            "tooltip" => $tooltip,
                            "example_value" => $example_value
                        ]);
                        break;

                    case 'DateTime':

                        $original_time_in_utc = $example_value;
                        if(isset($value['timezone'])){
                            $timezone_parameter = $value['timezone'];
                            if(isset($data[$timezone_parameter])){
                                $timezone = $data[$timezone_parameter];

                                $date_time_value = self::utcToUserTimezone($original_time_in_utc, $timezone);
                            }
                            else{
                                $timezone = 'UTC';
                                $date_time_value = date('Y-m-d H:i:s', strtotime($original_time_in_utc));
                            }
                        }
                        else{
                            $timezone = 'UTC';
                            $date_time_value = date('Y-m-d H:i:s', strtotime($original_time_in_utc));
                        }
                        $formatsWithExampleValues = [];
                        foreach ($value['formats'] as $format){
                            array_push($formatsWithExampleValues, [
                                "format" => $format,
                                "example_value" => date($format, strtotime($date_time_value))
                            ]);
                        }
                        array_push($schema_with_data, [
                            "type" => "DateTime",
                            "source" => $source,
                            "timezone" => $timezone,
                            "name" => $value['name'],
                            "formats" => $formatsWithExampleValues
                        ]);
                        break;
                }
            }

        }
        return [
            'status' => 'Success',
            'data' => $schema_with_data
        ];


    }


    public static function getVariablesOfNotification($course_id, $notification_id) {
        $configurationSchema = static::getConfigurationSchema($notification_id);
        if($configurationSchema === null || $configurationSchema === ""){
            return [
                'status' => 'Error',
                'error' => 'Notification service seems to be disabled'
            ];
        }
        $calendar_example = array(
                             array(
                                  "calendar" => "Google",
                                  "google_link" =>""

                             ),

                             array(
                                  "calendar" => "Yahoo",
                                  "yahoo_link" => ""
                             ),

                             array(
                                 "calendar" => "Outlook",
                                 "outlook_link" => ""
                             )
                        );



        $upcoming_session_details = static::getTheDetailsOfTheUpcomingSession($course_id);
        if(count($upcoming_session_details) == 0){
            $data = [];
            $course_details = static::getDetailsOfTheCourse($course_id);
            $course_name =  $course_details[0]['course_name'];
            $course_org_logo = $course_details[0]['course_org_logo'];
            $data["session_name"] = "Introduction";
            $data["start_timestamp"] = date('Y-m-d H:i:s');
            $data['duration'] = "02:00:00";
            $data["session_index"] = "1";
            $data["meeting_link"] = "https://zoom.com";
            $topics = array( "topic 1", "topic 2" );
            $data['session_topic_list'] = $topics;
            $data['calendar_links'] = $calendar_example;
            $data ['course_org_logo'] = $course_org_logo;
            $data['course_name'] = $course_name;

        }else{
            $subject_id = $upcoming_session_details[0]['subject_id'];
            $subjectDetails = static::getTheSubjectDetails($subject_id);
            $session_id = $upcoming_session_details[0]['session_id'];
            $topics = static::getTopicsOfTheSession($session_id);
            $upcoming_session_details[0]['session_topic_list'] = $topics;
            $facilitators = static::getFacilitatorsOfTheCourse($course_id);
            $notificationEmailOfCourseOrg = static::getNotificationEmailOfCourseOrg($course_id);
            $data = array_merge($upcoming_session_details[0], $subjectDetails[0]);
            $data['calendar_links'] = $calendar_example;
            $data['assignment_name'] = "Read Book";
            $data['assignment_description'] = "Read the handbook and submit the review";
        }




        $schema_with_data = [];
        $schema = json_decode($configurationSchema, true);
        foreach ($schema as $key => $value) {
            $source = $value['source'];
            if(isset($data[$source])){
                $example_value = $data[$source];
                switch ($value['type']){
                    case 'String':
                    case 'Link':
                    case 'List':
                        array_push($schema_with_data, [
                            "type" => $value['type'],
                            "source" => $source,
                            "name" => $value['name'],
                            "example_value" => $example_value
                        ]);
                        break;

                    case 'Logo':
                        $tooltip = "";
                        $tooltip_source = $value['tooltip'];
                        if(isset($data[$tooltip_source])){
                            $tooltip = $data[$tooltip_source];
                        }

                        array_push($schema_with_data, [
                            "type" => "Logo",
                            "source" => $source,
                            "name" => $value['name'],
                            "tooltip_source" => $tooltip_source,
                            "tooltip" => $tooltip,
                            "example_value" => $example_value
                        ]);
                        break;

                    case 'DateTime':

                        $original_time_in_utc = $example_value;
                        if(isset($value['timezone'])){
                            $timezone_parameter = $value['timezone'];
                            if(isset($data[$timezone_parameter])){
                                $timezone = $data[$timezone_parameter];

                                $date_time_value = self::utcToUserTimezone($original_time_in_utc, $timezone);
                            }
                            else{
                                $timezone = 'UTC';
                                $date_time_value = date('Y-m-d H:i:s', strtotime($original_time_in_utc));
                            }
                        }
                        else{
                            $timezone = 'UTC';
                            $date_time_value = date('Y-m-d H:i:s', strtotime($original_time_in_utc));
                        }
                        $formatsWithExampleValues = [];
                        foreach ($value['formats'] as $format){
                            array_push($formatsWithExampleValues, [
                                "format" => $format,
                                "example_value" => date($format, strtotime($date_time_value))
                            ]);
                        }
                        array_push($schema_with_data, [
                            "type" => "DateTime",
                            "source" => $source,
                            "timezone" => $timezone,
                            "name" => $value['name'],
                            "formats" => $formatsWithExampleValues
                        ]);
                        break;

                    case 'Calendar Link Table':
                        array_push($schema_with_data, [
                            "type" => "Calendar Link Table",
                            "source" => $source,
                            "name" => $value['name'],
                            "example_value" => $example_value,

                        ]);
                        break;
                }
            }

        }
        return [
            'status' => 'Success',
            'data' => $schema_with_data
        ];


    }

    /*
     * Serve the ajax request to save the new alert
     */
    public static function addNewAlert($course_id, $alert_id, $time, $subject, $template) {
        $user_id = $_SESSION['user_id'];
        $current_time = gmdate('Y-m-d H:i:s');
        $db = static::getDB();

        $db->beginTransaction();
        $query = "INSERT INTO `alert_configurations`
                    (`id`, `config_group_id`, `course_id`, `user_id`, `timestamp`, `status`)
                VALUES(
                    null,
                    :config_group_id,
                    :course_id,
                    :user_id,
                    :timestamp,
                    :status
                ) ";
        $stmt = $db->prepare($query);
        //Configuration group id is hardcoded, it shall be made variable on addition of another alert service
        $stmt->bindValue(':config_group_id', 1, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':timestamp', $current_time, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 1 of saving the alert'
            ];
        }
        $alert_config_id = $db->lastInsertId();

        $query = "INSERT INTO `course_configurations`
                        (`id`, `course_id`, `group_id`, `reference_id`, `parameter`, `value`, `status`)
                    VALUES(
                        null,
                        :course_id,
                        1,
                        :reference_id,
                        :parameter,
                        :value,
                        :status
                    ) ";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_config_id, PDO::PARAM_INT);
        $stmt->bindValue(':parameter', "time_before_session_schedule", PDO::PARAM_STR);
        $stmt->bindValue(':value', $time, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 2 of saving the alert'
            ];
        }

        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_config_id, PDO::PARAM_INT);
        $stmt->bindValue(':parameter', "subject", PDO::PARAM_STR);
        $stmt->bindValue(':value', $subject, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 3 of saving the alert'
            ];
        }

        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_config_id, PDO::PARAM_INT);
        $stmt->bindValue(':parameter', "message_body", PDO::PARAM_STR);
        $stmt->bindValue(':value', $template, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 4 of saving the alert'
            ];
        }
        $db->commit();
        return [
            'status' => 'Success',
            'alert_config_id' => $alert_config_id
        ];

    }

    public static function addNewNotification($course_id, $alert_id, $subject, $template, $group_id) {

        $user_id = $_SESSION['user_id'];
        $current_time = gmdate('Y-m-d H:i:s');
        $db = static::getDB();

        $db->beginTransaction();
        $query = "INSERT INTO `alert_configurations`
                    (`id`, `config_group_id`, `course_id`, `user_id`, `timestamp`, `status`)
                VALUES(
                    null,
                    :config_group_id,
                    :course_id,
                    :user_id,
                    :timestamp,
                    :status
                ) ";
        $stmt = $db->prepare($query);

        $stmt->bindValue(':config_group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':timestamp', $current_time, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 1 of saving the notification'
            ];
        }
        $alert_config_id = $db->lastInsertId();

        $query = "INSERT INTO `course_configurations`
                        (`id`, `course_id`, `group_id`, `reference_id`, `parameter`, `value`, `status`)
                    VALUES(
                        null,
                        :course_id,
                        :group_id,
                        :reference_id,
                        :parameter,
                        :value,
                        :status
                    ) ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_config_id, PDO::PARAM_INT);
        $stmt->bindValue(':parameter', "subject", PDO::PARAM_STR);
        $stmt->bindValue(':value', $subject, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 2 of saving the notification'
            ];
        }

        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_config_id, PDO::PARAM_INT);
        $stmt->bindValue(':parameter', "message_body", PDO::PARAM_STR);
        $stmt->bindValue(':value', $template, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        if(!$stmt->execute()){
            $db->rollBack();
            return [
                'status' => 'Error',
                'error' => 'Error at stage 3 of saving the notification'
            ];
        }
        $db->commit();
        return [
            'status' => 'Success',
            'alert_config_id' => $alert_config_id
        ];

    }


    public static function addAlertEventsForFutureSessions($course_id, $advance_time_for_alerting, $alert_config_id) {
        $sessions_response = Sessions::getSessionSchedules($course_id);
        if($sessions_response['status'] !== "Success"){
            return [
                'status' => 'Error',
                'error' => 'error while getting the sessions of the course'
            ];
        }
        $sessions = $sessions_response['data'];

        $current_time = gmdate('Y-m-d H:i:s');
        $event_names = [];
        foreach ($sessions as $session){
            $session_scheduled_time = date('Y-m-d H:i:s', strtotime($session['start_timestamp']));
            $alert_schedule = static::getTimeOffset($session_scheduled_time, $advance_time_for_alerting);
            if($alert_schedule > $current_time){
                //alert time is in the future, we need to create trigger event
                $session_id = $session['session_id'];
                $event_creation_response = static::createAlertEvent($alert_schedule, $course_id, $session_id, $alert_config_id);
                if($event_creation_response['status'] === "Success"){
                    array_push($event_names, $event_creation_response['event_name']);
                }
                else{
                    static::dropAlertEvents($event_names);
                    return $event_creation_response;
                }
            }
        }
        return [
            'status' => 'Success'
        ];
    }

    public static function removeAlertConfigurations($alert_config_id) {
        $db = static::getDB();

        $stmt = $db->prepare("UPDATE `alert_configurations`
                            SET `status` = 'INACTIVE'
                            WHERE `id` = :alert_conf_id ");
        $stmt->bindValue(':alert_conf_id', $alert_config_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public static function getAlertConfigurations($course_id) {
        $db = static::getDB();

        //Get active events
        $stmt = $db->prepare("SELECT * FROM information_schema.EVENTS ");
        $stmt->execute();
        $active_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Get alert configurations
        $stmt = $db->prepare("SELECT `id`, `user_id`, `timestamp`
                            FROM `alert_configurations`
                            WHERE `course_id` = :course_id
                                AND `config_group_id` = 1
                                AND `status` = :status ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $user_timezone = $_SESSION['user_timezone_configured'];
        $data = [];
        if(count($alerts) > 0){
            $session_alerts = [];
            foreach ($alerts as $alert){
                $configurations = static::configurationsOfAnAlert($alert['id']);
                if($configurations['status'] === "Error"){
                    continue;
                }
                $configuration_data = $configurations['data'];

                array_push($session_alerts, [
                    'id' => $alert['id'],
                    'creation_time' => static::utcToUserTimezone($alert['timestamp']),
                    'time_before_session_schedule' => $configuration_data['time_before_session_schedule'],
                    'subject' => $configuration_data['subject'],
                    'message_body' => $configuration_data['message_body']
                ]);
            }
            if(count($session_alerts) > 0){
                array_push($data,[
                    'id' => 1,
                    'name' => "Alerts about Upcoming Sessions",
                    'data' => $session_alerts
                ]);
            }
        }
        return [
            'status' => 'Success',
            'data' => $data
        ];
    }


 public static function getNotificationConfigurations($course_id) {
    $db = static::getDB();

    $configuration_groups = array('Notification on Session Creation','Notification on Assignment Creation');

    $notifications = array();
    foreach($configuration_groups as $configuration_group){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `id`
                            FROM `course_configuration_groups`
                            WHERE `configuration_group` = :configuration_group
                                AND `status` = :status ");
        $stmt->bindValue(':configuration_group', $configuration_group, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $group_id = (int)$result[0]['id'];


        $stmt = $db->prepare("SELECT `id`, `user_id`, `timestamp`,`config_group_id`AS group_id
                            FROM `alert_configurations`
                            WHERE `course_id` = :course_id
                                AND `config_group_id` = :group_id
                                AND `status` = :status ");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $notification = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $notifications = array_merge($notifications,$notification);

    }

    $data = [];
    $session_notifications = [];
    if(count($notifications) > 0){

        foreach ($notifications as $notification){
            $configurations = static::configurationOfNotification($notification['id'], $notification['group_id']);

            if($configurations['status'] === "Error"){
                continue;
            }
           $configuration_data = $configurations['data'];
           $group_id = $configurations['data']['group_id'];


           $stmt = $db->prepare("SELECT `configuration_group` AS name
                               FROM `course_configuration_groups`
                               WHERE `id` = :group_id
                                   AND `status` = :status ");
           $stmt->bindValue(':group_id', $group_id, PDO::PARAM_INT);
           $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
           $stmt->execute();
           $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

           $name = $result[0]['name'];

            array_push($session_notifications, [
                'id' => $notification['id'],
                'creation_time' => static::utcToUserTimezone($notification['timestamp']),
                'subject' => $configuration_data['subject'],
                'message_body' => $configuration_data['message_body'],
                'name' => $name ,
            ]);
        }
            array_push($data,[
                'data' => $session_notifications
            ]);
    }
    return [
        'status' => 'Success',
        'data' => $session_notifications
    ];
}

    public static function getGroupId($notification_id){
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `id`
                            FROM `course_configuration_groups`
                            WHERE `handler_id` = :handler_id
                                AND `status` = :status ");
        $stmt->bindValue(':handler_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result[0]['id'];
    }

    public static function isAlertMappedToCourse($alert_id, $course_id) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `course_id`
                            FROM `alert_configurations`
                            WHERE `id` = :alert_id
                                AND `status` = :status ");
        $stmt->bindValue(':alert_id', $alert_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        if(!$stmt->execute() || $stmt->rowCount() === 0){
            return false;
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result[0]['course_id'] == $course_id){
            return true;
        }
        return false;
    }

    public static function deleteNotificationConfiguration($reference_id) {
        $db = static::getDB();
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE `alert_configurations`
                                SET `status` = :status
                            WHERE `id` = :alert_id ");

        $stmt->bindValue(':alert_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return array(
                "status" => "Error",
                "error" => "Error Encountered in deleting configuration"
            );
        }
        $stmt = $db->prepare("UPDATE `course_configurations`
                                SET `status` = :status
                            WHERE `reference_id` = :alert_id");

        $stmt->bindValue(':alert_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);
        if (!$stmt->execute()) {
            $db->rollBack();
            return array(
                "status" => "Error",
                "error" => "Error Encountered in deleting configuration"
            );
        }

        $db->commit();
        return array(
            "status" => "Success"
        );

    }

    public static function deleteAlertConfiguration($alert_id) {
        $db = static::getDB();
        $stmt = $db->prepare("UPDATE `alert_configurations`
                                SET `status` = :status
                            WHERE `id` = :alert_id ");
        $stmt->bindValue(':alert_id', $alert_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'INACTIVE', PDO::PARAM_STR);
        if($stmt->execute()){
            return [
                'status' => 'Success'
            ];
        }
        else{
            return [
                'status' => "Error",
                'error' => 'Encountered an error while diabling the Alert Configuration'
            ];
        }
    }

    private static function configurationsOfAnAlert($alert_id) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `parameter`, `value`
                            FROM `course_configurations`
                            WHERE `group_id` = :group_id
                                AND `reference_id` = :reference_id
                                AND `status` = :status ");
        $stmt->bindValue(':group_id', 1, PDO::PARAM_INT);
        $stmt->bindValue(':reference_id', $alert_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $config_array = [];
        foreach ($configurations as $configuration){
            $param = $configuration['parameter'];
            $value = $configuration['value'];
            $config_array[$param] = $value;
        }

        if(!isset($config_array['time_before_session_schedule'])
            || !isset($config_array['subject'])
            || !isset($config_array['message_body'])){

            return [
                'status' => 'Error',
                'error' => 'no configurations found'
            ];
        }
        return [
            'status' => 'Success',
            'data' => $config_array
        ];
    }

    private static function configurationOfNotification($notification_id, $group_id) {
        $db = static::getDB();
        $stmt = $db->prepare("SELECT `parameter`, `value`, `group_id`
                            FROM `course_configurations`
                            WHERE `reference_id` = :notification_id
                                AND `group_id` = :group_id
                                AND `status` = :status ");
        $stmt->bindValue(':notification_id', $notification_id, PDO::PARAM_INT);
        $stmt->bindValue(':group_id', $group_id, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);
        $stmt->execute();
        $configurations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $config_array = [];
        foreach ($configurations as $configuration){
            $param = $configuration['parameter'];
            $value = $configuration['value'];
            $group_id = $configuration['group_id'];
            $config_array[$param] = $value;
            $config_array['group_id'] = $group_id;

        }

        if( (!isset($config_array['subject']))
            || (!isset($config_array['message_body']))){
            return [
                'status' => 'Error',
                'error' => 'no configurations found'
            ];
        }
        return [
            'status' => 'Success',
            'data' => $config_array
        ];
    }


    private static function utcToUserTimezone($time_in_utc, $timezone = null) {

        if($timezone === null){
            $timezone = $_SESSION['user_timezone_configured'];
        }
        $utcTimezone = new \DateTimeZone( 'UTC' );
        $time = new \DateTime( $time_in_utc, $utcTimezone );
        $userTimezone = new \DateTimeZone( $timezone );
        $time->setTimeZone( $userTimezone );
        $date_time_value = $time->format( 'Y-m-d H:i:s' );

        return $date_time_value;
    }

    /**
     * For the given time of schedule and the duration ahead of which we need to generate alert
     * @param type $date_time : Time of schedule "Y-m-d H:i:s"
     * @param type $duration : How many days, hours & minutes ahead of schecle we need to alert "d H:i"
     */
    private static function getTimeOffset($date_time, $duration) {
        $first_split = explode(" ", $duration);
        $no_of_days = (int)$first_split[0];

        $second_split = explode(":", $first_split[1]);
        $no_of_hours = (int)$second_split[0];
        $no_of_minutes = (int)$second_split[1];

        if($no_of_days > 0){
            $date_time = date('Y-m-d H:i:s', strtotime("-$no_of_days days", strtotime($date_time)));
        }

        if($no_of_hours > 0){
            $date_time = date('Y-m-d H:i:s', strtotime("-$no_of_hours hours", strtotime($date_time)));
        }

        if($no_of_minutes > 0){
            $date_time = date('Y-m-d H:i:s', strtotime("-$no_of_minutes minutes", strtotime($date_time)));
        }
        return $date_time;
    }

    private static function createAlertEvent($date_time, $course_id, $session_id, $alert_config_id) {
        $db = static::getDB();
        $event_name = "schedule_alert_".$alert_config_id.$session_id;
        $stmt = $db->prepare("CREATE EVENT $event_name
                            ON SCHEDULE AT :alert_schedule
                            ON COMPLETION NOT PRESERVE
                            ENABLE
                            DO BEGIN
                                SET @p0=:course_id;
                                SET @p1=:session_id;
                                SET @p2=:alert_config_id;
                                CALL `generate_session_start_notification_event`(@p0, @p1, @p2);
                            END ");

        $stmt->bindValue(':alert_schedule', $date_time, PDO::PARAM_STR);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_STR);
        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_STR);
        $stmt->bindValue(':alert_config_id', $alert_config_id, PDO::PARAM_STR);

        if($stmt->execute() === false){
            return [
                'status' => 'Error',
                'error' => 'Error while scheduling the alert event'
            ];
        }
        return [
            'status' => 'Success',
            'event_name' => $event_name
        ];
    }

    private static function dropAlertEvents($event_names) {
        $db = static::getDB();

        $stmt = $db->prepare("DROP TRIGGER IF EXISTS :event_name ");
        foreach ($event_names as $event_name){
            $stmt->bindValue(':event_name', $event_name, PDO::PARAM_STR);
            if(!$stmt->execute()){
                return false;
            }
        }
        return true;
    }

    private static function getTheDetailsOfTheUpcomingSession($course_id) {
        $db = static::getDB();

        $now_gmt = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT `courses`.`course_id`,
                                `course_name`,
                                `course_sessions`.`session_id`,
                                `session_index`,
                                `session_name`,
                                `start_timestamp`,
                                `duration`,
                                `meeting_link`,
                                `organisation`.`name` AS course_org_name,
                                `organisation`.`short_name` AS course_org_short_name,
                                `organisation`.`website_link` AS course_org_website,
                                `organisation`.`logo_link` AS course_org_logo,
                                `organisation`.`short_logo_link` as course_org_short_logo,
                                `organisation`.`custom_domain`,
                                `subject_id`
                            FROM `courses`
                                JOIN `course_sessions` ON (`courses`.`course_id` = `course_sessions`.`course_id`)
                                JOIN `course_session_schedules` ON (`course_sessions`.`session_id` = `course_session_schedules`.`session_id`)
                                JOIN `organisation` ON (`courses`.`org_id` = `organisation`.`id`)
                            WHERE `courses`.`course_id` = :course_id
                                AND `course_session_schedules`.`start_timestamp` > :time
                                AND `courses`.`status` = :status
                                AND `course_sessions`.`status` = :status
                                AND `course_session_schedules`.`status` = :status
                            ORDER BY `course_session_schedules`.`start_timestamp` ASC
                            LIMIT 0, 1 ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':time', $now_gmt, PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    private static function getDetailsOfTheCourse($course_id) {
        $db = static::getDB();

        $now_gmt = gmdate('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT `courses`.`course_id`,
                                `course_name`,
                                `organisation`.`name` AS course_org_name,
                                `organisation`.`short_name` AS course_org_short_name,
                                `organisation`.`website_link` AS course_org_website,
                                `organisation`.`logo_link` AS course_org_logo,
                                `organisation`.`short_logo_link` as course_org_short_logo,
                                `organisation`.`custom_domain`,
                                `subject_id`
                            FROM `courses`
                                JOIN `organisation` ON (`courses`.`org_id` = `organisation`.`id`)
                            WHERE `courses`.`course_id` = :course_id
                                AND `courses`.`status` = :status
                            ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    private static function getTheSubjectDetails($subject_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `subject`,
                                `logo` AS subject_org_logo,
                                `organisation`.`name` AS subject_org_name,
                                `organisation`.`website_link` AS subject_org_website
                            FROM `subjects`
                                JOIN `organisation` ON (`subjects`.`content_org_id` = `organisation`.`id`)
                            WHERE `subjects`.`id` = :subject_id
                                ORDER BY `subjects`.`id` ASC
                            LIMIT 0,1 ");

        $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    private static function getTopicsOfTheSession($session_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT
                                CONCAT(
                                    COALESCE(`course_session_to_topic_mapping`.`general_topic`, ''),
                                    COALESCE(`subject_topics`.`name`, '')
                                ) AS topic
                            FROM
                                `course_session_to_topic_mapping`
                                    LEFT JOIN `subject_topics` ON(
                                    `course_session_to_topic_mapping`.`topic_type` = 'SUBJECT_TOPIC'
                                    AND `course_session_to_topic_mapping`.`topic_id` = `subject_topics`.`id`
                                    AND `subject_topics`.`status` = :status
                                )
                            WHERE
                                `course_session_to_topic_mapping`.`session_id` = :session_id
                                AND `course_session_to_topic_mapping`.`status` = :status
                            ORDER BY `course_session_to_topic_mapping`.`order` ASC ");

        $stmt->bindValue(':session_id', $session_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }

    /**
     * Get the list of facilitators of the given course along with their email ID
     * @param type $course_id
     */
    private static function getFacilitatorsOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `users`.`id`,
                                `name` AS first_name,
                                `last_name`,
                                CONCAT(`name`, ' ',`last_name`) AS name,
                                `email` AS email_id
                            FROM `user_to_course_mapping`
                                JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                            WHERE `user_to_course_mapping`.`course_id` = :course_id
                                AND `user_to_course_mapping`.`role` = :role
                                AND `user_to_course_mapping`.`status` = :status
                                AND `users`.`status` = :status
                            ORDER BY `mapping_id` ASC");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'FACILITATOR', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Get the details of one learner of the given course along with their email ID and Timezone info
     * @param type $course_id
     */
    private static function getOneLearnersOfTheCourse($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `users`.`id` AS learner_id,
                                `name` AS learner_first_name,
                                `last_name` AS learner_last_name,
                                CONCAT(`name`, ' ',`last_name`) AS learner_full_name,
                                `email` AS learner_email_id,
                                `timezone` AS learner_timezone
                            FROM `user_to_course_mapping`
                                JOIN `users` ON (`user_to_course_mapping`.`user_id` = `users`.`id`)
                            WHERE `user_to_course_mapping`.`course_id` = :course_id
                                AND `user_to_course_mapping`.`role` = :role
                                AND `user_to_course_mapping`.`status` = :status
                                AND `users`.`status` = :status
                            ORDER BY `mapping_id` ASC
                            LIMIT 0,1 ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', 'PARTICIPANT', PDO::PARAM_STR);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /*
     * Get the notification email ID of the org
     */
    private static function getNotificationEmailOfCourseOrg($course_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `organisation`.`name`,
                                `notification_email_id` AS email_id
                            FROM `courses`
                                JOIN `organisation` ON (`courses`.`org_id` = `organisation`.`id`)
                            WHERE `courses`.`course_id` = :course_id
                                AND `courses`.`status` = :status
                                AND `organisation`.`status` = :status ");

        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            $result = array_pop($result);
            $email_id = trim($result['email_id']);
            if ($email_id !== "") {
                return $result;
            }
        }
        return [];
    }

    private static function getConfigurationSchema($alert_id) {
        $db = static::getDB();

        $stmt = $db->prepare("SELECT `schema_for_variables`
                            FROM `micro_services`
                            WHERE `id` = :alert_id
                                AND `status` = :status ");

        $stmt->bindValue(':alert_id', $alert_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'ACTIVE', PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if(count($result) > 0){
            return $result[0];
        }
        else{
            return null;
        }

    }



}
