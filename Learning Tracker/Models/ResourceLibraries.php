<?php

namespace App\Models;

use PDO;
use App\s3;

/**
 * modules model
 *
 * PHP version 5.4
 */
class ResourceLibraries extends \Core\Model {

    //groupBy for resources
    public static function group_by($key, $data) {
        $result = array();
        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {

                if (!empty($result) && array_key_exists($val[$key], $result)) {
                    $resources = $result[$val[$key]]["resources"];
                    $resources[] = array_slice($val, 0, -4);
                    $result[$val[$key]]["resources"] = $resources;
                } else {
                    $arraySlice = array();
                    $arraySlice[] = array_slice($val, 0, -4);
                    if ($key == "module_index") {
                        $resources = array("module_index" => $val['module_index'], "module_name" => $val['module_name'], "module_id" => $val['module_id'], "resources" => $arraySlice);
                    }
                    if ($key == "session_index") {
                        $resources = array("session_index" => $val['session_index'], "session_name" => $val['session_name'], "session_id" => $val['session_id'], "resources" => $arraySlice);
                    }
                    if ($key == "topic_id") {
                        $resources = array("topic_id" => $val['topic_id'], "topic_name" => $val['topic_name'], "topic_id" => $val['topic_id'], "resources" => $arraySlice);
                    }
                    $result[$val[$key]] = $resources;
                }
            } else {
                $result[""][] = $val;
            }
        }
        return $result;
    }

    /**
     * Get some values as an associative array, some as column, some as int
     *
     * @return array called module details
     */
    public static function getResourceDetails($course_id, $type) {
        try {
            $resourceTypeList = ResourceLibraries::getTypeList($course_id);
            $db = static::getDB();

            //this query will give resource id, link, name, thumbnail  from resources details table
            $program_resources = array();
            $stmt = $db->prepare("SELECT `resources`.`id`, `source`, `file_name`, `link`,
                                    `resources`.`name`,
                                    `thumbnail_source`, `thumbnail_file_name`, `resources`.`thumbnail`
                                FROM `resource_details`
                                    JOIN `resources` ON (`resource_details`.`resource_id` = `resources`.`id`)
                                WHERE `resources`.`status` = :status
                                    AND `resources`.`course_id` = :course_id
                                    AND `resource_details`.`status` = :status
                                    AND `resources`.`type` = :type
                                    AND `resources`.`resource_for` = :resource_for
                                ORDER BY `name` ASC ");
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_for', "PROGRAM", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $program_resources['program_sections'] = array();
            foreach ($result1 as $resource){
                array_push($program_resources['program_sections'], self::getS3LinkForInternalFiles($resource));
            }

            //this query will gives the details of resources from resources_details & subject_modules table
            $stmt = $db->prepare("SELECT `source`, `file_name`, `link`,
                                        `resources`.`id`,`resources`.`name`,
                                        `thumbnail_source`, `thumbnail_file_name`, `resources`.`thumbnail`,
                                        `resources`.`reference_id`,
                                        `subject_modules`.`module_id`,`subject_modules`.`module_index`,`subject_modules`.`module_name`
                                    FROM `resource_details`
                                    JOIN `resources` ON (`resource_details`.`resource_id` = `resources`.`id`)
                                    JOIN `subject_modules` ON ( `resources`.`reference_id` = `subject_modules`.`module_id`)
                                    WHERE `resources`.`status` = :status
                                        AND `resources`.`course_id` = :course_id
                                        AND `resource_details`.`status` = :status
                                        AND `subject_modules`.`status` = :status
                                        AND `resources`.`type` = :type
                                        AND `resources`.`resource_for` = :resource_for
                                        ORDER BY `name` ASC");
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_for', "MODULE", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result21 = array();
            foreach ($result2 as $resource){
                array_push($result21, self::getS3LinkForInternalFiles($resource));
            }
            $module_resources = ResourceLibraries::group_by("module_index", $result21);

           //this query will gives the details of resources from resources_details & course_sessions table
            $stmt = $db->prepare("SELECT `source`, `file_name`, `link`,
                                        `resources`.`id`,`resources`.`name`,
                                        `thumbnail_source`, `thumbnail_file_name`, `resources`.`thumbnail`,
                                        `resources`.`reference_id`,
                                        `course_sessions`.`session_id`,`course_sessions`.`session_index`,`course_sessions`.`session_name`
                                    FROM `resource_details`
                                    JOIN `resources` ON (`resource_details`.`resource_id` = `resources`.`id`)
                                    JOIN `course_sessions` ON ( `resources`.`reference_id` = `course_sessions`.`session_id`)
                                    WHERE `resources`.`status` = :status
                                        AND `resources`.`course_id` = :course_id
                                        AND `resource_details`.`status` = :status
                                        AND `course_sessions`.`status` = :status
                                        AND `resources`.`type` = :type
                                        AND `resources`.`resource_for` = :resource_for
                                        ORDER BY `name` ASC");
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_for', "SESSION", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $result3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result31 = array();
            foreach ($result3 as $resource){
                array_push($result31, self::getS3LinkForInternalFiles($resource));
            }
            $session_resources = ResourceLibraries::group_by("session_index", $result31);


            //this query will gives the details of resources from resources_details & subject_topics table
            $stmt = $db->prepare("SELECT `source`, `file_name`, `link`,
                                    `course_sessions`.`session_id`,`course_sessions`.`session_index`,`course_sessions`.`session_name`,
                                    `subject_modules`.`module_id`,`subject_modules`.`module_index`,`subject_modules`.`module_name`,
                                    `resources`.`id`, `resources`.`name`,
                                    `thumbnail_source`, `thumbnail_file_name`, `resources`.`thumbnail`,
                                    `resources`.`reference_id`,
                                    `subject_topics`.`id` AS topic_id, `subject_topics`.`name` AS topic_name
                                FROM `resource_details`
                                    JOIN `resources` ON (`resource_details`.`resource_id` = `resources`.`id`)
                                    JOIN `subject_topics` ON ( `resources`.`reference_id` = `subject_topics`.`id` )
                                    JOIN `subject_modules` ON ( `subject_topics`.`module_id` = `subject_modules`.`module_id` )
                                    JOIN `course_session_to_topic_mapping` ON ( `subject_topics`.`id` = `course_session_to_topic_mapping`.`topic_id` )
                                    JOIN `course_sessions` ON ( `course_session_to_topic_mapping`.`session_id` = `course_sessions`.`session_id` )
                                WHERE `resources`.`status` = :status
                                    AND `course_sessions`.`course_id` = :course_id
                                    AND `resource_details`.`status` = :status
                                    AND `subject_topics`.`status` = :status
                                    AND `course_session_to_topic_mapping`.`status` = :status
                                    AND `course_sessions`.`status` = :status
                                    AND `resources`.`type` = :type
                                    AND `resources`.`resource_for` = :resource_for");
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_for', "TOPIC", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $result4 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result41 = array();
            foreach ($result4 as $resource){
                array_push($result41, self::getS3LinkForInternalFiles($resource));
            }

            foreach ($result41 as $topicResource) {
                $session_index = $topicResource['session_index'];
                $module_index = $topicResource['module_index'];

                if (isset($module_resources[$module_index])) {
                    array_push($module_resources[$module_index]['resources'],
                            array(
                                "source" => $topicResource['source'],
                                "link" => $topicResource['link'],
                                "topic_id" => $topicResource['topic_id'],
                                "name" => $topicResource['name'],
                                "thumbnail_source" => $topicResource['thumbnail_source'],
                                "thumbnail" => $topicResource['thumbnail'],
                                "topic_name" => $topicResource['topic_name']
                            )
                        );
                } else {
                    $module_resources[$module_index] = array(
                        "module_index" => $topicResource['module_index'],
                        "module_name" => $topicResource['module_name'],
                        "module_id" => $topicResource['module_id'],
                        "resources" => array(
                            array(
                                "source" => $topicResource['source'],
                                "link" => $topicResource['link'],
                                "topic_id" => $topicResource['topic_id'],
                                "name" => $topicResource['name'],
                                "thumbnail_source" => $topicResource['thumbnail_source'],
                                "thumbnail" => $topicResource['thumbnail'],
                                "topic_name" => $topicResource['topic_name']
                            )
                        )
                    );
                }


                if (isset($session_resources[$session_index])) {
                    array_push($session_resources[$session_index]['resources'],
                            array(
                                "source" => $topicResource['source'],
                                "link" => $topicResource['link'],
                                "topic_id" => $topicResource['topic_id'],
                                "name" => $topicResource['name'],
                                "thumbnail_source" => $topicResource['thumbnail_source'],
                                "thumbnail" => $topicResource['thumbnail'],
                                "topic_name" => $topicResource['topic_name']
                            )
                        );
                } else {
                    $session_resources[$session_index] = array(
                        "sesssion_index" => $topicResource['session_index'],
                        "session_name" => $topicResource['session_name'],
                        "session_id" => $topicResource['session_id'],
                        "resources" => array(
                            array(
                                "source" => $topicResource['source'],
                                "link" => $topicResource['link'],
                                "topic_id" => $topicResource['topic_id'],
                                "name" => $topicResource['name'],
                                "thumbnail_source" => $topicResource['thumbnail_source'],
                                "thumbnail" => $topicResource['thumbnail'],
                                "topic_name" => $topicResource['topic_name']
                            )
                        )
                    );
                }
            }

            // returns an array
            return array(
                "active_resource_type" => $type,
                "resource_type_list" => $resourceTypeList,
                "program_resources" => $program_resources,
                "module_resources" => $module_resources,
                "session_resources" => $session_resources
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getTypeList($course_id) {
        $db = static::getDB();

        //this query will give id, type, icon from resources table
        $stmt = $db->prepare("SELECT `type`,`resource_type`.`type_name`, IFNULL(`resource_type`.`icon`, (SELECT `resource_type`.`icon`
                            FROM `resource_type` WHERE `type_name` = 'Unknown')) AS `icon`
                            FROM `resources`
                            LEFT JOIN `resource_type` ON (`resources`.`type` = `resource_type`.`type_name`)
                            WHERE `course_id` = :course_id
                                AND status = :status GROUP BY `type` ORDER BY `type` ASC");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    public static function getGenericTypeList() {
        $db = static::getDB();

        //this query will give id, type, icon from resources table
        $stmt = $db->prepare("SELECT `type`,`resource_type`.`type_name`, IFNULL(`resource_type`.`icon`, (SELECT `resource_type`.`icon`
                            FROM `resource_type` WHERE `type_name` = 'Unknown')) AS `icon`
                            FROM `resources`
                            LEFT JOIN `resource_type` ON (`resources`.`type` = `resource_type`.`type_name`)
                            WHERE status = :status GROUP BY `type` ORDER BY `type` ASC");
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public static function getModSessionDetails($course_id, $subject_id) {
        try {
            $db = static::getDB();
            //this query will gives session_index & session_name from course_sessions table
            $stmt = $db->prepare("SELECT `session_name` FROM `course_sessions` WHERE `course_id`=:course_id AND `status`=:status");
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $session_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //this query will gives module_index & module_name from subject_modules table
            $stmt = $db->prepare("SELECT `module_name` FROM `subject_modules` WHERE `subject_id`=:subject_id AND `status`=:status");
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->execute();
            $module_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // returns an array
            return array(
                "module_details" => $module_details,
                "session_details" => $session_details
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getSearchResults($course_id, $textEntered, $type, $moduleSearch, $sessionSearch) {
        try {
            $db = static::getDB();

            $query = "SELECT `link`,`name`,`resources`.`type`,`resources`.`id`,`resources`.`reference_id`,
                        `resource_type`.`type_name`,
                        IFNULL(`resource_type`.`icon`,(SELECT `resource_type`.`icon` FROM `resource_type` WHERE `type_name` = 'Unknown')) AS `icon`
                    FROM `resource_details`
                    JOIN `resources` ON (`resource_details`.`resource_id` = `resources`.`id`)
                    LEFT JOIN `resource_type` ON (`resources`.`type` = `resource_type`.`type_name`)
                    LEFT JOIN `subject_modules` ON (`resources`.`reference_id` = `subject_modules`.`module_id`)
                    LEFT JOIN `course_sessions` ON (`resources`.`reference_id` = `course_sessions`.`session_id`)";


            $query = $query . " WHERE `resources`.`status` = :status
                                    AND `resources`.`course_id` = :course_id
                                    AND `resource_details`.`status` = :status
                                    AND `resources`.`name` LIKE :textEntered
                                    AND `resource_details`.`status` = :status";


            if (sizeof($type) > 0) {
                $query = $query . " AND `resources`.`type` IN ('" . implode("','", $type) . "')";
            }
            if (sizeof($moduleSearch) > 0 && sizeof($sessionSearch) > 0) {
                $query = $query . " AND(`subject_modules`.`module_name` IN ('" . implode("','", $moduleSearch) . "')
                OR `course_sessions`.`session_name` IN ('" . implode("','", $sessionSearch) . "'))";
            }
            else if (sizeof($moduleSearch) > 0) {
                $query = $query . " AND (`subject_modules`.`module_name` IN ('" . implode("','", $moduleSearch) . "'))";
            }
            else if (sizeof($sessionSearch) > 0) {
                $query = $query . " AND (`course_sessions`.`session_name` IN ('" . implode("','", $sessionSearch) . "'))";
            }

            $stmt = $db->prepare($query);
            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':textEntered', "%$textEntered%", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $search_resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array(
                "search_resources" => $search_resources
            );
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getResourcesOfTheCourse($course_id, $subject_id){
        try {
            $db = static::getDB();

            $stmt = $db->prepare("SELECT `resources`.`id` AS resource_id, `name`,
                        `type`, `thumbnail_source`, `thumbnail_file_name`, `thumbnail`,
                        `resource_for`, `source`, `file_name`, `link`,
                        sessions.`session_index`, `session_name`,
                        modules.`module_index`, `module_name`,
                        topic_module.module_topic, topic_module_index, topic_module_name,
                        topic_session.session_topic, topic_session_index, topic_session_name,
                        'course' AS resource_is_of
                    FROM `resources`
                    JOIN `resource_details` ON (`resources`.`id` = `resource_details`.`resource_id`)
                    LEFT JOIN(
                        SELECT
                            `session_id`,
                            `session_index`,
                            `session_name`
                        FROM `course_sessions`
                        WHERE `course_id` =  :course_id
                            AND `status` = :status
                    ) AS sessions ON ( `resources`.`reference_id` = sessions.session_id )
                    LEFT JOIN(
                        SELECT
                            `module_id`,
                            `module_index`,
                            `module_name`
                        FROM `subject_modules`
                        WHERE `subject_id` = :subject_id
                            AND `status` = :status
                    ) AS modules ON ( `resources`.`reference_id` = modules.module_id )
                    LEFT JOIN(
                        SELECT  `name` AS module_topic,
                        	`subject_topics`.`id` AS module_topic_id,
                        	`module_index` AS topic_module_index,
                        	`module_name` AS topic_module_name
                        FROM `subject_topics`
                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                        WHERE `subject_id` = :subject_id
                        AND `subject_topics`.`status` = :status
                        AND `subject_modules`.`status` = :status
                    ) AS topic_module ON ( `resources`.`reference_id` = topic_module.module_topic_id )
                    LEFT JOIN(
                        SELECT  `name` AS session_topic,
                            `subject_topics`.`id` AS session_topic_id,
                            `session_index` AS topic_session_index,
                            `session_name` AS topic_session_name
                        FROM `subject_topics`
                        JOIN `course_session_to_topic_mapping` ON (`subject_topics`.`id` = `course_session_to_topic_mapping`.`topic_id`)
                        JOIN `course_sessions` ON (`course_session_to_topic_mapping`.`session_id` = `course_sessions`.`session_id`)
                        WHERE `course_id` = :course_id
                        AND `subject_topics`.`status` = :status
                        AND `course_session_to_topic_mapping`.`status` = :status
                        AND `course_sessions`.`status` = :status
                    ) AS topic_session ON ( `resources`.`reference_id` = topic_session.session_topic_id )
                    WHERE `course_id` = :course_id
                        AND `resources`.`status` = :status
                        AND `resource_details`.`status` = :status ");

            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT `subject_resources`.`id` AS resource_id, `name`,
                        `type`, `thumbnail_source`, `thumbnail_file_name`, `thumbnail`,
                        `resource_for`, `source`, `file_name`, `link`,
                        sessions.`session_index`, `session_name`,
                        modules.`module_index`, `module_name`,
                        topic_module.module_topic, topic_module_index, topic_module_name,
                        topic_session.session_topic, topic_session_index, topic_session_name,
                        'subject' AS resource_is_of
                    FROM `subject_resources`
                    JOIN `subject_resource_details` ON (`subject_resources`.`id` = `subject_resource_details`.`resource_id`)
                    LEFT JOIN(
                        SELECT
                            `session_id`,
                            `session_index`,
                            `session_name`
                        FROM `course_sessions`
                        WHERE `course_id` =  :course_id
                            AND `status` = :status
                    ) AS sessions ON ( `subject_resources`.`reference_id` = sessions.session_id )
                    LEFT JOIN(
                        SELECT
                            `module_id`,
                            `module_index`,
                            `module_name`
                        FROM `subject_modules`
                        WHERE `subject_id` = :subject_id
                            AND `status` = :status
                    ) AS modules ON ( `subject_resources`.`reference_id` = modules.module_id )
                    LEFT JOIN(
                        SELECT  `name` AS module_topic,
                        	`subject_topics`.`id` AS module_topic_id,
                        	`module_index` AS topic_module_index,
                        	`module_name` AS topic_module_name
                        FROM `subject_topics`
                        JOIN `subject_modules` ON (`subject_topics`.`module_id` = `subject_modules`.`module_id`)
                        WHERE `subject_id` = :subject_id
                        AND `subject_topics`.`status` = :status
                        AND `subject_modules`.`status` = :status
                    ) AS topic_module ON ( `subject_resources`.`reference_id` = topic_module.module_topic_id )
                    LEFT JOIN(
                        SELECT  `name` AS session_topic,
                            `subject_topics`.`id` AS session_topic_id,
                            `session_index` AS topic_session_index,
                            `session_name` AS topic_session_name
                        FROM `subject_topics`
                        JOIN `course_session_to_topic_mapping` ON (`subject_topics`.`id` = `course_session_to_topic_mapping`.`topic_id`)
                        JOIN `course_sessions` ON (`course_session_to_topic_mapping`.`session_id` = `course_sessions`.`session_id`)
                        WHERE `course_id` = :course_id
                        AND `subject_topics`.`status` = :status
                        AND `course_session_to_topic_mapping`.`status` = :status
                        AND `course_sessions`.`status` = :status
                    ) AS topic_session ON ( `subject_resources`.`reference_id` = topic_session.session_topic_id )
                    WHERE `subject_id` = :subject_id
                        AND `subject_resources`.`status` = :status
                        AND `subject_resource_details`.`status` = :status ");

            $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindValue(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->execute();
            $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // echo '<pre>'; var_dump($result1); echo '</pre>';exit;
            $resourceData = array();
            foreach (array_merge($result,$result1) as $resource){
                array_push($resourceData, self::getS3LinkForInternalFiles($resource));
            }

            return $resourceData;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public static function getResourceTypesAcrossTheSystem() {
        $db = static::getDB();

        //this query will give id, type, icon from resources table
        $stmt = $db->prepare("SELECT `type`
                            FROM `resources`
                            WHERE `status` = :status
                            GROUP BY `type`
                            ORDER BY `type` ASC ");
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $result;
    }

    public static function addNewResource($data) {
        $course_id = $data['course_id'];

        //Extract detaild of resource for
        if($data['resource_for']['for'] === "Program"){
            $resource_for = "PROGRAM";
            $reference_id = 0;
        }
        elseif($data['resource_for']['for'] === "Module"){
            $resource_for = "MODULE";
            $reference_id = $data['resource_for']['module_id'];
        }
        elseif($data['resource_for']['for'] === "Session"){
            $resource_for = "SESSION";
            $reference_id = $data['resource_for']['session_id'];
        }
        else{
            $resource_for = "TOPIC";
            $reference_id = $data['resource_for']['topic_id'];
        }

        //Extract the detaild of the resource
        $name = $data['resource_details']['name'];
        $type = $data['resource_details']['type'];
        $form = $data['resource_details']['form'];
        if($form === "File"){
            $source = "INTERNAL_FILE";
            $file_name = $data['resource_details']['uploadedResourceFiles'][0]['fileName'];
            $link = $data['resource_details']['uploadedResourceFiles'][0]['internalFileName'];
        }
        else{
            $source = "EXTERNAL_LINK";
            $file_name = "";
            $link = $data['resource_details']['resourceLink'];
        }

        //Extract the details of the thumbnail
        if($data['thumbnail_details']['type'] === "image-upload"){
            $thumbnail_source = "INTERNAL_FILE";
            $thumbnail_file_name = $data['thumbnail_details']['uploadedThumbnailFile'][0]['fileName'];
            $thumbnail = $data['thumbnail_details']['uploadedThumbnailFile'][0]['internalFileName'];
        }
        elseif($data['thumbnail_details']['type'] === 'icon'){
            $thumbnail_source = "ICON";
            $thumbnail_file_name = "";
            $thumbnail = $data['thumbnail_details']['icon'];
        }
        else{
            $thumbnail_source = "EXTERNAL_LINK";
            $thumbnail_file_name = "";
            $thumbnail = $data['thumbnail_details']['link'];
        }

        $db = static::getDB();

        //Start the transaction
        $db->beginTransaction();

        //insert into resources table
        $stmt = $db->prepare("INSERT INTO `resources`
                            (`course_id`, `name`, `type`, `thumbnail_source`, `thumbnail_file_name`, `thumbnail`, `resource_for`, `reference_id`, `status`)
                            VALUES(
                                :course_id,
                                :name,
                                :type,
                                :thumbnail_source,
                                :thumbnail_file_name,
                                :thumbnail,
                                :resource_for,
                                :reference_id,
                                :status
                            )");
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':thumbnail_source', $thumbnail_source, PDO::PARAM_STR);
        $stmt->bindValue(':thumbnail_file_name', $thumbnail_file_name, PDO::PARAM_STR);
        $stmt->bindValue(':thumbnail', $thumbnail, PDO::PARAM_STR);
        $stmt->bindValue(':resource_for', $resource_for, PDO::PARAM_STR);
        $stmt->bindValue(':reference_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if(!$stmt->execute()){
            return array(
                "status" => "Error",
                "message" => "Could not insert thumbnail details"
            );
        }
        $resource_id = $db->lastInsertId();

        //insert the link to source into resource_details table
        $stmt = $db->prepare("INSERT INTO `resource_details`
                            (`resource_id`, `source`, `file_name`, `link`, `status`)
                            VALUES(
                                :resource_id,
                                :source,
                                :file_name,
                                :link,
                                :status
                            )");

        $stmt->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
        $stmt->bindValue(':source', $source, PDO::PARAM_STR);
        $stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
        $stmt->bindValue(':link', $link, PDO::PARAM_STR);
        $stmt->bindValue(':status', "ACTIVE", PDO::PARAM_STR);
        if(!$stmt->execute()){
            $db->rollBack();
            return array(
                "status" => "Error",
                "message" => "Could not insert the resource details"
            );
        }
        $db->commit();
        return array(
            "status" => "Success",
            "message" => ""
        );
    }

    /**
     * Take a resource details as input and replace its link to resource and thumbnail by its full s3-link
     * @param type $resource
     * @return type $resource
     */
    private static function getS3LinkForInternalFiles($resource) {

        //If resource file is a internal file then replace its link by full s3-link
        if ($resource['source'] === "INTERNAL_FILE") {
            $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $resource['link'], $resource['file_name']);
            $resource['link'] = $filePath;
        }
        //If thumbnail is a internal file then replace its link by full s3-link
        if ($resource['thumbnail_source'] === "INTERNAL_FILE") {
            $filePath = s3::getSignedTempUrl('ap-southeast-1', 'dasa-learning-tracker-files', $resource['thumbnail'], $resource['thumbnail_file_name']);
            $resource['thumbnail'] = $filePath;
        }
        return $resource;
    }

    public static function deleteResource($resource_id,$resource_is_of){

        $db = static::getDB();

        if($resource_is_of ==='subject'){
            $stmt = $db->prepare("UPDATE `subject_resources`
                                set `subject_resources`.`status` = :status
                                WHERE `id` = :resource_id ");
            $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
        }

        elseif($resource_is_of ==='course'){
            $stmt = $db->prepare("UPDATE `resources`
                                set `resources`.`status` = :status
                                WHERE `id` = :resource_id ");
            $stmt->bindValue(':status', "INACTIVE", PDO::PARAM_STR);
            $stmt->bindValue(':resource_id', $resource_id, PDO::PARAM_INT);
        }


        if($stmt->execute()){
            return array(
                "status"=>"Success"
            );
        }
        else{
            return array(
                "status"=>"Error",
                "error"=>"There was an error while removing this resource. Please reload and try again."
            );
        }
    }

}
