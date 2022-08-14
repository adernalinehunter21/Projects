<?php

namespace App\Controllers;

use \DateTimeZone;
use \DateTime;
use \Core\View;
use \App\Auth;
use \App\Flash;
use \App\EventLoger;
use \App\Models\User;
use App\Controllers\FirstLogin;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
/**
 * Profile controller
 *
 * PHP version 7.0
 */
class Profile extends Authenticated {

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
    public function showAction() {
        if($_SESSION['role'] === "PARTICIPANT"){
            
            $this->showParticipantProfile();
        }elseif($_SESSION['role'] === "FACILITATOR"){
            
            $this->showFacilitatorProfile();
        }
        //eventlogging for View profile EventType
        $logDetails = array(
            "user_details" => $this->user
        );
        EventLoger::logEvent('View profile', json_encode($logDetails));
    }
    
    /**
     * show the profile for user of role Participant
     */
    private function showParticipantProfile() {
        $course_id = $_SESSION['course_id'];   
        $user_details['name'] = $_SESSION['user_name'];
        $user_details['profile_pic_binary'] = $_SESSION['profile_pic_binary'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        View::renderTemplate('Profile/show.html', [
            'user' => $this->user, 
            "user_details" => $user_details, 
            'resourceTypeList' => $resourceTypeList,
            "module_list" => $moduleList,
            "course_org_details" => $_SESSION['course_org_details'],
            "content_org_details" => $_SESSION['content_org_details'],
            "navbar_links" => $_SESSION['navbar_links'],
            "Superglobal_session" => $_SESSION
        ]);        
    }
    
    /**
     * show the profile for user of role Facilitator
     */
    private function showFacilitatorProfile() {
        
        View::renderTemplate('Profile/showFacilitatorProfile.html', [
            'user' => $this->user, 
            "Superglobal_session" => $_SESSION
        ]);
    }

    /**
     * Show the form for editing the profile
     *
     * @return void
     */
    public function editAction() {
        if($_SESSION['role'] === "PARTICIPANT"){
            
            $this->editParticipantProfile();
        }elseif($_SESSION['role'] === "FACILITATOR"){
            
            $this->editFacilitatorProfile();
        }
    }
    
    /**
     * Profile editor page for Participant user
     */
    private function editParticipantProfile() {
        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $user_details = User::getUserDetails($_SESSION['user_id']);
        $timeZoneDetails = FirstLogin::TZList(true);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        View::renderTemplate('Profile/edit.html', [
            'user' => $this->user, 
            "user_details" => $user_details, 
            "time_zones_details" => $timeZoneDetails,
            'resourceTypeList' => $resourceTypeList,
            "module_list" => $moduleList,
            "course_org_details" => $_SESSION['course_org_details'],
            "content_org_details" => $_SESSION['content_org_details'],
            "navbar_links" => $_SESSION['navbar_links'],
            "Superglobal_session" => $_SESSION
        ]);
    }
    
    /**
     * Profile editor for the user of role Facilitator
     */
    private function editFacilitatorProfile() {
        //To be supported
    }

    /**
     * Update the profile
     *
     * @return void
     */
    public function updateAction() {
        //If user's timezone is not part of the request, throw the error
        if(!$this->isTimezoneValid($_POST['data']['timezone'])){
            $response = array(
                "status" => "Error",
                "message" => "Invalid timezone, please report to your facilitator and the technical support"
            );
        }else if ($this->user->updateProfile($_POST['data'])) {
            Flash::addMessage('Changes saved');
            $this->updateSession();
            $response = array(
                "status" => "Success"
            );
        } else {
            $response = array(
                "status" => "Error",
                "message" => "Invalid Request"
            );
        }
        echo json_encode($response);
    }

    private function updateSession() {
        $this->user = Auth::getUser();
        $_SESSION['user_id'] = $this->user->id;
        $_SESSION['user_name'] = $this->user->name;
        $_SESSION['profile_pic_binary'] = $this->user->profile_pic_binary;
        $_SESSION['clienttimezone'] = $this->user->timezone;
        $_SESSION['user_timezone_configured'] = $this->user->timezone;
                
    }
    
    /**
     * Validate the timezone string and return true/false
     * @param type $timezone
     * @return boolean 
     */
    private function isTimezoneValid($timezone) {
        $_all_timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        foreach ($_all_timezone_identifiers as $oneTimezone){
            if($timezone === $oneTimezone){
                return true;
            }
        }
        return false;
    }

}
