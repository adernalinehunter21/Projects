<?php

namespace App\Controllers;

use PDO;
use App\s3;
use \App\Auth;
use \Core\View;
use \Core\Model;
use \App\Models\Sessions;
use \App\Models\Assignments;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
use App\Models\Reflections;
use App\Models\Quizzes;
use App\Models\Courses;
use App\Models\ExamPrep;
use App\Models\Messages;

class MyActivities extends AuthenticatedParticipant {

    public function summaryAction() {
        $this->MyActivities("MyActivities", "Summary");
    }

    private function MyActivities($tab_name, $subtab_name) {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $user_id = $_SESSION['user_id'];
        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $assignmentSummary = Assignments::getSummaryDataOfUser($course_id, $user_id);

        foreach ($sessions as $session) {
            $newAssignments = Assignments::getSubjectAssignmentSummaryOfUser($session['session_id'], $user_id);

            foreach ($assignmentSummary['dataForChart'] as $index => $oneSessionData) {
                if ($oneSessionData['session_index'] == $session['session_index'] && isset($newAssignments['dataForChart'][0])) {
                    $assignmentSummary['dataForChart'][$index]['points_earned'] += $newAssignments['dataForChart'][0]['points_earned'];
                    $assignmentSummary['dataForChart'][$index]['points_available'] += $newAssignments['dataForChart'][0]['points_available'];
                    $assignmentSummary['dataForChart'][$index]['earnings'] = array_merge($assignmentSummary['dataForChart'][$index]['earnings'], $newAssignments['dataForChart'][0]['earnings']);
                    $assignmentSummary['dataForChart'][$index]['available_assignments'] = array_merge($assignmentSummary['dataForChart'][$index]['available_assignments'], $newAssignments['dataForChart'][0]['available_assignments']);
                }
            }

            $assignmentSummary['pendingAssignments'] = array_merge($assignmentSummary['pendingAssignments'], $newAssignments['pendingAssignments']);
        }

        $subjectModuleAssignment = Assignments::getModuleAndSubjectAssignmentSummaryOfUser($subject_id, $user_id);
        $assignmentSummary['dataForChart'] = array_merge($assignmentSummary['dataForChart'], $subjectModuleAssignment['dataForChart']);
        $assignmentSummary['pendingAssignments'] = array_merge($assignmentSummary['pendingAssignments'], $subjectModuleAssignment['pendingAssignments']);

        $quizSummary = Quizzes::getSummaryDataOfUser($subject_id,$user_id);
        $examPrepSummary = ExamPrep::getSummaryDataOfUser($subject_id, $user_id);
        $reflectionSummary = Reflections::getSummaryDataOfUser($subject_id, $course_id, $user_id);
        $earned_points_data = array(
            "assignments" => 0,
            "quizzes" => 0,
            "reflections" => 0,
            "exam_preps" => 0,
            "total" => 0
        );

        $availble_points_data = array(
            "assignments" => 0,
            "quizzes" => 0,
            "reflections" => 0,
            "exam_preps" => 0,
            "total" => 0
        );
        foreach ($assignmentSummary['dataForChart'] as $oneAssignment) {
            $earned_points_data['assignments'] += $oneAssignment['points_earned'];
            $availble_points_data['assignments'] += $oneAssignment['points_available'];
        }

        foreach ($quizSummary['dataForChart'] as $oneQuiz) {
            $earned_points_data['quizzes'] += $oneQuiz['points_earned'];
            $availble_points_data['quizzes'] += $oneQuiz['points_available'];
        }

        foreach ($examPrepSummary['dataForChart'] as $oneExamPrep) {
            $earned_points_data['exam_preps'] += $oneExamPrep['points_earned'];
            $availble_points_data['exam_preps'] += $oneExamPrep['points_available'];
        }

        foreach ($reflectionSummary['dataForChart'] as $oneReflection) {
            $earned_points_data['reflections'] += $oneReflection['points_earned'];
            $availble_points_data['reflections'] += $oneReflection['points_available'];
        }

        $earned_points_data['total'] = $earned_points_data['assignments'] + $earned_points_data['quizzes'] + $earned_points_data['exam_preps'] + $earned_points_data['reflections'];

        $availble_points_data['total'] = $availble_points_data['assignments'] + $availble_points_data['quizzes'] + $availble_points_data['exam_preps'] + $availble_points_data['reflections'];

        View::renderTemplate('MyActivities/summary.html',
                ['activeTab' => $tab_name,
                    'activeSubTab' => $subtab_name,
                    "assignmentSummary" => $assignmentSummary,
                    "quizSummary" => $quizSummary,
                    "examPrepSummary" => $examPrepSummary,
                    "reflectionSummary" => $reflectionSummary,
                    "earned_points_data" => $earned_points_data,
                    "availble_points_data" => $availble_points_data,
                    "user_details" => $this->user_details,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

    //assignmentsAction is for the assignment tab.

    public function assignmentsAction() {
        $this->Assignment("MyActivities", "Assignments");
    }

    private function Assignment($tab_name, $subtab_name) {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $user_id = $_SESSION['user_id'];
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $sessionWiseAssignments = array();

        foreach ($sessions as $session) {

            $sessionAssignments = Assignments::getSessionAssignments($course_id, $session['session_id'], $user_id);
            $session_level_assignment_of_subject = Assignments::getSessionAssignmentsOfSubject($course_id, $session['session_id'], $user_id);
            array_push($sessionWiseAssignments, array(
                "session_id" => $session['session_id'],
                "session_name" => $session['session_name'],
                "session_index" => $session['session_index'],
                "assignments" => array_merge($sessionAssignments, $session_level_assignment_of_subject),
            ));
        }

        $moduleAndSubjectAssignments = Assignments::getModuleAndSubjectMappedAssignments($subject_id, $user_id);
        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $s3Details = Assignments::get_s3_details($user_id);
        $s3Details4AssignmentUpload = $s3Details['s3Details4AssignmentUpload'];
        $attachment_file_prefix = $s3Details['attachment_file_prefix'];
        $teamMembers = Courses::getTeamMemberList($course_id, $user_id);
        View::renderTemplate('MyActivities/Assignments.html',
                [
                    'activeTab' => $tab_name,
                    'activeSubTab' => $subtab_name,
                    'user_id' => $user_id,
                    's3_details_4_attachment_upload' => $s3Details4AssignmentUpload,
                    'attachment_file_prefix'=> $attachment_file_prefix,
                    "sessionWiseAssignments" => $sessionWiseAssignments,
                    "moduleAndSubjectAssignments" => $moduleAndSubjectAssignments,
                    "user_details" => $this->user_details,
                    'team_members' => $teamMembers,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }



    /**
     * Quiz controller
     */
    public function QuizAction() {
        $this->Quiz("MyActivities", "Quiz");
    }

    private function Quiz($tab_name, $subtab_name) {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $user_id = $_SESSION['user_id'];
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $sessionWiseQuiz = array();

        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);

        foreach ($sessions as $session) {

           $quizDetails = Quizzes::getSessionQuiz($session['session_id'], $user_id, $subject_id);
           array_push($sessionWiseQuiz, array(
               "session_id" => $session['session_id'],
               "session_name" => $session['session_name'],
               "session_index" => $session['session_index'],
               "quiz_question_group" => $quizDetails,
           ));
       }
       $moduleAndSubjectQuiz = Quizzes::getModuleAndSubjectMappedQuiz($subject_id, $user_id);

       View::renderTemplate('MyActivities/quiz.html',
       [
           'activeTab' => $tab_name,
           'activeSubTab' => $subtab_name,
           "sessionWiseQuiz" => $sessionWiseQuiz,
           "user_details" => $this->user_details,
           'resourceTypeList' => $resourceTypeList,
           'banner_details' => $banners,
           "module_list" => $moduleList,
           "course_org_details" => $_SESSION['course_org_details'],
           "content_org_details" => $_SESSION['content_org_details'],
           "navbar_links" => $_SESSION['navbar_links'],
           "moduleAndSubjectQuiz" => $moduleAndSubjectQuiz,
           "Superglobal_session" => $_SESSION
       ]);
    }

    /**
     * Reflection controller
     */
    public function ReflectionAction() {
        $this->Reflection("MyActivities", "Reflections");
    }

    private function Reflection($tab_name, $subtab_name) {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $user_id = $_SESSION['user_id'];

        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $sessionWiseReflections = array();

        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        $moduleList = \App\Models\Modules::getModuleList($subject_id);

        $reflectionsNotMappedToSessions = Reflections::getSessionReflectionsOfTheUser($user_id, $subject_id);
        if ($reflectionsNotMappedToSessions['status'] === "Success" && count($reflectionsNotMappedToSessions['data']) > 0) {
            array_push($sessionWiseReflections, array(
                "session_id" => 0,
                "group" => "Program",
                "Reflections" => $reflectionsNotMappedToSessions['data'],
            ));
        }

        foreach ($sessions as $session) {

            $reflectionDetails = Reflections::getSessionReflectionsOfTheUser($user_id, $subject_id, $session['session_id']);

            if ($reflectionDetails['status'] === "Success" && count($reflectionDetails['data']) > 0) {
                array_push($sessionWiseReflections, array(
                    "session_id" => $session['session_id'],
                    "group" => "Session " . $session['session_index'] . ": " . $session['session_name'],
                    "Reflections" => $reflectionDetails['data'],
                ));
            }
        }

        View::renderTemplate('MyActivities/reflections.html',
                [
                    'activeTab' => $tab_name,
                    'activeSubTab' => $subtab_name,
                    "sessionWiseReflections" => $sessionWiseReflections,
                    "user_details" => $this->user_details,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

    /**
     * ExamPrep controller
     */
    public function examPrepAction() {
        $this->examPrep("MyActivities", "ExamPrep");
    }

    private function examPrep($tab_name, $subtab_name) {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];

        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);

        $moduleList = Modules::getModuleList($subject_id);

        $moduleWiseDetails = array();

        foreach ($moduleList as $oneModuleDetail) {

            $module_index = $oneModuleDetail['module_index'];
            $module_id = $oneModuleDetail['module_id'];

            $exam_prep_details = Modules::getExamPrepDetails($course_id, $module_id);
            array_push($moduleWiseDetails, array(
                "moduleIndex" => $oneModuleDetail['module_index'],
                "moduleName" => $oneModuleDetail['module_name'],
                "exam_prep_details" => $exam_prep_details,
            ));
        }

        View::renderTemplate('MyActivities/examPrep.html',
                [
                    'activeTab' => $tab_name,
                    'activeSubTab' => $subtab_name,
                    "moduleWiseDetails" => $moduleWiseDetails,
                    "user_details" => $this->user_details,
                    'resourceTypeList' => $resourceTypeList,
                    'banner_details' => $banners,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

}

?>
