<?php


class SurveysController extends BaseAPIController
{
	
	protected function _init()
    {
    	parent::_init();
		Yii::import('application.libraries.Limesurvey_lang'); 		
	}

    public function actionCopy(){
    	header('Content-type: application/json');
        @$iSurveyID = $_POST['sid'];
        if (@$_POST['copysurveytranslinksfields'] == "on" || @$_POST['translinksfields'] == "on")
        {
            $sTransLinks = true;
        }
        $iSurveyID = sanitize_int($_POST['copysurveylist']);
        $exclude = array();
        $sNewSurveyName = $_POST['copysurveyname'];
		Yii::app()->setLang(new Limesurvey_lang('en'));
		$clang = Yii::app()->lang;
    	$group['Arrays'] = $clang->gT('Arrays');
		 
        if (!$iSurveyID)
        {
            echo 0;
			return;
        }
        Yii::app()->loadHelper('export');
        $copysurveydata = surveyGetXMLData($iSurveyID, $exclude);
        Yii::app()->loadHelper('admin/import');
        if (empty($importerror) || !$importerror)
        {
            $aImportResults = XMLImportSurvey('', $copysurveydata, $sNewSurveyName);
            if (!isset($exclude['permissions']))
            {
                Survey_permissions::model()->copySurveyPermissions($iSurveyID,$aImportResults['newsid']);
            }
        }
        else
        {
            $importerror = true;
        }
		echo CJSON::encode(array('surveyid'=>$aImportResults['newsid']));
 		Yii::app()->end();
    }
    
	public function actionCreate()
    {
    	header('Content-type: application/json');

        Yii::app()->loadHelper("surveytranslator");

    	$sTitle = $_POST['title'];
        $aInsertData = array(
        'template' => "default",
        'owner_id' => 1, //Yii::app()-> session['loginID'],
        'active' => 'N',
        'allow_dynamic_tokens' => 'Y',
        'usetokens' => 'Y',
        'language' => 'en'
        );
		$xssfilter = false;
        $iNewSurveyid = Survey::model()->insertNewSurvey($aInsertData, $xssfilter);
        if (!$iNewSurveyid)
        {
        	echo 0;
			return;
        }
        
        $sTitle = html_entity_decode($sTitle, ENT_QUOTES, "UTF-8");
        $aInsertData = array(
            'surveyls_survey_id' => $iNewSurveyid,
            'surveyls_title' => $sTitle,
            'surveyls_language' => 'en'
        );
        $langsettings = new Surveys_languagesettings;
        $langsettings->insertNewSurvey($aInsertData, $xssfilter);
		echo CJSON::encode(array('sid'=>$iNewSurveyid));
		Yii::app()->end();
    }
	
	public function actionExport(){
		$iSurveyID = (int)$_POST['sid'];
		Yii::app()->setLang(new Limesurvey_lang('en'));
		$clang = Yii::app()->lang;
		Yii::app()->loadHelper("admin/exportresults");
		
		$options = new FormattingOptions();
		
		$excesscols = createFieldMap($iSurveyID,'full',false,false,getBaseLanguageFromSurveyID($iSurveyID));
        $excesscols = array_keys($excesscols);
		$options->selectedColumns = $excesscols;
		$surveybaselang = Survey::model()->findByPk($iSurveyID)->language;
        $exportoutput = "";
		$explang = $surveybaselang;
		
		$options->headingFormat = "full";
		$options->responseCompletionState = "show";
		$options->answerFormat = "long";
		$options->format = 'json';
		$resultsService = new ExportSurveyResultsService();
        $resultsService->exportSurvey($iSurveyID, $explang, $options);
	}
	
	public function actionActivate()
	{
		header('Content-type: application/json');
		
		$iSurveyID = $this->getSid();

		Yii::app()->setLang(new Limesurvey_lang('en'));
			
        $surveyInfo = getSurveyInfo($iSurveyID);
		
        if (!isset($surveyInfo['active']) || $surveyInfo['active'] == 'Y')
        {
        	$this->handleError(500, "Survey activity not set or already active.");
		}
		
        Yii::app()->loadHelper("admin/activate");
		
        $survey = Survey::model()->findByAttributes(array('sid' => $iSurveyID));
        
        if (!is_null($survey))
        {
            $survey->anonymized = false;
            $survey->datestamp = true;
            $survey->ipaddr = true;
            $survey->refurl = true;
            $survey->savetimings = true;
            $survey->save();
        }
        activateSurvey($iSurveyID);		
		echo CJSON::encode(array('success'=>'true'));
		Yii::app()->end();
	}
	
	/**
	 * Function responsible to deactivate a survey.
	 *
	 * @access public
	 * @param int $iSurveyID
	 * @return void
	 */
	// CRAIG this fails if already deactivated - is that a problem?
	public function actionDeactivate()
	{
		$iSurveyID = $this->getSid();
	
		Yii::app()->setLang(new Limesurvey_lang('en'));
		
		// CRAIG what is the difference? Do we need both?
		//$postsid = Yii::app()->request->getPost('sid', $iSurveyID);
		$postsid = $iSurveyID;
		
		$date = date('YmdHis'); //'Hi' adds 24hours+minutes to name to allow multiple deactiviations in a day
	
		if (empty($_POST['ok']))
		{
			$aData['surveyid'] = $iSurveyID;
			$aData['date'] = $date;
			$aData['dbprefix'] = Yii::app()->db->tablePrefix;
			$aData['step1'] = true;
		}
		else
		{
			
			//See if there is a tokens table for this survey
			if (Yii::app()->db->schema->getTable("{{tokens_{$postsid}}}"))
			{
				if (Yii::app()->db->getDriverName() == 'postgre')
				{
					$deactivateresult = Yii::app()->db->createCommand()->renameTable($toldtable . '_tid_seq', $tnewtable . '_tid_seq');
					$setsequence = "ALTER TABLE ".Yii::app()->db->quoteTableName($tnewtable)." ALTER COLUMN tid SET DEFAULT nextval('{{{$tnewtable}}}_tid_seq'::regclass);";
					$deactivateresult = Yii::app()->db->createCommand($setsequence)->query();
					$setidx = "ALTER INDEX {{{$toldtable}}}_idx RENAME TO {{{$tnewtable}}}_idx;";
					$deactivateresult = Yii::app()->db->createCommand($setidx)->query();
				}
	
				$toldtable = "{{tokens_{$postsid}}}";
				$tnewtable = "{{old_tokens_{$postsid}_{$date}}}";
	
				$tdeactivateresult = Yii::app()->db->createCommand()->renameTable($toldtable, $tnewtable);
	
				$aData['tnewtable'] = $tnewtable;
				$aData['toldtable'] = $toldtable;
			}
	
			// IF there are any records in the saved_control table related to this survey, they have to be deleted
			$result = Saved_control::model()->deleteSomeRecords(array('sid' => $postsid)); //Yii::app()->db->createCommand($query)->query();
			$oldtable = "{{survey_{$postsid}}}";
			$newtable = "{{old_survey_{$postsid}_{$date}}}";
	
			//Update the auto_increment value from the table before renaming
			$new_autonumber_start = 0;
			$query = "SELECT id FROM ".Yii::app()->db->quoteTableName($oldtable)." ORDER BY id desc LIMIT 1";
			$result = Yii::app()->db->createCommand($query)->query();
			if ($result->getRowCount() > 0)
			{
				foreach ($result->readAll() as $row)
				{
					if (strlen($row['id']) > 12) //Handle very large autonumbers (like those using IP prefixes)
					{
						$part1 = substr($row['id'], 0, 12);
						$part2len = strlen($row['id']) - 12;
						$part2 = sprintf("%0{$part2len}d", substr($row['id'], 12, strlen($row['id']) - 12) + 1);
						$new_autonumber_start = "{$part1}{$part2}";
					}
					else
					{
						$new_autonumber_start = $row['id'] + 1;
					}
				}
			}
	
			$condn = array('sid' => $iSurveyID);
			$insertdata = array('autonumber_start' => $new_autonumber_start);
	
			$survey = Survey::model()->findByAttributes($condn);
			$survey->autonumber_start = $new_autonumber_start;
			$survey->save();
			if (Yii::app()->db->getDrivername() == 'postgre')
			{
				$deactivateresult = Yii::app()->db->createCommand()->renameTable($oldtable . '_id_seq', $newtable . '_id_seq');
				$setsequence = "ALTER TABLE $newtable ALTER COLUMN id SET DEFAULT nextval('{$newtable}_id_seq'::regclass);";
				$deactivateresult = Yii::app()->db->createCommand($setsequence)->execute();
			}
	
			$deactivateresult = Yii::app()->db->createCommand()->renameTable($oldtable, $newtable);
	
			$insertdata = array('active' => 'N');
			$survey->active = 'N';
			$survey->save();
	
			$prow = Survey::model()->find('sid = :sid', array(':sid' => $postsid));
			if ($prow->savetimings == "Y")
			{
				$oldtable = "{{survey_{$postsid}_timings}}";
				$newtable = "{{old_survey_{$postsid}_timings_{$date}}}";
	
				$deactivateresult2 = Yii::app()->db->createCommand()->renameTable($oldtable, $newtable);
				$deactivateresult = ($deactivateresult && $deactivateresult2);
			}
	
			$aData['surveyid'] = $iSurveyID;
			$aData['newtable'] = $newtable;
		}
	
		//$this->_renderWrappedTemplate('survey', 'deactivateSurvey_view', $aData);
		$aData["message"] = "Deactivated";
		echo json_encode($aData);
	}
	
	private function getSid($required = true) {
		$sid = (int)$_POST['sid'];
		if ($required && !$sid) {
			$this->handleError(400, "Missing sid parameter.");
		}
		return $sid;		
	}
	
}
