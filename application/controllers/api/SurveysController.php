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
    	$sTitle = $_POST['title'];
        $sTemplate = "default";
        Yii::app()->loadHelper("surveytranslator");
        $aInsertData = array(
        'template' => $sTemplate,
        'owner_id' => 1, //Yii::app()-> session['loginID'],
        'active' => 'N'
        );
		$xssfilter = false;
        $iNewSurveyid = Survey::model()->insertNewSurvey($aInsertData, $xssfilter);
        if (!$iNewSurveyid){
        	echo 0;
			return;
        }
        
        $sTitle = html_entity_decode($sTitle, ENT_QUOTES, "UTF-8");
        $aInsertData = array('surveyls_survey_id' => $iNewSurveyid,
        'surveyls_title' => $sTitle,
        'surveyls_language' => 'en'
        );
        $langsettings = new Surveys_languagesettings;
        $langsettings->insertNewSurvey($aInsertData, $xssfilter);
		echo CJSON::encode(array('surveyid'=>$iNewSurveyid));
		Yii::app()->end();
    }
}
