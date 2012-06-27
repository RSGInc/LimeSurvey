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
    	
    	// get a list of filters keyed by each survey id
    	$filtersBySurvey = array();
    	$qids = $_POST['qid'];
    	foreach ($qids as $qid) {
    		$sid = $_POST['sid_'.$qid];
    		
    		$filter = new stdClass();
    		$filter->qid = $qid;
    		$filter->gid = $_POST['gid_'.$qid];
    		$filter->type = $_POST['type_'.$qid];
    		$filter->name = $_POST['name_'.$qid];
    		$filter->codes = $this->getArrayFromPost('code_'.$qid);
    		$filter->values = $this->getArrayFromPost('value_'.$qid);
    		    		    		
    		if (!$filtersBySurvey[$sid]) {
    			$filtersBySurvey[$sid] = array();
    		}    		
    		array_push($filtersBySurvey[$sid], $filter);
    	}
    	   	
    	echo json_encode(Survey_filter::model()->getSurveyResponses($filtersBySurvey));
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
    	if (!$value || $values && count($values) == 1 && $values[0] === "") {
    		$values = array();
    	}
    	return $values;
    }
    
}
