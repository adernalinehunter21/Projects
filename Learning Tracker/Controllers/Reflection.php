<?php

namespace App\Controllers;

use \App\Auth;
use App\Models\Reflections;
use \App\Models\Subjects;
use \App\Models\Courses;
use \App\Models\Sessions;
use \App\Models\ResourceLibraries;
use \Core\View;

/**
 * schedule controller
 *
 * PHP version 7.0
 */
class Reflection extends Authenticated {

    /**
     * Before filter - called before each action method
     *
     * @return void
     */
    protected function before() {
        parent::before();

        $this->user = Auth::getUser();
    }

    public function getAction() {

        $response = Reflections::getReflectionDetails($_POST['data']);
        if ($response['status'] == "Error") {
            $response = array(
                "status" => "Error",
                "message" => $reflectionDetails['message']
            );
        }
        echo json_encode($response);
    }

    public function updateAction() {

        $logResult = array();
        //Log the request and return error if there is any issue
        $logResult = Reflections::updateReflectionDetails($_POST['data']);
        if ($logResult['status'] == "Success") {
            $response = array(
                "status" => "Success",
                "answeredStatus" => $logResult['answeredStatus']
            );
            echo json_encode($response);
        } else {
            $response = array(
                "status" => "Error",
                "message" => $logResult['message']
            );
            echo json_encode($response);
        }
    }

    public function viewSubmissionsAction() {

//      If user is not the facilitator the redirect to home page
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }

        $user_id = $_SESSION['user_id'];
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        $firstSubjectCourses = array();
        if (count($subjectList) > 0) {
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'], $user_id);
        }

        View::renderTemplate('Reflection/submission.html',
                [
                    'activeTab' => "Reflection",
                    'activeSubTab' => "submissionReflection",
                    'subjectList' => $subjectList,
                    'firstSubjectProgramList' => $firstSubjectCourses,
                    "Superglobal_session" => $_SESSION
        ]);
    }

    public function getSubmittedReflectionAction() {

        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['courseId'];
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        if (Courses::isCourseMappedToFacilitator($course_id, $user_id)) {

            $data["status"] = "Success";
            $data['session_wise_assignment_data'] = array();
            foreach ($sessions as $session) {

                $quizDetails = Reflections::getSubmittedReflection($session['session_id']);
                array_push($data['session_wise_assignment_data'], array(
                    "session_id" => $session['session_id'],
                    "session_name" => $session['session_name'],
                    "session_index" => $session['session_index'],
                    "Participants" => $quizDetails
                ));
            }

            echo json_encode($data);
        }
    }

    public function getReflectionDetailsAction() {
        $reflectionId = $_POST['reflectionId'];
        $userId = $_POST['user_id'];
        $course_id = $_POST['courseId'];
        $facilitatorUserId = $_SESSION['user_id'];
        $data = array();
        if (Courses::isCourseMappedToFacilitator($course_id, $facilitatorUserId)) {
            $data = Reflections::getReflectionsDetails($reflectionId, $userId);
        }

        echo json_encode($data);
    }

    public function configureAction() {
//      If user is not the facilitator the redirect to home page
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $resourceTypeList = ResourceLibraries::getGenericTypeList();
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        View::renderTemplate('Reflection/configure.html',
                [
                    'activeTab' => "Reflection",
                    'activeSubTab' => "configureReflection",
                    'subjectList' => $subjectList,
                    "Superglobal_session" => $_SESSION,
                    "resourceTypeList" => $resourceTypeList
        ]);
    }

    /**
     * Serve the ajax request to display the configured reflection topics of a subject
     */
    public function reflectionsOfTheSubjectAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $subject_id = $_POST['subject_id'];

        $data = Reflections::reflectionsOfTheSubject($subject_id);
        echo json_encode($data);
    }

    /**
     * Serve the ajax request to delete a specific reflection topic
     */
    public function deleteReflectionAction() {
        $reflection_id = $_POST['reflection_id'];

        $response = Reflections::deleteReflection($reflection_id);
        echo json_encode($response);
    }

    /**
     * Serve the ajax request to add new reflection topic
     */
    public function addAction() {
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option viewSubmissions is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $user_id = $_SESSION['user_id'];
        $data = $_POST['data'];
        if (Subjects::isSubjectMappedToFacilitator($data['subject_id'], $user_id)) {
            $response = Reflections::addNewReflection($data);
        } else {
            $response = array(
                "status" => "Error",
                "error" => "Access denied for the selected subject. Please consult our support"
            );
        }
        echo json_encode($response);
    }

}
