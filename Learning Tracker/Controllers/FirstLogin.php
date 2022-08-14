<?php

namespace App\Controllers;

use \DateTimeZone;
use \DateTime;
use \Core\View;
use \App\Auth;
use App\Controllers\Schedule;
use Spatie\CalendarLinks\Link;
use \App\Mail;
use \App\Models\User;

/**
 * Login controller
 *
 * PHP version 7.0
 */
class FirstLogin extends Authenticated {

    protected function before() {
        
        $this->user = Auth::getUser();

        if(!$this->user){
            $this->redirect('/login');
        }
    }

    public function editProfileAction() {
        //Get the list of timezones and time-zone regions/groups
        $tzlist = $this->TZList(true);
       
        View::renderTemplate('Profile/firstLogin.html', [
            'user' => $this->user,
            "time_zones_details" => array(
                "time_zone_groups" => $tzlist['time_zone_groups'],
                "timezones" => $tzlist['timezones'],
                "client_region" => $tzlist['client_region'],
                "client_timezone" => $tzlist['client_timezone']
            )
        ]);
    }
    
    /***
     * Update user profile and send them the calender invite 
     */
    public function updateProfileAction() {
        if(!$this->isTimezoneValid($_POST['data']['timezone'])){
            $response = array(
                "status" => "Error",
                "message" => "Invalid timezone, please report to your facilitator and the technical support"
            );
        } else if ($this->user) {

            if ($this->user->updateProfile($_POST['data'])) {
                $this->user->activateUser();

                $this->updateSession();

                $response = array(
                    "status" => "Success",
                    "message" => "Updated the profile successully."
                );
            } else {
                $response = array(
                    "status" => "Error",
                    "message" => "Invalid Request"
                );
            }
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

    /***
     * Generate the list of timezones required for user to select during their profile edit
     */
   public static function TZList($data_type = false) {
        $_all_timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $TIMEZONE_LIST = array();
        foreach ($_all_timezone_identifiers as $k => $v) {
            $_tzone_group = explode("/", $_all_timezone_identifiers[$k])[0];
            $_tzone_new = new DateTimeZone($_all_timezone_identifiers[$k]);
            $_tzone_new_date = new DateTime("now", $_tzone_new);
            $tzone_arr = array(
                'timezone' => $_all_timezone_identifiers[$k],
                'timediff' => $_tzone_new_date->format('P'),
                'timezone_offset' => $_tzone_new_date->format('Z') / 60, //minutes
                'text' => "(GMT" . $_tzone_new_date->format('P') . ") " . $_all_timezone_identifiers[$k]
            );
            //BY CONTINENT
            if ($data_type === true) {
                $TIMEZONE_LIST[$_tzone_group][] = $tzone_arr;
            } else {
                $TIMEZONE_LIST[] = $tzone_arr;
            }
        }

        //BY TIMEZONE: "America/New_York"
        if (is_string($data_type)) {
            $key = array_search($data_type, array_column($TIMEZONE_LIST, 'timezone'));
            $TIMEZONE_LIST = $key !== false ? $TIMEZONE_LIST[$key] : null;
        }
        $tzgroups = array();
        $timezones = array();
        foreach ($TIMEZONE_LIST as $key => $value) {
            array_push($tzgroups, $key);
            $temp = array();
            foreach ($value as $timezone){
                $temp[$timezone['timezone']] = $timezone;
            }
            $timezones[$key] = $temp;
        }
        //Get the timezone of the client
        $clientTimezone = $_SESSION['clienttimezone'];
        $token = explode("/", $clientTimezone);
        $clientTzRegion =  $token[0];
        $clientTzRegion = isset($timezones[$clientTzRegion])?$clientTzRegion: "";
        if(isset($token[1])){
            $clientTz = $clientTzRegion."/".$token[1];
            $clientTz = isset($timezones[$clientTzRegion][$clientTz])? $clientTz: "";
        }else{
            $clientTz = "";
        }
        
        if (isset($_SESSION['user_timezone_configured']) && $_SESSION['user_timezone_configured'] != "") {
            $userTimeZone = $_SESSION['user_timezone_configured'];
            $token1 = explode("/", $userTimeZone);
            $userTimeRegion = $token1[0];
        } else {
            $userTimeZone = "";
            $userTimeRegion = "";
        }

        return array(
            "time_zone_groups" => $tzgroups,
            "timezones" => $timezones,
            "client_region" => $clientTzRegion,
            "client_timezone" => $clientTz,
            "userTimeZone" => $userTimeZone,
            "userTimeZoneRegion" => $userTimeRegion
        );
    }

}
