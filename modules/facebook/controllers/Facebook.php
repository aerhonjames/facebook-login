<?php defined('BASEPATH') OR exit('No direct script access allowed');

use CI\Models\Customer;

class Facebook extends Public_Controller{

    function __construct(){
        parent::__construct();

        if($this->customer_account->has_active_session()) show_404();

        $this->load->library(['facebook_login']);
        $this->facebook_login->boot();
    }

    function login(){

        $login_url = $this->facebook_login->get_login_url();

        redirect($login_url);
    }

    function callback(){

        $state = $this->input->get('state');

        if(!$state) show_404();
        
        $access_token = $this->facebook_login->get_access_token($state);

        if(!$access_token){
             if(is_mobile()) redirect('mobile/login');
             redirect('login');
        }

        $user_details = $this->facebook_login->get_user_info($access_token);

        if(!isset($user_details->email)){
            $this->facebook_login->revoke($user_details->id, $access_token);

            $this->message->set_error('Our website requires your Facebook email address to process your login credentials and provide better experience.');

            if(is_mobile()) redirect('mobile/login');
            redirect('login');
        }
        
        $customer = new Customer;

        $_customer = $customer
            ->referenceId($user_details->id)
            ->first();

        if($_customer instanceOf Customer AND $_customer->exists){
            $_customer->reference_token = $access_token; // update access token

            if($_customer->isSuspended()){
                $this->message->set_error('Your account is currently suspended.');

                if(is_mobile()) redirect('mobile/login');
                redirect('login');
            }
            else{
                if($_customer->save()){
                    $this->customer_account->login($_customer);

                    if(is_mobile()) redirect('mobile/products/all');
                    redirect('products/all');
                }
            }
        }
        else{
            $_customer = $customer
                ->where('email', '=', $user_details->email)
                ->first();
            
            if($_customer instanceOf Customer AND $_customer->exists){ // if exists with email address update details

                if(!$_customer->reference_id){
                    $_customer->reference_id = $user_details->id;
                    $_customer->reference_token = $access_token;
                }

                if($_customer->isInactive()){
                    $_customer->status = 'active';
                    $_customer->resetRequestKey();
                }

                if($_customer->isSuspended()){
                    $this->message->set_error('Your account is currently suspended.');
                    
                    if(is_mobile()) redirect('mobile/login');
                    redirect('login');
                }
                else{
                    if($_customer->save()){
                        $this->customer_account->login($_customer);

                        if(is_mobile()) redirect('mobile/products/all');
                        redirect('products/all');
                    }
                }
            }
            else{
                // Create new account
                $customer->email = $user_details->email;
                $customer->first_name = $user_details->first_name;
                $customer->last_name = $user_details->last_name;

                $customer->reference_id = $user_details->id;
                $customer->reference_token = $access_token;
                $customer->registration_type = 'facebook';
                $customer->status = 'active';
                $customer->password = random_string('alnum', 8);
                $customer->generateRequestKey();
                $customer->generateAccessKey();

                if($customer->save()){
                    $this->customer_account->login($customer);
                    // Send registration success
                    $this->notification
						->success_facebook_registration($customer)
                        ->send();
                    
                    if(is_mobile()) redirect('mobile/products/all');
                    redirect('products/all');
                }
            }
        }
    }
}