<?php
class Survey_filter {

    public function getSurveyResponses($filtersBySurveyId)
    {
    	$query = Yii::app()->db->createCommand();    	
    	$aliasCount = 0;
    	$fromAlias = false;
    	$cols = array();
    	
		foreach ($filtersBySurveyId as $sid => $filters) {
			$surveyAlias = 's'.$aliasCount++;
			$joined = false;

			foreach ($filters as $filter) {
				$column = $surveyAlias.'.'.$this->getColumnName($filter);
				if ($addConditions($query, $column, $filter)) {
					$haveFilter = true;
					if (!$fromAlias) {
						$fromAlias = $surveyAlias;
						$query->from("{{survey_$sid}} ".$fromAlias);
					} else if (!$joined) {
						$query->join("{{survey_$sid}} $surveyAlias ON $surveyAlias.token=$fromAlias.token");
						$joined = true;
					}
					// set alias as something recognizable and unique
					array_push($cols, $column.' AS '.$filter->name.'_'.$filter->qid);
				}				
			}
		}
		$query->select($columns);
		
    	if ($fromAlias) {
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
    				$values = array_map($this->toint, $filter->values);
    				$query->where($column.' IN ('.implode(',', $values).')');
    				$added = true;
    			}
    			break;
    		case '~':
    		case 'S':
    		case 'T':
    			if ($filter->values) {
    				$query->where('('.$column." LIKE '".implode("' OR ".$column." LIKE '", $filter->values)."')");
    				$added = true;
    			}
    			break;
    		case 'F':
    		case 'L':
    		case 'M':
    		case 'Y':
    			if ($filter->codes) {
    				$query->where('('.$column."='".implode("' OR ".$column."='", $filter->codes)."')");
    				$added = true;
    			}
    			break;
    	}
    	return $added;
    }
    
    private function toint($string) {
    	return (int)$string;
    }
    
	private function getColumnName($filter) {
		switch ($filter->type) {
			case 'L':
			case 'N':
			case 'S':
			case 'T':
			case 'Y':
				return $sid.'X'.$gid.'X'.$qid;
			case '~': // this type added for filtering (in Survey_questions model)
				return $sid.'X'.$gid.'X'.$qid.'other';
			case 'F':
			case 'M':
				return $sid.'X'.$gid.'X'.$qid.$filter->name;
			default:
				throw new Exception("Unhandled type '$filter->type");
		}			
	}
    
}

?>
