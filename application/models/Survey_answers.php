<?php

// returns answers for all answer types as specified in 
//   Survey_questions::getExternalTypeSql() as type 'A'
class Survey_answers extends CActiveRecord
{

	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	public function tableName()
	{
		return '{{answers}}';
	}

    function getAnswersForSurveys($iSurveyIDs = array())
    {
    	$answerSql = $this->getSimpleAnswersQuery();
    	$this->joinSurveys($answerSql, $iSurveyIDs);
    	 
    	$multiSql = $this->getMultipleChoiceAnswersQuery();
    	$this->joinSurveys($multiSql, $iSurveyIDs);
    	 
    	$yesNoSql = $this->getYesNoAnswersQuery();
    	$this->joinSurveys($yesNoSql, $iSurveyIDs);
    	 
		$unioned = "SELECT * FROM (".
						$answerSql->getText().
					" UNION ".
						$multiSql->getText().
					" UNION ".
						$yesNoSql->getText().
					") a ORDER BY sid, ordering, subordering";
    	return Yii::app()->db->createCommand($unioned)->queryAll();		 
    }

    private function getSimpleAnswersQuery() 
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("a.qid id, a.code, a.answer defaulttext, a.sortorder `order`");
    	$query->from("{{answers}} a");
    	$query->join("{{questions}} q",  "q.qid=a.qid");
    	return $query;    	 
    }
    
    private function getMultipleChoiceAnswersQuery()
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("qid id, q.title code, q.question defaulttext, q.question_order `order`");
    	$query->from("{{questions}} q");
    	$query->join("{{questions}} parent ON q.parent_qid=parent.qid AND parent.type='M'");
    	return $query;
    }
    
    private function getYesNoAnswersQuery()
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("qid id, q.title code, q.question defaulttext, q.question_order `order`");
    	$query->from("{{questions}} q");
    	$query->join("{{questions}} parent ON q.parent_qid=parent.qid AND parent.type='M'");
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
