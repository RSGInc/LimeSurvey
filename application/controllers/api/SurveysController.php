<?php


class SurveysController extends BaseAPIController
{
	
	protected function _init()
    {
    	parent::_init();
		Yii::import('application.libraries.Limesurvey_lang'); 		
	}

    public function actionCopy(){
    	
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
        Yii::app()->loadHelper("surveytranslator");

    	$sTitle = $_POST['title'];
        $aInsertData = array(
        'template' => "default",
        'owner_id' => 1, //Yii::app()-> session['loginID'],
        'active' => 'N',
        'allow_dynamic_tokens' => 'Y',
        'usetokens' => 'Y'
        );
		$xssfilter = false;
        $iNewSurveyid = Survey::model()->insertNewSurvey($aInsertData, $xssfilter);
        if (!$iNewSurveyid){
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
	
	public function actionActivate()
	{
		$iSurveyID = (int)$_POST['sid'];
		Yii::app()->setLang(new Limesurvey_lang('en'));
		$clang = Yii::app()->lang;
    	//$group['Arrays'] = $clang->gT('Arrays');
		
		if (!$iSurveyID)
        {
            echo CJSON::encode(array('success'=>'false'));
			Yii::app()->end();
        }
		
        $surveyInfo = getSurveyInfo($iSurveyID);
		
        if (!isset($surveyInfo['active']) || $surveyInfo['active'] == 'Y'){
            //$this->getController()->error('Survey not active');
            echo CJSON::encode(array('success'=>'false'));
			Yii::app()->end();
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
}
