<?php defined('BASEPATH') or exit('No direct script access allowed');

class Facebook_login{

    protected $errors;
    protected $ci;
    protected $facebook;
    protected $fb_helper;

    function __construct(){
        $this->ci =& get_instance();
        $this->ci->load->config('facebook');
        
    }

    function boot(){
        $credentials = (object)$this->ci->config->item('credentials', 'facebook');

        $this->facebook = new \Facebook\Facebook([
            'app_id' => $credentials->app_id,
            'app_secret' => $credentials->app_secret,
            'default_graph_version' => 'v2.10'
        ]);
    }

    function get_login_url(){
        $login_config = (object)$this->ci->config->item('login', 'facebook');
        $callback_url = base_url($login_config->redirect_uri);

        $fb_helper = $this->get_redirect_login_helper();
        $login_url = $fb_helper->getLoginUrl($callback_url, $login_config->scopes);

        return $login_url;
    }

    function get_access_token($state=NULL){
        $access_token = NULL;
        $fb_helper = $this->get_redirect_login_helper();

        if ($state) {
            $fb_helper->getPersistentDataHandler()->set('state', $state);
        }

        try{
            $access_token = $fb_helper->getAccessToken(); // get access token of facebook
        }catch(Facebook\Exceptions\FacebookResponseException $e){
            $this->errors = ['Graph returned an error:'=> $e->getMessage()]; //pass error of graph in function errors
        }catch(Facebook\Exceptions\FacebookSDKException $e){
            $this->errors = ['Facebook SDK returned an error:' => $e->getMessage()]; //pass errors of Facebook SDK into function errors
        }

        if(!$access_token AND $fb_helper->getError()){
            $this->errors = [
                'Error' => $fb_helper->getError(),
                'Error Code' => $fb_helper->getErrorCode(),
                'Error Reason' => $fb_helper->getErrorReason(),
                'Error Description' => $fb_helper->getErrorDescription()
            ];
        }
        else $access_token = $access_token->getValue();

        return $access_token;
    }

    public function get_user_info($access_token=NULL){
        $response = NULL;
        
		if($access_token){
			try{
				$response = $this->facebook->get('/me?fields=id,email,first_name,last_name', $access_token);
			}catch(Facebook\Exceptions\FacebookResponseException $e){
				$this->errors = ['Graph returned an error:'=> $e->getMessage()]; //pass error of graph in function errors
			}catch(Facebook\Exceptions\FacebookSDKException $e){
				$this->errors = ['Facebook SDK returned an error:' => $e->getMessage()]; //pass errors of Facebook SDK into function errors
            }
            
            $response = json_decode($response->getGraphUser());
            
			return $response;
        }
        
        return NULL;
    }
    
    function revoke($user_id=NULL, $access_token=NULL){
        if($user_id){
            try{
                $response = $this->facebook->delete(sprintf('//%1$s/permissions', $user_id) , [], $access_token);
            }catch(Facebook\Exceptions\FacebookResponseException $e){
                $this->errors = ['Graph returned an error:'=> $e->getMessage()]; //pass error of graph in function errors
            }catch(Facebook\Exceptions\FacebookSDKException $e){
                $this->errors = ['Facebook SDK returned an error:' => $e->getMessage()]; //pass errors of Facebook SDK into function errors
            }

            $response = json_decode($response->getGraphUser());
            
            if(isset($response->success)) return TRUE;
        }

        return FALSE;
    }

    function validate_access_token($access_token=NULL){

    }

    
    function errors(){
        return $this->errors;
    }

    function has_errors(){
        if(count($this->errors)) return TRUE;
        return FALSE;
    }

    protected function get_redirect_login_helper(){
        if(!$this->fb_helper) $this->fb_helper = $this->facebook->getRedirectLoginHelper();
        return $this->fb_helper;
    }
}