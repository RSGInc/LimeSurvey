<?php


class FilterController extends BaseAPIController
{
	
	protected function _init()
    {
    	parent::_init();
		Yii::import('application.libraries.Limesurvey_lang'); 		
	}
	
    public function actionQuestions()
    {
    	header('Content-type: application/json');
    	$sids = $this->getSurveyIDs();
    	echo json_encode(Survey_questions::model()->getQuestionsForSurveys($sids));
		exit();
    }
    	
    public function actionAnswers()
    {
    	header('Content-type: application/json');
    	$sids = $this->getSurveyIDs();
    	echo json_encode(Survey_answers::model()->getAnswersForSurveys($sids));
    	exit();
    }

    public function getSurveys() 
    {
    	header('Content-type: application/json');
    	$filters = json_decode($_POST['filters']);
    	echo json_encode(Survey_filter::model()->getSurveyResponses($sids));
    }
    
    
    private function getSurveyIDs() 
    {
    	// caller: parameter name to pass is 'sid[]' for php to treat this as an array
    	@$sids = $_POST['sid'];
    	if ($sids && count($sids) == 1 && $sids[0] === "") {
    		$sids = array();
    	}
    	return $sids;
    }
    
    
}
