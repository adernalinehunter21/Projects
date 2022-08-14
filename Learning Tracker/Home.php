<?php

namespace App\Controllers;

use \Core\View;
use \Core\Model;
use \App\Auth;
use App\Models\Sessions;
use App\Models\DashboardData;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
use App\Models\FacilitatorDashboard;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Home extends Authenticated {

    /**
     * Show the home page
     * from this function we are passing active tab and user details to home view
     * @return void
     */
    public function indexAction() {

        $user = Auth::getUser();
        if ($user->role === "PARTICIPANT") {
            $this->participantHome("Home");
        } elseif ($user->role === "FACILITATOR") {
            $this->facilitatorHome("Dashboard");
        } else {
            $this->redirect('/login');
        }
    }

    private function participantHome($tab_name) {

        $course_id = $_SESSION['course_id'];
        $user_id = $_SESSION['user_id'];
        $subject_id = $_SESSION['subject_id'];
        if (isset($_SESSION['user_timezone_configured'])) {
            $userTimezone = $_SESSION['user_timezone_configured'];
        } else {
            $userTimezone = "UTC";
        }
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $sessionDetails = Sessions::getSessionDetailsForProgressChart($course_id, $userTimezone);
        $scoreData = DashboardData::getRewardsDetails($subject_id, $course_id, $user_id);
        $assignmentData = DashboardData::getAssignmentPointsData($subject_id, $course_id, $user_id);
        $banners = Model::getBannerDetails($course_id, $tab_name);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        View::renderTemplate('Home/index.html',
                [
                    'activeTab' => $tab_name,
                    'user_details' => $this->user_details,
                    'banner_details' => $banners,
                    'sessionDetails' => $sessionDetails,
                    'scoreDetails' => $scoreData,
                    'resourceTypeList' => $resourceTypeList,
                    'assignmentDetails' => $assignmentData,
                    "module_list" => $moduleList,
                    "course_org_details" => $_SESSION['course_org_details'],
                    "content_org_details" => $_SESSION['content_org_details'],
                    "navbar_links" => $_SESSION['navbar_links'],
                    "Superglobal_session" => $_SESSION
        ]);
    }

    private function facilitatorHome($tab_name) {

        $user_id = $_SESSION['user_id'];
        $overviewCoursesFor30Days = FacilitatorDashboard::get30DayOverviewCourses($user_id);
        $currentAndUpcomingPrograms = FacilitatorDashboard::getCurrentAndUpcomingPrograms($user_id);
        $facilitatorSubjects = FacilitatorDashboard::getFacilitatorSubjects($user_id);
        $learnedCourses = FacilitatorDashboard::getLearnedCourses($user_id);
        View::renderTemplate('Dashboard/index.html',
                [
                    "activeTab" => $tab_name,
                    "Superglobal_session" => $_SESSION,
                    'facilitatorSubjects' => $facilitatorSubjects,
                    'learnedCourses' => $learnedCourses,
                    'overviewCoursesFor30Days' => $overviewCoursesFor30Days,
                    'currentAndUpcomingPrograms' => $currentAndUpcomingPrograms
        ]);
    }

}
