<?php

namespace App\Controllers;

use \App\Auth;
use \App\Models\Subjects;
use \App\Models\Courses;
use \App\Models\Sessions;
use \App\Models\ResourceLibraries;
use \App\Models\Quizzes;
use \App\Models\User;


use \Core\View;

/**
* Profile controller
*
* PHP version 7.0
*/
class Quiz extends Authenticated {

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

        $firstSubjectCourses = array();
        if(count($subjectList) > 0){
            $firstSubjectCourses = Courses::getSubjectCoursesOfFacilitator($subjectList[0]['subject_id'],$user_id);
        }

        View::renderTemplate('Quiz/submissions.html',
        [
            'activeTab' => "Quiz",
            'activeSubTab' => "submissionQuiz",
            'subjectList' => $subjectList,
            'firstSubjectProgramList' => $firstSubjectCourses,
            "Superglobal_session" => $_SESSION
        ]);
    }
    public function getSubmittedQuizAction() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['courseId'];
        $subjectId = $_POST['subjectId'];
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $data = array();
        if(Courses::isCourseMappedToFacilitator($course_id, $user_id)){

            $data["status"] = "Success";
            $data['session_wise_assignment_data']=array();

            foreach($sessions as $session){
                $quizDetails = Quizzes::getSubmittedQuizzes($session['session_id'],$subjectId,$course_id);
                array_push($data['session_wise_assignment_data'],array(
                    "session_id" => $session['session_id'],
                    "session_name" => $session['session_name'],
                    "session_index" => $session['session_index'],
                    "Participants"=> $quizDetails
                ));
            }

            echo json_encode($data);
        }
    }

    public function getSubmittedsubjectAndModuleQuiz() {
        $user_id = $_SESSION['user_id'];
        $course_id = $_POST['courseId'];
        $subjectId = $_POST['subjectId'];
        $sessions = Sessions::getSessionsOfTheCourse($course_id);
        $data = array();
        if(Courses::isCourseMappedToFacilitator($course_id, $user_id)){

            $data["status"] = "Success";
            $data['session_wise_assignment_data']=array();

            $programAndModuleQuizDetails = Quizzes::getSubjectAndModuleSubmittedQuizzes($subjectId);

            array_push($data['session_wise_assignment_data'],array(
                    "Participants"=> $programAndModuleQuizDetails
                ));
            echo json_encode($data);
        }
    }

    /**
    * Serve the detail of submitted quiz groups
    * This is being used on click of quiz card in facilitator login
    */

    public function configureAction(){
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option configureQuiz is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }

        $user_id = $_SESSION['user_id'];
        $resourceTypeList = ResourceLibraries::getGenericTypeList();
        $subjectList = Subjects::getSubjectsOfTheFacilitator($user_id);

        View::renderTemplate('Quiz/configure.html',
        [
            'activeTab' => "Quiz",
            'activeSubTab' => "configureQuiz",
            'subjectList' => $subjectList,
            "Superglobal_session" => $_SESSION,
            "resourceTypeList" => $resourceTypeList
        ]);

    }

    public function quizOfTheSubjectAction(){
        if ($_SESSION['role'] !== "FACILITATOR") {
            Flash::addMessage('option configureQuiz is valid only for user of role Facilitator', Flash::INFO);

            $this->redirect('/');
        }
        $subjectId = $_POST['subject_id'];
        $data = Quizzes::quizOfTheSubject($subjectId);
        echo json_encode($data);
    }

    public function addAction(){
        $user_id = $_SESSION['user_id'];
        $response = Quizzes::addQuiz($_POST['data'],$user_id);
        echo json_encode($response);

    }
    public function updateQuizAction(){
        $user_id = $_SESSION['user_id'];
        $quizId = $_POST['quizId'];
        $subjectId = $_POST['subjectId'];
        $facilitatorUserId = $_SESSION['user_id'];
        if(Subjects::isSubjectMappedToFacilitator($subjectId, $facilitatorUserId)){
            if(Quizzes::isQuizBelongToSubject($quizId,$subjectId)){
                $response = Quizzes::updateQuiz($_POST['data'], $user_id, $quizId);
            }
        }
        echo json_encode($response);

    }

    public function deleteQuizAction(){
        $quizId = $_POST['quizId'];
        $response = Quizzes::deleteQuiz($quizId);
        echo json_encode($response);
    }


    public function getQuizQuestionsAction(){

        if ($_SESSION['role'] === "FACILITATOR") {
            $quizId = $_POST['quizId'];
            $userId = $_POST['user_id'];
            $courseId = $_POST['courseId'];
            $facilitatorUserId = $_SESSION['user_id'];
            if(Courses::isCourseMappedToFacilitator($courseId, $facilitatorUserId)){
                if(Quizzes::isQuizBelongToCourse($quizId,$courseId)){
                    $question = Quizzes::getQuizQuestions($quizId, $userId);
                }
            }
        }
        elseif ($_SESSION['role'] === "PARTICIPANT") {
            $quizId = $_POST['quizId'];
            $user_id = $_SESSION['user_id'];
            $question = Quizzes::getQuizQuestions($quizId, $user_id);
        }
        echo json_encode(array(
            "status"=>"Success",
            "quizDetails"=>$question
        ));

    }
    public function submitQuizAction(){
        $data = $_POST['data'];
        $user_id = $_SESSION['user_id'];
        $response = Quizzes::quizSubmission($data,$user_id);
        echo json_encode($response);
    }
    public function getQuizDetailsAction(){
        $quizId = $_POST['quizId'];
        $subjectId = $_POST['subjectId'];
        $createdQuizDetail = Quizzes::getQuizDetails($quizId, $subjectId);
        echo json_encode($createdQuizDetail);
    }

}
