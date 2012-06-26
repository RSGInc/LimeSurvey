<?php

class SurveysController extends BaseAPIController
{
    protected function _init()
    {
    	parent::_init();
		Yii::import('application.libraries.Limesurvey_lang'); 		
    }

    public function actionCopy()
    {
        @$sid = $_POST['sid'];
        if (@$_POST['copysurveytranslinksfields'] == "on" || @$_POST['translinksfields'] == "on")
        {
            $sTransLinks = true;
        }

        $exclude = array();
        $sNewSurveyName = $_POST['copysurveyname'];
        Yii::app()->setLang(new Limesurvey_lang('en'));
        $clang = Yii::app()->lang;
    	$group['Arrays'] = $clang->gT('Arrays');
		 
        if (!$sid)
        {
            echo 0;
            return;
        }
        Yii::app()->loadHelper('export');
        $copysurveydata = surveyGetXMLData($sid, $exclude);
        Yii::app()->loadHelper('admin/import');
        //Yii::app()->session['loginID'];
        if (empty($importerror) || !$importerror)
        {
            $aImportResults = XMLImportSurvey('', $copysurveydata, $sNewSurveyName);

            Survey_permissions::model()->copySurveyPermissions($sid,$aImportResults['newsid']);
        }
        else
        {
            $importerror = true;
        }
        
		echo CJSON::encode(array('sid'=>$aImportResults['newsid']));
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
            'alloweditaftercompletion' => 'Y',
            'usetokens' => 'Y',
            'language' => 'en',
            'format' => 'G'
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
        $excesscols = array_diff($excesscols, array("id", "Completed", "Last page seen", "Start language"));
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

            $sid = $this->params('sid');
            Yii::app()->setLang(new Limesurvey_lang('en'));
            $surveyInfo = getSurveyInfo($sid);		
            if (!isset($surveyInfo['active']) || $surveyInfo['active'] == 'Y')
            {
        	$this->handleError(500, "Survey activity not set or already active.");
            }
		
            Yii::app()->loadHelper("admin/activate");
                    
            $survey = Survey::model()->findByAttributes(array('sid' => $sid));
            
            if (!is_null($survey))
            {
                $survey->anonymized = false;
                $survey->datestamp = true;
                $survey->ipaddr = true;
                $survey->refurl = true;
                $survey->savetimings = true;
                $survey->save();
            } else {
                $this->handleError(500, "Survey not found");
            }
            activateSurvey($sid);		

            # we need the token table in order for the dynamic token creation hack to work...
            Yii::app()->loadHelper("admin/token");
            createTokenTable($sid);

            echo CJSON::encode(
                array('url' => $this->createAbsoluteUrl("/survey/index/sid/$sid"))
            );
            Yii::app()->end();
	}

    # CH 2012-6-12 removed actionDeactivate, as we determined that this would make our client code
    # more complex 
	
        /**
         * @param   Array   $sids   The array of surveys for which completion statuses
         *                          are being requested.
         * @returns JSON            The completion statuses for the requested surveys.
         */
        public function actionSummaries() 
        {
            $sids = $this->params("sids");
            $aData = Tokens_dynamic::summaries($sids);
            echo CJSON::encode($aData);
        }
	
        private function params($paramName, $required = true) 
        {
            $param = $_REQUEST[$paramName];
            if ($required && !$param) 
            {
                $this->handleError(400, "Missing $paramName parameter.");
            }
            return $param;		
	}
	
}
