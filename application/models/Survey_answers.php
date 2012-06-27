<?php
/*
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 *	$Id$
 */
class Survey_answers extends CActiveRecord{
	/**
	 * Returns the static model of Settings table
	 *
	 * @static
	 * @access public
     * @param string $class
	 * @return CActiveRecord
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	/**
	 * Returns the setting's table name to be used by the model
	 *
	 * @access public
	 * @return string
	 */
	public function tableName()
	{
		return '{{answers}}';
	}

    function getAnswersForSurveys($iSurveyIDs = array())
    {
    	$sql = "select a.qid id, a.code, a.answer defaulttext, a.sortorder `order` from {{answers}} a
    		join {{questions}} q on a.qid=q.qid";
    	
    	if ($iSurveyIDs) 
    	{
    		$sql .= " where q.sid IN (".implode(", ", $iSurveyIDs).")";
    	} 
    	else 
    	{
    		$sql .= " join {{surveys}} s on s.sid=q.sid and s.active='Y'";
    	}

    	return Yii::app()->db->createCommand($sql)->queryAll();    			 
    }


}

?>
