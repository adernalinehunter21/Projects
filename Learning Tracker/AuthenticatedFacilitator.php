<?php

namespace App\Controllers;
use App\Models\User;
/**
 * Authenticated base controller
 *
 * PHP version 7.0
 */
abstract class AuthenticatedFacilitator extends \Core\Controller {
    
    protected $user_details;
    /**
     * Require the user to be authenticated before giving access to all methods in the controller
     *
     * @return void
     */
    protected function before() {
        $this->requireLogin("FACILITATOR");
        $this->user_details = User::getUserDetails();
        if(isset($_SESSION['user_timezone_configured'])){
            date_default_timezone_set($_SESSION['user_timezone_configured']);
        }else{
            date_default_timezone_set("UTC");
        }
    }

}
