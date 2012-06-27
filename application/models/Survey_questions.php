<?php

/*
 * extTypes:
 *  A answers supplied
 *  N numeric
 *  T text
 * 
 * supported internal types:
 * 	L list radio
 *  F array
 *  N numeric
 *  Y yes/no
 *  T long free text
 *  S short free text
 *  M multiple choice
 *  ~ other (for all 'Other' questions, the ~ is a new type key for filtering)
 */

class Survey_questions extends CActiveRecord{
	
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return '{{questions}}';
	}

	// ordering and subordering are relative only - there are gaps		
    function getQuestionsForSurveys($iSurveyIDs = array())
    {    	   	
    	$questionSql = $this->getQuestionQuery();
    	$this->joinSurveys($questionSql, $iSurveyIDs);

    	$subQuestionSql = $this->getSubQuestionQuery();
    	$this->joinSurveys($subQuestionSql, $iSurveyIDs);
    	
    	$otherQuestionSql = $this->getOtherQuestionQuery();
    	$this->joinSurveys($otherQuestionSql, $iSurveyIDs);
    	 
    	// couldn't figure out how to get setUnion to work like this...
		$unioned = "SELECT * FROM (".
						$questionSql->getText().
					" UNION ".
						$subQuestionSql->getText().
					" UNION ".
						$otherQuestionSql->getText().
					") a ORDER BY sid, ordering, subordering";
    	return Yii::app()->db->createCommand($unioned)->queryAll();		 
    }

    private function getExternalTypeSql($typeCol) 
    {
    	$answerTypes = array('F', 'L', 'M', 'Y');
    	$textTypes = array('S', 'T');
    	// N for numeric below

    	return "(CASE $typeCol WHEN '".
    		implode("' THEN 'A' WHEN '", $answerTypes)."' THEN 'A' WHEN '".
    		implode("' THEN 'T' WHEN '", $textTypes)."' THEN 'T' WHEN '".
    		"N' THEN 'N' END".
    	") extType";  	
    }
    
    
    private function getQuestionQuery() 
    {	
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.gid, q.type, ".$this->getExternalTypeSql('q.type').", q.title name, 
    					q.question description, ('') context, question_order ordering, (0) subordering");
    	$query->from("{{questions}} q");
    
    	// not including F (array questions) - included in SubQuestions
    	$query->where("q.parent_qid=0 AND type IN ('L', 'N', 'Y', 'T', 'S', 'M')");
    	return $query;
    }
    
    private function getSubQuestionQuery()
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.gid, parent.type, ".$this->getExternalTypeSql('parent.type').", q.title name, q.question description, parent.question context, parent.question_order ordering, q.question_order subordering"); 
    	$query->from("{{questions}} q");
    	$query->join("{{questions}} parent",  "q.parent_qid=parent.qid AND parent.type='F'");
    	return $query;
    }
    
    // returning as new type - '~'
    private function getOtherQuestionQuery()
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.gid, ('~') type, ('T') extType, CONCAT(q.title, 'Other') name, ('Other') description, q.question context, q.question_order ordering, (MAX(IFNULL(children.question_order,0)) + 1) subordering"); 
    	$query->from("{{questions}} q");
    	$query->leftJoin("{{questions}} children",  "q.qid=children.parent_qid");
    	$query->where("q.other='Y'");
    	$query->group("q.qid");
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
