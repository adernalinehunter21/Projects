<?php

namespace App\Controllers;

use \Core\View;
use App\Models\Teams;
use \App\Models\ResourceLibraries;
use \App\Models\Modules;
/**
 * Team controller
 *
 * PHP version 7.0
 */
class Team extends AuthenticatedParticipant {

    /**
     * Show the Team page
     * from this function we are passing active tab and user details to team view
     * @return void
     */
    public function newAction() {

        $this->Team("Team");
    }

    private function Team($tab_name) {


        $course_id = $_SESSION['course_id'];
        $subject_id = $_SESSION['subject_id'];
        $moduleList = \App\Models\Modules::getModuleList($subject_id);
        $banners = Teams::getBannerDetails($course_id, $tab_name);
        $team_details = Teams::getTeamDetails($course_id);
        $resourceTypeList = ResourceLibraries::getTypeList($course_id);
        View::renderTemplate('Team/Team.html', 
                ['activeTab' => $tab_name, 
                    "user_details" => $this->user_details, 
                    "team_details" => $team_details, 
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
