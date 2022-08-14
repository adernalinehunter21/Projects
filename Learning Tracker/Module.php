<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\Modules;
use \App\Models\Assignments;
use \App\Models\Quizzes;
use \App\Models\ResourceLibraries;
use \App\Models\Notes;
use App\Models\Subjects;
use \App\Models\Courses;
use App\Models\Reflections;
use App\s3;
/**
 * module controller
 *
 * PHP version 7.0
 */
class Module extends Authenticated {

    /**
     * Show the index page
     * from this function we are passing active tab, user details, module details and active module index to modules view page
     * @return void
     */
    public function detailsAction() {

        $this->Module("Modules");
    }

    private function Module($tab_name) {
        $module_index = $this->route_params['token'];
        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $banners = Modules::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        if($course_id <= 3){
            $module_details = Modules::getModuleDetails($course_id, $module_index);
            View::renderTemplate('Modules/index.html',
                    ['activeTab' => $tab_name,
                        "user_details" => $this->user_details,
                        'module_detail' => $module_details,
                        'active_module_index' => $module_index,
                        'resourceTypeList' => $resourceTypeList,
                        'banner_details' => $banners,
                        "module_list" => $moduleList,
                        "course_org_details" => $_SESSION['course_org_details'],
                        "content_org_details" => $_SESSION['content_org_details'],
                        "navbar_links" => $_SESSION['navbar_links'],
                        "Superglobal_session" => $_SESSION
                    ]);
        }else{
            $module_details = Modules::getModuleDetailsNew($course_id, $module_index, $moduleList);
            $module_notes = Notes::getModuleNotes($module_index, $subject_id);
            $assignments = Assignments::getModuleAndTopicMappedAssignments($subject_id, $user_id, $module_index);
            $teamMembers = Courses::getTeamMemberList($course_id, $user_id);

            $s3Details = Assignments::get_s3_details($user_id);
            $s3Details4AssignmentUpload = $s3Details['s3Details4AssignmentUpload'];
            $attachment_file_prefix = $s3Details['attachment_file_prefix'];

            $moduleAssignment = array();
            foreach($assignments as $assignment){
                if(($module_index == $assignment['module_index']) || ($module_index == $assignment['topic_module_index'])){
                    array_push($moduleAssignment,$assignment);
                }
            }

            $reflection_data = Reflections::getModuleRefelectionsOfTheUser($user_id, $module_details['module_id']);
            if ($reflection_data['status'] === "Success") {
                $reflections = $reflection_data['data'];
            } else {
                $reflections = array();
            }
            $quizGroup = Quizzes::getModuleAndTopicMappedQuiz($subject_id, $user_id,$module_details['module_id']);

            View::renderTemplate('Modules/newIndex.html',
                    ['activeTab' => $tab_name,
                        "user_details" => $this->user_details,
                        'module_details' => $module_details,
                        'active_module_index' => $module_index,
                        'resourceTypeList' => $resourceTypeList,
                        'banner_details' => $banners,
                        "module_list" => $moduleList,
                        'team_members' => $teamMembers,
                        "course_org_details" => $_SESSION['course_org_details'],
                        "content_org_details" => $_SESSION['content_org_details'],
                        "navbar_links" => $_SESSION['navbar_links'],
                        "Superglobal_session" => $_SESSION,
                        "module_notes"=> $module_notes,
                        "moduleAssignment" => $moduleAssignment,
                        "moduleReflections" => $reflections,
                        "moduleQuiz" => $quizGroup,
                        's3_details_4_attachment_upload' => $s3Details4AssignmentUpload,
                        'attachment_file_prefix'=> $attachment_file_prefix
                    ]);
        }
    }

    /**
     * Get the json containing the list of module of the subject
     * Required to list the modules for dorp-downs
     */
    public function getModulesOfTheSubjectAction() {

        $subject_id = $_POST['data']['subject_id'];
        $user_id = $_SESSION['user_id'];

        $module_list = array();

        if(Subjects::isSubjectMappedToFacilitator($subject_id, $user_id)){
            $module_list = Modules::getModuleList($subject_id);
        }

        echo json_encode($module_list);
    }
}
