<?php

namespace App\Controllers;

use \App\Auth;
use \App\Models\Subjects;
use \App\Models\Courses;
use \App\Models\User;
use \App\Models\Assignments;
use \App\Models\ResourceLibraries;
use \Core\View;
use App\s3;
use App\Models\Messages;
use App\Models\Configurations;


/**
 * Profile controller
 *
 * PHP version 7.0
 */
class Assignment extends Authenticated {

    /**
     * Before filter - called before each action method
     *
     * @return void
     */
    protected function before() {
        parent::before();

        $this->user = Auth::getUser();
    }

    /**
     * Show the profile
     *
     * @return void
     */
    public function viewSubmissionsAction() {
//      If user is not the facilitator the redirect to home page
        if($_SESSION['role'] !== "FACILITATOR"){
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);
        $s3Details = Assignments::get_s3_details($user_id);
        $s3Details4AssignmentUpload = $s3Details['s3Details4AssignmentUpload'];
        $attachment_file_prefix = $s3Details['attachment_file_prefix'];
        $firstSubjectCourses = array();
        if(count($subjectList) > 0){
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'],$user_id);
        }

        View::renderTemplate('Assignment/submissions.html',
                            [
                                'activeTab' => "Assignment",
                                'activeSubTab'=>"submissionAssignment",
                                'subjectList' => $subjectList,
                                'firstSubjectProgramList' => $firstSubjectCourses,
                                's3_details_4_attachment_upload' => $s3Details4AssignmentUpload,
                                'attachment_file_prefix'=> $attachment_file_prefix,
                                'user_id' => $user_id,
                                "Superglobal_session" => $_SESSION
                            ]);
    }


    public function configureAction() {
//      If user is not the facilitator the redirect to home page
        if($_SESSION['role'] !== "FACILITATOR"){
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $resourceTypeList = ResourceLibraries::getGenericTypeList();
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        View::renderTemplate('Assignment/configure.html',
                            [
                                'activeTab' => "Assignment",
                                'activeSubTab'=>"configureAssignment",
                                'subjectList' => $subjectList,
                                "Superglobal_session" => $_SESSION,
                                "resourceTypeList" => $resourceTypeList

                            ]);
    }

    public function assignmentsOfTheSubject(){
        $subjectId = $_POST['subjectId'];
        $user_id = $_SESSION['user_id'];
        $s3Details4fileUpload = s3::getDetailsForFileUpload('dasa-learning-tracker-files', 'ap-southeast-1');
        $resourceFilePrefix = "ASR".$_POST['subjectId'].random_int(100, 999);
        $subject_assignments =  Assignments::getAssignmentsOfSubject($subjectId);
        echo json_encode(array(
            "status" => "Success",
            "subject_assignments" => $subject_assignments,
            "s3Details4fileUpload" => $s3Details4fileUpload,
            "resourceFilePrefix" => $resourceFilePrefix
        ));
    }

    public function addAction(){
        $data = $_POST['data'];
        $user_id = $_SESSION['user_id'];
        $subject_id = $data['subject_id'];
        $response = Assignments::addNewAssignment($_POST['data'],$user_id);
        $send_notification = $data['send_notification'];

        if($response['status'] === 'Success' && $send_notification === "true" ){
            $course_ids = Courses::getRuningCoursesOfTheSubject($subject_id, $user_id);
            foreach($course_ids as $one_course_id){
                $course_id = $one_course_id['course_id'];

                    $users = Courses::getUsersOfTheCourse($course_id);
                    $participants = $users['PARTICIPANT'];
                    $to =[];
                    if(isset($users['FACILITATOR'])){
                        $facilitators = $users['FACILITATOR'];
                        foreach($facilitators as $facilitator){
                            array_push($to,$facilitator['user_id']);
                        }
                    }

                    if(isset($users['PARTICIPANT'])){
                        foreach ($participants as $participant) {
                           $user = $participant['user_id'] ;
                           array_push($to, $user);
                        }
                    }
                    array_push($to,$_SESSION['user_id']);

                    $variables_for_message = array();
                    $course_name = Courses::getCourseName($course_id);
                    $variables_for_message['course_name'] = $course_name;

                    $variables_for_message['assignment_name'] = $data['name'];
                    $variables_for_message['assignment_description'] = strip_tags($data['description']);

                    $attachments = [];

                    $Configurations = Configurations::getConfigurations($course_id, 6); //6 for Notify on creation of new Assignment
                    $templates = Configurations::extractSubjectAndBodyTemplates($Configurations);
                    $subject_configuration = View::returnTemplateFromString($templates['subject_template'], $variables_for_message);
                    $message_configuration = View::returnTemplateFromString($templates['message_body_template'], $variables_for_message);

                    $type = "NOTIFICATION";
                    $log_response = Messages::logNewMessage($to, $subject_configuration, $message_configuration, $course_id,
                        $type, $attachments);
                    $message_id = $log_response['message_id'];
                    $notificationResponse =  Messages::notify($message_id, $message_configuration, $attachments, $type);
                    $response = $notificationResponse;

                }
            }
        echo json_encode($response);

    }

    public function deleteAssignment(){
        $assignmentId = $_POST['assignmentId'];
        $deleteAssignment = Assignments::DeleteAssignment($assignmentId);
        echo json_encode($deleteAssignment);
    }

    public function getSubmittedAssignmentsAction() {
        $course_id = $_POST['courseId'];
        $user_id = $_SESSION['user_id'];
        $subjectId = $_POST['subjectId'];

        if(Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            $assignmentDetails = Assignments::getSubmittedAssignmentsOfTheCourse($course_id,$subjectId);
            if($assignmentDetails == []){
                echo json_encode(array(
                    "status" => "Success",
                    "session_wise_assignment_data" => [],
                    "participants_details" => []
                ));
            }
            else{
                echo json_encode(array(
                    "status" => "Success",
                    "session_wise_assignment_data" => $assignmentDetails['session_wise_assignment_data'],
                    "participants_details" => $assignmentDetails['participants_details']
                ));
            }

        }
    }

    public function loadModuleAndSubjectSubmittedAssignmentsAction() {
        $course_id = $_POST['courseId'];
        $user_id = $_SESSION['user_id'];
        $subjectId = $_POST['subjectId'];

        if(Courses::isCourseMappedToFacilitator($course_id, $user_id)){
            $assignmentDetails = Assignments::loadModuleAndSubjectSubmittedAssignments($course_id,$subjectId);
            if($assignmentDetails == []){
                echo json_encode(array(
                    "status" => "Success",
                    "session_wise_assignment_data" => [],
                    "participants_details" => []
                ));
            }
            else{
                echo json_encode(array(
                    "status" => "Success",
                    "session_wise_assignment_data" => $assignmentDetails['session_wise_assignment_data'],
                    "participants_details" => $assignmentDetails['participants_details']
                ));
            }

        }
    }

    public function saveAssignmentAction(){
        $submission = $_POST['submission'];
        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];

        $response = Assignments::saveAssignment($submission, $course_id, $user_id);
        echo json_encode($response);
    }

    public function resubmitAssignmentAction(){
        $submission = $_POST['submission'];
        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        $message_type = 'ASSIGNMENT';
        $assignmentId = $_POST['assignmentId'];
        $message_id = $_POST['message_id'];

        $thread_id = Assignments::getThreadIdOfAssignment($assignmentId, $user_id, $message_type);
        $response = Assignments::resubmitAssignment($submission, $course_id, $user_id, $message_id, $thread_id);
        echo json_encode($response);
    }

    public function retriveMessagesOfAssignment(){
        $assignmentId = $_POST['assignmentId'];
        $message_type = 'ASSIGNMENT';
        if($_SESSION['role'] === 'PARTICIPANT'){
            $user_id = $_SESSION['user_id'];
            $learnersUserId = $_SESSION['user_id'];

            $thread_id = Assignments::getThreadIdOfAssignment($assignmentId, $learnersUserId, $message_type);
            $messages = Messages::getMessagesOfTheThread($user_id, $thread_id, $message_type);
            if ($messages['status'] == "Success") {
                $message_data = $messages['messages'];
                $message_subject = $messages['subject'];
            }
            echo json_encode($message_data);

        }
        elseif($_SESSION['role'] === 'FACILITATOR'){
            $facilitatorId = $_SESSION['user_id'];
            $learnersUserId = $_POST['learnersUserId'];

            $courseId = User::getParticipantCourseId($learnersUserId);
            if(Courses::isCourseMappedToFacilitator($courseId, $facilitatorId)){
                $learnersUserId = $_POST['learnersUserId'];
                $user_id = $_SESSION['user_id'];

                $thread_id = Assignments::getThreadIdOfAssignment($assignmentId, $learnersUserId, $message_type);
                $messages = Messages::getMessagesOfTheThread($user_id, $thread_id, $message_type);
                if ($messages['status'] == "Success") {
                    $message_data = $messages['messages'];
                    $message_subject = $messages['subject'];
                }
                echo json_encode($message_data);
            }

            else{
                echo json_encode([
                    'status' => 'Error',
                    'error' => 'Access denied'
                ]);
                exit;
            }

        }

    }

    public function reviewAssignmentSubmissionAction(){
        $submission = $_POST['submission'];
        $submissionId = $_POST['submissionId'];
        $assignmentId = $_POST['assignmentId'];
        $thread_id = $_POST['thread_id'];
        $learnersUserId = $_POST['learnersUserId'];
        $user_id = $_SESSION['user_id'];
        $message_type = 'ASSIGNMENT';
        $previous_message_id = Assignments::get_previous_message_id($thread_id);
        $response = Assignments::reviewAssignmentSubmission($submission, $submissionId, $thread_id, $user_id, $learnersUserId, $previous_message_id);
        echo json_encode($response);
    }


}
