<?php

namespace App\Controllers;

use \Core\View;
use \App\Models\User;
use App\Models\Organisations;
use \App\Auth;
use \App\Flash;
use \App\EventLoger;

/**
 * Login controller
 *
 * PHP version 7.0
 */
class Login extends \Core\Controller {

    /**
     * Show the login page
     *
     * @return void
     */
    public function newAction() {
        $host = $_SERVER['HTTP_HOST'];
        $brandDetails = Organisations::getBrandDetails($host);
        if($brandDetails != null){
            View::renderTemplate('Login/new.html', [
                'brand_details' => $brandDetails
            ]);
        }else{
            View::renderTemplate('Login/new.html');
        }
    }

    /**
     * Log in a user
     *
     * @return void
     */
    public function createAction() {
        $user = User::authenticate($_POST['email'], $_POST['password']);

        $remember_me = isset($_POST['remember_me']);
        $rememberPasswordStatus = isset($_POST['remember_me']) ? "optedin" : "optedout";
        if ($user) {

            Auth::login($user, $remember_me, $_POST['timezone']);

            Flash::addMessage('Login successful');
            //eventlogging for Login 
            $logDetails = array(
                "type" => "credentials",
                "remember_password" => $rememberPasswordStatus
            );
            EventLoger::logEvent('Login success', json_encode($logDetails));
            
            if ($user->is_active) {
                $this->redirect(Auth::getReturnToPage()); 
            }
            else{
                $this->redirect('/FirstLogin/editProfile');
            }
              
        } else {


            Flash::addMessage('Login unsuccessful, please try again', Flash::WARNING);

            $host = $_SERVER['HTTP_HOST'];
            $brandDetails = Organisations::getBrandDetails($host);
            if ($brandDetails != null) {
                View::renderTemplate('Login/new.html', [
                    'brand_details' => $brandDetails,
                    'email' => $_POST['email'],
                    'remember_me' => $remember_me
                ]);
            } else {
                View::renderTemplate('Login/new.html', [
                    'email' => $_POST['email'],
                    'remember_me' => $remember_me
                ]);
            }

            // eventlogging for login failed 
            $logDetails = array(
                "invalid_email" => $_POST['email']
            );
            EventLoger::logEvent('Login failed', json_encode($logDetails), 0);
        }
    }

    /**
     * Log out a user
     *
     * @return void
     */
    public function destroyAction() {
        //eventlogging for Logout 
        $logDetails = array();
        EventLoger::logEvent('Logout', json_encode($logDetails, JSON_FORCE_OBJECT)); // {}
        Auth::logout();

        $this->redirect('/login/show-logout-message');
    }

    /**
     * Show a "logged out" flash message and redirect to the homepage. Necessary to use the flash messages
     * as they use the session and at the end of the logout method (destroyAction) the session is destroyed
     * so a new action needs to be called in order to use the session.
     *
     * @return void
     */
    public function showLogoutMessageAction() {
        Flash::addMessage('Logout successful');

        $this->redirect('/');
    }

}
