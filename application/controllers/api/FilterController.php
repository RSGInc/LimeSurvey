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
        
        
        header('Content-type: application/json');
        echo CJSON::encode(array('questions'=>Filter_questions::model()->getQuestions($sids)));
        Yii::app()->end();
    }

    public function actionAnswers()
    {
    	$sids = $this->getSurveyIDs();
    	
        header('Content-type: application/json');
        echo CJSON::encode(array('answers'=>Filter_answers::model()->getAnswers($sids)));
        Yii::app()->end();
        
    }

    public function actionResponses()
    {
    	//header('Content-type: application/json');
    	
    	// get a list of filters keyed by each survey id
    	// cid is column id as specified in Filter_questions model
    	$filtersBySurvey = array();
    	$cids = $_POST['cid'];
    	foreach ($cids as $cid) {
    		$sid = $_POST['sid_'.$cid];
    		
    		$filter = new stdClass();
    		$filter->cid = $cid;
    		$filter->sid = $sid;
    		$filter->qid = $_POST['qid_'.$cid];
    		$filter->gid = $_POST['gid_'.$cid];
    		$filter->type = $_POST['type_'.$cid];
    		$filter->name = $_POST['name_'.$cid];
    		$filter->codes = $this->getArrayFromPost('code_'.$cid);
    		$filter->values = $this->getArrayFromPost('value_'.$cid);
    		    		    		
    		if (!isset($filtersBySurvey[$sid])) {
    			$filtersBySurvey[$sid] = array();
    		}    		
    		array_push($filtersBySurvey[$sid], $filter);
    	}
    	$filter = new Filter_responses();
        
        
        header('Content-type: application/json');
        echo CJSON::encode(array('responses'=>$filter->getResponses($filtersBySurvey)));
        Yii::app()->end();
    }
        
    private function getSurveyIDs() 
    {
    	return $this->getArrayFromPost('sid');
    }
    
    private function getArrayFromPost($name)
    {
    	// caller: parameter name to pass is $name.'[]' for php to treat this as an array
    	@$values = $_POST[$name];

    	// not present or not populated gets an empty array
    	if (!$values || $values && count($values) == 1 && $values[0] === "") {
    		$values = array();
    	}
    	return $values;
    }
    
}
