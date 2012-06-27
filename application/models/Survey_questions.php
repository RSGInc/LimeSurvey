<?php
class Survey_questions extends CActiveRecord{

	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return '{{questions}}';
	}

    function getQuestionsForSurveys($iSurveyIDs = array())
    {    	   	
    	$questionSql = $this->getQuestionQuery();
    	$this->joinSurveys($questionSql, $iSurveyIDs);

    	$subquestionSql = $this->getSubquestionQuery();
    	$this->joinSurveys($subquestionSql, $iSurveyIDs);
    	
    	// couldn't figure out how to get setUnion to work like this...
		$unioned = "SELECT * FROM (";
		$unioned .= 	$questionSql->getText();
		$unioned .= " UNION ";
		$unioned .= 	$subquestionSql->getText();		
		$unioned .= ") a ORDER BY qid";
    	return Yii::app()->db->createCommand($unioned)->queryAll();		 
    }

    private function getQuestionQuery() 
    {	
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.gid, q.type, q.title name, q.question description, ('') AS context, q.other");
    	$query->from("{{questions}} q");
    
    	// not including F (array questions) - included in subquestions
    	$query->where("q.parent_qid=0 AND type IN ('L', 'N', 'Y', 'T', 'S', 'M')");
    	return $query;
    }
    
    private function getSubquestionQuery() 
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.gid, q.type, q.title name, q.question description, parent.question context, q.other"); 
    	$query->from("{{questions}} q");
    	$query->join("{{questions}} parent",  "q.parent_qid=parent.qid AND parent.type='F'");
    	return $query;
    }
    
    private function joinSurveys($query, $iSurveyIDs) 
    {
    	if ($iSurveyIDs)
    	{
    		$query->where("q.sid IN (".implode(", ", $iSurveyIDs).")");
    	}
    	else
    	{
    		$query->join("{{surveys}} s", "s.sid=q.sid and s.active='Y'");
    	}
    }
       
}

?>
