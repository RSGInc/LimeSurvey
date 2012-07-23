<?php

define("CMI_USER_FULLNAME","CMI Staff");

class SurveysController extends BaseAPIController
{
    protected function _init()
    {
    	parent::_init();
		Yii::import('application.libraries.Limesurvey_lang'); 		
    }

    public function actionCopy()
    {
        $sid = $this->params('sid');
        //if ($this->params('copysurveytranslinksfields', false) == "on" || $this->params('translinksfields', false) == "on")
        //{
        //    $sTransLinks = true;
        //}
        $sid = sanitize_int($this->params('sid'));
        $exclude = array();
        $sNewSurveyName = $this->params('copysurveyname');
        Yii::app()->setLang(new Limesurvey_lang('en'));
        $clang = Yii::app()->lang;
    	$group['Arrays'] = $clang->gT('Arrays');
		 
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
        $this->renderJSON(array('sid'=>$aImportResults['newsid']));
    }
    
	public function actionCreate()
    {

        Yii::app()->loadHelper("surveytranslator");

    	$sTitle = $this->params("title");
        $aInsertData = array(
            'template' => "default",
            'owner_id' => 1, //Yii::app()-> session['loginID'],
            'active' => 'N',
            'allow_dynamic_tokens' => 'Y',
            'alloweditaftercompletion' => 'Y',
            'usetokens' => 'Y',
            'language' => 'en',
            'anonymized' => 'N',
            
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

        // HACK: add CMI to survey administrators with only survey edit permissions
        $cmi_user_id = User::model()->getID(CMI_USER_FULLNAME);

        $aPermRtnStatuses = Survey_permissions::model()->insertRecords(
            array(
                array(
                    'sid' => $iNewSurveyid, 
                    'uid' => $cmi_user_id, 
                    'permission' => 'survey', 
                    'read_p' => 1
                ),
                array(
                    'sid' => $iNewSurveyid, 
                    'uid' => $cmi_user_id, 
                    'permission' => 'surveycontent', 
                    'create_p' => 1,
                    'read_p' => 1,
                    'update_p' => 1,
                    'delete_p' => 1,
                    'import_p' => 1,
                    'export_p' => 1
                )
            )
        );

        foreach($aPermRtnStatuses as $status) {
            if(!$status){
        	$this->handleError(500, "Survey permissions for the ".CMI_USER_FULLNAME." could not be added to this survey.");
            }
        }

        $this->renderJSON(array('sid'=>$iNewSurveyid));
    }
	
	public function actionExport(){
		$iSurveyID = (int)$this->params('sid');
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
	
	public function actionActivate(){
	    
            $sid = $this->params('sid');
            Yii::app()->setLang(new Limesurvey_lang('en'));
            $surveyInfo = getSurveyInfo($sid);		
            if (!isset($surveyInfo['active']) || $surveyInfo['active'] == 'Y'){
        	   $this->handleError(500, "Survey activity not set or already active.");
            }
            Yii::app()->loadHelper("admin/activate");
            $survey = Survey::model()->findByAttributes(array('sid' => $sid));
            if (!is_null($survey))
            {
                $survey->anonymized = 'N';
                $survey->datestamp = 'Y';
                $survey->ipaddr = 'Y';
                $survey->refurl = 'Y';
                $survey->savetimings = 'Y';
                $survey->save();
            } else {
                $this->handleError(500, "Survey not found");
            }
            activateSurvey($sid);		
            # we need the token table in order for the dynamic token creation hack to work...
            Yii::app()->loadHelper("admin/token");
            createTokenTable($sid);
            $this->renderJSON(array('url' => $this->createAbsoluteUrl("/survey/index/sid/$sid")));
        
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
            $this->renderJSON($aData);
        }

        /**
         * @param   Array   $sids   The array of surveys for which completion statuses
         *                          are being requested.
         * @param   String  $token  The token record to be queried.
         * @returns JSON            The completion statuses for the requested surveys.
         */
        public function actionCompletes()
        {
            $sids = $this->params("sids");
            $token = $this->params("token");
            $aData = Tokens_dynamic::completes($sids, $token);

            $this->renderJSON($aData);
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

        private function renderJSON($aReturnVals) 
        {
            header('Content-type: application/json');
            echo CJSON::encode($aReturnVals);
            Yii::app()->end();
        }
}
