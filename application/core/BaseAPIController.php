<?php
class BaseAPIController extends LSYii_Controller
{
	
	protected function _init()
    {
		if(empty($_POST['username']) || empty($_POST['password'])){
			echo CJSON::encode(array('success'=>'false', 'message'=>'No credentials provided'));
			exit;	
		}
		
   		$identity = new UserIdentity(sanitize_user($_REQUEST['username']), $_REQUEST['password']);

        if (!$identity->authenticate()){
        	echo CJSON::encode(array('success'=>'false', 'message'=>'Credentials are wrong'));
			exit;
        }
	}
}
