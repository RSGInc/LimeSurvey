<?php
class BaseAPIController extends LSYii_Controller
{
	
	protected function _init()
    {
		header('Content-type: application/json');
		
		if(empty($_POST['username']) || !isset($_POST['password'])){
			$this->handleError(400, "No credentials provided.");
		}
		
   		$identity = new UserIdentity(sanitize_user($_REQUEST['username']), $_REQUEST['password']);

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
