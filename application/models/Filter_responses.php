<?php
// not a CActiveRecord or CModel b/c there isn't one survey table w/ set attributes
// assumes all survey tables in filters have tokens

// known issues - 
//   no way to filter unanswered numerics (value is null in db)
class Filter_responses {

    public function getResponses($filtersBySurveyId)
    {
    	$query = Yii::app()->db->createCommand();    	
    	$tables = array();
    	$columns = array();
    	$fromAlias = null;
    	
		foreach ($filtersBySurveyId as $sid => $filters) {			
			foreach ($filters as $filter) {
				$tableAlias = "s$sid";
				$column = $tableAlias.'.'.$this->getColumnName($filter);
				if ($this->addConditions($query, $column, $filter)) {					
					if (!$fromAlias) {						
						$tables[] = $sid;
						$fromAlias = $tableAlias;
						$columns[] = $fromAlias.'.token'; 
						$query->from("{{survey_$sid}} $tableAlias");
					} else if (!in_array($sid, $tables)) {
						$tables[] = $sid;
						$query->join("{{survey_$sid}} $tableAlias", "$tableAlias.token=$fromAlias.token");
					}
					array_push($columns, $column.' AS c'.$filter->cid);
				}				
			}
		}
		$query->select($columns);
		
    	if ($fromAlias) {
    		echo $query->getText();
    		return $query->queryAll();
    	} else {
    		return array();
    	}    	
    }

    // returns true if added, otherwise false
    private function addConditions($query, $column, $filter) {
    	$added = false;
    	
    	switch ($filter->type) {
    		case 'N':
    			if ($filter->values) {
    				$values = array_map(function ($string) { return (int)$string; }, $filter->values);
    				$query->where(array('in', $column, $values));
    				$added = true;
    			}
    			break;
    		case '~':
    		case 'S':
    		case 'T':
    			if ($filter->values) {
    				$query->where(array('or like', $column, $filter->values));
    				$added = true;
    			}
    			break;
    		case 'F':
    		case 'L':
    		case 'M':
    		case 'Y':
    			if ($filter->codes) {
    				// the where 'or' option has a different syntax which is more difficult than this, and we need to quote and escape it anyway
    				$codes = array_map(function ($string) { return Yii::app()->db->quoteValue($string); }, $filter->codes);
    				$query->where('('.$column."=".implode(" OR ".$column."=", $codes).")");
    				$added = true;
    			}
    			break;
    	}
    	return $added;
    }
    
	private function getColumnName($filter) {
		$sid = $filter->sid;
		$gid = $filter->gid;
		$qid = $filter->qid;
		$name = $filter->name;
		$type= $filter->type;
		
		switch ($type) {
			case 'L':
			case 'N':
			case 'S':
			case 'T':
			case 'Y':
				return $sid.'X'.$gid.'X'.$qid;
			case '~': // this type added for filtering (in Filter_questions model)
				return $sid.'X'.$gid.'X'.$qid.'other';
			case 'F':
			case 'M':
				return $sid.'X'.$gid.'X'.$qid.$name;
			default:
				throw new Exception("Unhandled type '$type'");
		}			
	}
    
}

?>
