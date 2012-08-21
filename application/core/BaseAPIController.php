<?php
class BaseAPIController extends LSYii_Controller
{
    
    protected function _init()
    {
	$username = null;
	$password = null;
        if(empty($_REQUEST['username']) || empty($_REQUEST['password'])){
            $post = file_get_contents("php://input");
	    $params = CJSON::decode($post, true);
            if(!isset($params) || !$params['username'] || !$params['password']) {
                $this->handleError(400, "No credentials provided.");
	    } else {
		$username = $params['username'];
		$password = $params['password'];
	    }
        } else {
	    $username = $_REQUEST['username'];
	    $password = $_REQUEST['password'];
	}

        $username = sanitize_user($username);

        $identity = new UserIdentity($username, $password);

        if (!$identity->authenticate()){
            $this->handleError(400, "Credentials are wrong.");
        }
             
        
    }

    // tried hooking up yii's error handling - didn't get it to work...
    public function handleError($httpStatus, $message){
        $statusHeader = null;
        switch ($httpStatus) {
            case 400:
                $statusHeader = 'HTTP/1.0 400 Bad Request';
                break;
            case 500:
                $statusHeader = 'HTTP/1.0 500 Internal Server Error';
                break;
        }
        header($statusHeader, true, $httpStatus);
        $output = array("status" => $httpStatus, "message" => $message);
        echo json_encode($output);
        exit;        
    }
        
}
