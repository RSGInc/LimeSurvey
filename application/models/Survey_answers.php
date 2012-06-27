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
    	$multiSql = $this->getMultipleChoiceAnswersQuery();
    	$yesNoSql = $this->getYesNoAnswersSql();

		$unioned = "SELECT a.* FROM (".
						$answerSql->getText().
					" UNION ".
						$multiSql->getText().
					" UNION ".
						$yesNoSql.
					") a";
		if ($iSurveyIDs)
		{
			$unioned .= " WHERE a.sid IN (".implode(", ", $iSurveyIDs).")";
		}
		else
		{
			$unioned .= " JOIN {{surveys}} s ON s.sid=a.sid and s.active='Y'";
		}
		$unioned .= " ORDER BY a.sid, a.qid, a.`order`";
		
    	return Yii::app()->db->createCommand($unioned)->queryAll();		 
    }

    private function getSimpleAnswersQuery() 
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, a.qid, a.code, a.answer defaulttext, a.sortorder order");
    	$query->from("{{answers}} a");
    	$query->join("{{questions}} q",  "q.qid=a.qid");
    	return $query;    	 
    }
    
    private function getMultipleChoiceAnswersQuery()
    {
    	$query = Yii::app()->db->createCommand();
    	$query->select("q.sid, q.qid, q.title code, q.question defaulttext, q.question_order order");
    	$query->from("{{questions}} q");
    	$query->join("{{questions}} parent", "q.parent_qid=parent.qid AND parent.type='M'");
    	return $query;
    }
    
    private function getYesNoAnswersSql()
    {
    	$query = "SELECT * FROM (
      		SELECT q.sid, q.qid, ('Y') code, ('Yes') defaulttext, (1) `order` FROM {{questions}} q WHERE q.type='Y'
      			UNION 
      		SELECT q.sid, q.qid, ('N') code, ('No') defaulttext, (2) `order` FROM {{questions}} q WHERE q.type='Y'
      			UNION
      		SELECT q.sid, q.qid, ('') code, ('No answer') defaulttext, (3) `order` FROM {{questions}} q WHERE q.type='Y'
      	) yesno";
    		
    	return $query;
    }

    
}

?>
