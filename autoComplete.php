<?php
/**
 * autocomplete via csv file for public survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017-2020 Denis Chenu <www.sondages.pro>
 * @license AGPL v3
 * @version 1.5.7
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class autoComplete extends PluginBase
{

    static protected $description = 'Use devbridgeAutocomplete for short text question with CSV.';
    static protected $name = 'autoComplete';

    public function init()
    {
        if (Yii::app() instanceof CConsoleApplication) {
            return;
        }
        $this->subscribe('beforeQuestionRender','launchAutoComplete');
        $this->subscribe('newQuestionAttributes','addAutoCompleteAttribute');
        $this->subscribe('newDirectRequest');
        /* for ajax mode : need registering js file in all page … */
        $this->subscribe('beforeSurveyPage');
    }

    /**
    * Launch autocmplete for question
    */
    public function launchAutoComplete()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $oEvent=$this->getEvent();
        $qid = $oEvent->get('qid');
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
        if(isset($aAttributes['autoComplete']) && $aAttributes['autoComplete']){
            if(!$this->_getFileName($qid)) {
                return;
            }
            $filterBy = (isset($aAttributes['autoCompleteFilter']) && $aAttributes['autoCompleteFilter']) ? "{".trim($aAttributes['autoCompleteFilter'])."}" : "";
            if(version_compare(Yii::app()->getConfig("versionnumber"),"3.0.0",">=")) {
                $filterBy = LimeExpressionManager::ProcessString($filterBy,$oEvent->get('qid'));
            }
            if(!empty($filterBy)) {
                $filterBy = CHtml::tag("div",array(
                        'class'=>"hidden",
                        'style'=>"display:none",
                        'id'=>"AutoCompleteFilter".$qid,
                    ),$filterBy
                );
            }
            switch ($oEvent->get('type')) {
                case "S":
                    $sgq = $oEvent->get('surveyId')."X".$oEvent->get('gid')."X".$oEvent->get('qid');
                    $currentValue = $_SESSION['survey_'.$oEvent->get('surveyId')][$sgq]; // This can not broke : it's set in EM::_validateQuestion
                    $replaceValue = "";
                    if($currentValue && !$aAttributes['autoCompleteOneColumn']) {
                        $replaceValue = $this->_getCurrentString($qid,$currentValue);
                    }
                    $oneColumn = intval($aAttributes['autoCompleteOneColumn']);
                    break;
                case "Q":
                case ";":
                    $replaceValue = '';
                    $oneColumn = 1;
                    break;
                default:
                    return; // Not needed, can not be here
            };
            $oEvent->set("answers",$oEvent->get("answers").$filterBy);
            $placeholder = "";
            if(!empty($aAttributes['autoCompletePlaceholder'][Yii::app()->getLanguage()])) {
                $placeholder = $aAttributes['autoCompletePlaceholder'][Yii::app()->getLanguage()];
            }
            $minChar = intval($aAttributes['autoCompleteMinChar']);
            $minChar = ($minChar >= 0) ? $minChar : 1;
            $asDropDown = (bool) ($aAttributes['autoCompleteAsDropdown']);

            $dependentsString = $aAttributes['autoCompleteDependents'];
            $dependents = [];
            $depSGQs = [];
            if (!empty($dependentsString))
            {
              $auxD = preg_split ('/(\s*,\s*)*,+(\s*,\s*)*/', $dependentsString);
              while (count($auxD)) {
                  list($key,$value) = array_splice($auxD, 0, 2);
                  $dependents[trim($key)] = trim($value);
              }

              $LSApi = $this->pluginManager->getApi();
              foreach ($dependents as $QC => $col)
              {
                $SGQ = $LSApi->EMevaluateExpression('{'. $QC . '.sgqa}');
                $depSGQs[ $SGQ ] = $col;
              }
            }

            $options = array(
                "serviceUrl" => Yii::app()->getController()->createUrl('plugins/direct', array('plugin' => get_class($this),'function'=>'getData','qid'=>$oEvent->get('qid'))),
                "minChar" => $asDropDown ? 0 : intval($minChar),
                "asDropDown" => intval($asDropDown),
                "replaceValue" => $replaceValue,
                "oneColumn" => $oneColumn,
                "useCache" => intval(empty($filterBy) || version_compare ( App()->getConfig("versionnumber") , "3" , ">=" )), // For 3 and up version can use html:updated event
                "filterBy" => !empty($filterBy) ? "AutoCompleteFilter".$qid : false,
                "placeholder" => $placeholder,
                // "dependents" => $dependents,
                "depSGQs" => $depSGQs,
            );

            if($aAttributes['autoCompleteShowDefaultTip']) {
                switch ($minChar) {
                    case 0:
                        $tipText = gT("Choose one of the following answers");
                        break;
                    case 1:
                        $tipText = $this->_translate("Type a character");
                        break;
                    default:
                        $tipText = sprintf($this->_translate("Type %s characters"),$minChar);
                        break;
                }
                $questionStatus = LimeExpressionManager::GetQuestionStatus($qid);
                $tipsDatas = array(
                    'qid'       =>$qid,
                    'coreId'    =>"vmsg_{$qid}_autocomplete",
                    'coreClass' =>"ls-em-tip em_autocomplete",
                    'vclass'    =>'autocomplete',
                    'vtip'      =>$tipText,
                    'hideTip'   =>false,
                );
                $tip = Yii::app()->getController()->renderPartial('/survey/questions/question_help/em-tip', $tipsDatas, true);
                $tip = LimeExpressionManager::ProcessString($tip,$qid);
                $validTip = $questionStatus['validTip'] . $tip;
                $class = empty($aAttributes['hide_tip']) ? "" : " hide-tip";
                $oEvent->set("valid_message",doRender('/survey/questions/question_help/help', array('message'=>$validTip, 'classes'=>$class, 'id'=>"vmsg_{$qid}"), true));
            }

            switch ($oEvent->get('type')) {
                case "Q":
                case ";":
                    $script = "setAutoCompleteCodeWholeQuestion('".$qid."',".json_encode($options).");\n";
                    break;
                case "S":
                    $script = "setAutoCompleteCode('answer".$sgq."',".json_encode($options).");\n";
                    break;
                default:
                    return; // Not needed, already quit
            }
            $this->_registerPackage();
            App()->getClientScript()->registerScript("autoComplete{$oEvent->get('qid')}",$script,CClientScript::POS_END);
        }
    }

    public function newDirectRequest()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        if($this->getEvent()->get('target') != get_class($this)) {
            return;
        }
        /* Need test about $_SESSION survey ? */
        $qid = $this->api->getRequest()->getParam('qid');
        $oQuestion = Question::model()->find("qid =:qid", array(":qid"=>$qid));
        if(!$oQuestion) {
            $this->_renderJson();
        }
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
        if(empty($aAttributes['autoComplete'])){
            $this->_renderJson();
        }
        if(empty($aAttributes['autoCompleteCsvFile'])){
            $this->_renderJson();
        }
        $oQuestion = Question::model()->find("qid = :qid",array(":qid"=>$qid));

        $csvFile = trim($aAttributes['autoCompleteCsvFile']);
        if(strlen($csvFile) < 4 || strtolower(substr($csvFile, -4)) != ".csv") {
            $csvFile =  $csvFile.".csv";
        }
        $oneColumn = 1;
        if($oQuestion->type == "S") {
            $oneColumn = !empty($aAttributes['autoCompleteOneColumn']);
        }
        $suggestion = array();
        $completeFile = $this->_getFileName($qid);
        if(!$completeFile) {
            /* Except with hack can not happen */
            $suggestion[] = array("value"=> "Invalid file for this question", "data"=>"invalid");
            $this->_renderSuggestion($suggestion);
        }
        $filter = $this->api->getRequest()->getParam('filter');
        $search = $this->api->getRequest()->getParam('query');
        $asDropDown = intval($aAttributes['autoCompleteAsDropdown']);
        if($asDropDown) {
            $search = "";
        }
        $search = $this->_removeSpecialCharacter($search);

        $handle = fopen($completeFile, "r");
        $headerDone = false;
        while (($line = fgetcsv($handle, 10000, ",")) !== false) {
            if (!$headerDone) {
                $headerDone = true;
                continue;
            }
            $data = $line[0];
            $value = isset($line[1]) ? $line[1] : "";
            $searchValue = $this->_removeSpecialCharacter($value);
            if($oneColumn) {
                $searchValue = $this->_removeSpecialCharacter($data);
            }
            if(empty($filter) || substr($data, 0, strlen($filter)) == $filter) {
                if($oneColumn) {
                    if(!$search || strpos($searchValue,$search)!==false) {
                        $suggestion[] = array(
                            'data'=>$data,
                            'value'=>$data,
                            'line'=>$line,
                        );
                    }
                } else {
                    if(!$search || strpos($searchValue,$search)!==false) {
                        $suggestion[] = array(
                            'data'=>$data,
                            'value'=>$value,
                            'line'=>$line,
                        );
                    }
                }
            }
        }
        fclose($handle);
        $this->_renderSuggestion($suggestion);
    }

    /**
     * Just to load package when ajax is set : @todo : deprecate
     */
    public function beforeSurveyPage()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $criteria = new CDbCriteria;
        $criteria->compare('sid',$this->getEvent()->get('surveyId'));
        $criteria->addInCondition('type',array('S','M',';'));

        $aQuestionShorttextInSurvey = CHtml::listData(
            Question::model()->findAll($criteria),
            'qid',
            'qid'
        );
        if(!empty($aQuestionShorttextInSurvey)) {
            $attributeCriteria = new CDbCriteria;
            $attributeCriteria->compare('attribute','autoComplete');
            $attributeCriteria->compare('value','1');
            $attributeCriteria->addInCondition('qid',$aQuestionShorttextInSurvey);
            if(QuestionAttribute::model()->count($attributeCriteria)) {
                $this->_registerPackage();
            }
        }
    }
    /**
     * render json for autocomplete
     * @param string[][]
     * @return void
     */
    private function _renderSuggestion($suggestion) {
        $this->_renderJson(array(
            "suggestions" =>$suggestion,
        ));
    }

    /**
     * render json
     * @param mixed
     * @return void
     */
    private function _renderJson($data=null) {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($data);
        Yii::app()->end();
    }

    /**
     * Find current text to be shown
     * @param integer question id
     * @param string value
     * @return $string
     */
    private function _getCurrentString($qid,$value) {
        $completeFile = $this->_getFileName($qid);
        if(!$completeFile) {
            return;
        }
        $handle = fopen($completeFile, "r");
        $headerDone = false;
        $string = "";
        while (($line = fgetcsv($handle, 10000, ",")) !== false) {
            if (!$headerDone) {
                $headerDone = true;
                continue;
            }
            $data = $line[0];
            if($data == $value) {
                $string = $line[1];
                break;
            }
        }
        fclose($handle);
        return $string;
    }
    /**
    * The attribute, use readonly for 3.X version
    */
    public function addAutoCompleteAttribute()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $autoCompleteAttributes = array(
            'autoComplete' => array(
                'types'     => 'SQ;',//'!S', /* List radio and short text */
                'category'  => $this->_translate('AutoComplete'),
                'sortorder' => 1,
                'inputtype' => 'switch',
                'default'   => 0,
                'caption' => $this->_translate("Use autocomplete"),
            ),
            /* @todo : add more source : 1. label (easy) , 2. plugin table : need a GUI for table update */
            'autoCompleteCsvFile'=>array(
                'types'=>'SQ;', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>100,
                'inputtype'=>'text',
                'default'=>'', /* not needed (it's already the default) */
                'help'=>$this->_translate("The CSV file must be in this survey files directory (ex: upload/surveys/123456). It will be read in UTF8 with comma \",\" as separator."),
                'caption'=>$this->_translate('CSV file to be used'),
            ),
            'autoCompleteOneColumn'=>array(
                'types'=>'S', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>110,
                'inputtype'=>'switch',
                'default'=>1,
                'help'=>$this->_translate("Complete from first column in the csv file or from the second one."),
                'caption'=>$this->_translate('Use only one column'),
            ),
            'autoCompleteFilter'=>array(
                'types'=>'SQ;',//'!S', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>120,
                'inputtype'=>'text',
                'expression'=>2, // Forced expression
                'default'=>'', /* not needed (it's already the default) */
                'help'=>$this->_translate("Enter the expression for filtering, filter is done on first column, return only line where 1st column code start by this current value."),
                'caption'=>$this->_translate('Filter by (expression)'),
            ),
            'autoCompleteMinChar'=>array(
                'types'=>'SQ;',//'!S', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>130,
                'inputtype'=>'integer',
                'default'=>1,
                'help'=>"",//$this->_translate("Enter the expression manager for filtering, filter is done on first column, return line where code start by this value."),
                'caption'=>$this->_translate('Minimum character to start search'),
                'min'=>0,
            ),
            /* @todo review according to https://github.com/devbridge/jQuery-Autocomplete/issues/155 */
            'autoCompleteRemoveSpecialChar'=>array(
                'types'=>'SQ;',//'!S', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>130,
                'inputtype'=>'switch',
                'default'=>1,
                'help'=>$this->_translate("When searching : line search returned was done in lowercase and without any special character."),
                'caption'=>$this->_translate('Do search with lower case and without special character.'),
            ),
            'autoCompleteAsDropdown'=>array(
                'types'=>'SQ;',
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>150,
                'inputtype' => 'switch',
                'default' => 1,
                'help'=>$this->_translate("If you want to use autocomplete like a dropdown, user can only select value. Without this option : user can write anything in input."),
                'caption'=>$this->_translate('Show autocomplete as dropdown (no user input).'),
            ),
            'autoCompleteShowDefaultTip'=>array(
                'types'=>'SQ;',
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>200,
                'inputtype' => 'switch',
                'default' => 0,
                'help'=>sprintf($this->_translate("For show as dropddow : default is same than limesurvey dropdown. Else can be translated at %s."),"<a href='https://translate.sondages.pro/projects/'>translate.sondages.pro</a>."),
                'caption'=>$this->_translate('Show thed efault tip.'),
            ),
            'autoCompletePlaceholder'=>array(
                'types'=>'SQ;',
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>210,
                'inputtype' => 'text',
                'i18n'=>true,
                'default' => "",
                'help'=>sprintf($this->_translate("Add this string as placeholder to the input HTML, this placeholder are shown when input is empty.")),
                'caption'=>$this->_translate('Place holder.'),
            ),
            'autoCompleteDependents'=>array(
                'types'=>'S', /* Short text */
                'category'=>$this->_translate('AutoComplete'),
                'sortorder'=>305,
                'inputtype'=>'text',
                'default'=>'', /* not needed (it's already the default) */
                'help'=>$this->gT("Enter qcodes and column numbers separated by commas. Ex: QCode1,2,QCode2,3"),
                'caption'=>$this->gT('Dependent questions'),
            ),
        );
        if(method_exists($this->getEvent(),'append')) {
          $this->getEvent()->append('questionAttributes', $autoCompleteAttributes);
        } else {
          $questionAttributes=(array)$this->event->get('questionAttributes');
          $questionAttributes=array_merge($questionAttributes,$autoCompleteAttributes);
          $this->event->set('questionAttributes',$questionAttributes);
        }
    }

    private function _registerPackage()
    {
        Yii::setPathOfAlias('autoComplete', dirname(__FILE__));
        Yii::setPathOfAlias(get_class($this),dirname(__FILE__));
        $min = (App()->getConfig('debug')) ? '.min' : '';

        /* Add package if not exist (LimeSurvey 3 have own devbridge-autocomplete) */
        if(!Yii::app()->clientScript->hasPackage('devbridge-autocomplete')) { // Not tested with 3 and older devbridge-autocomplete
            Yii::app()->clientScript->addPackage('devbridge-autocomplete', array(
                'basePath'    => get_class($this).'.assets.devbridge-autocomplete',
                'js'          => array('jquery.autocomplete'.$min.'.js'),
                'depends'      =>array('jquery'),
            ));
        }
        if(!Yii::app()->clientScript->hasPackage('limesurvey-autocomplete')) {
            Yii::app()->clientScript->addPackage('limesurvey-autocomplete', array(
                'basePath'    => get_class($this).'.assets.limesurvey-autocomplete',
                'js'          => array('limesurvey-autocomplete.js'),
                'css'          => array('limesurvey-autocomplete.css'),
                'depends'      =>array('devbridge-autocomplete'),
            ));
        }
        /* Registering the package */
        Yii::app()->getClientScript()->registerPackage('limesurvey-autocomplete');
    }

    private function _translate($string, $sEscapeMode = 'unescaped', $sLanguage = NULL) {
        if(intval(Yii::app()->getConfig('versionnumber')) >= 3) {
            return parent::gT($string, $sEscapeMode, $sLanguage );
        }
        return $string;
    }

    /**
     * @inheritdoc
     * Adding message to vardump if user activate debug mode
     */
    public function log($message, $level = \CLogger::LEVEL_TRACE)
    {
        if(intval(Yii::app()->getConfig('versionnumber')) >= 3) {
            parent::log($message, $level);
        }
        /* To be in web log with debug */
        Yii::log("[".get_class($this)."] ".$message, $level, 'vardump');
        /* SP system */
        Yii::log($message, $level,'application.plugins.'.get_class($this));
    }

    /**
     * return complete filename
     * @param integer question id
     * @return string|false
     */
    private function _getFileName($qid) {
        static $filename = array();
        if(isset($filename[$qid])) {
            return $filename[$qid];
        }
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);

        if(empty($aAttributes['autoCompleteCsvFile'])){
            $filename[$qid] = false;
            return $filename[$qid];
        }
        $oQuestion = Question::model()->find("qid = :qid",array(":qid"=>$qid));
        $csvFile = sanitize_filename($aAttributes['autoCompleteCsvFile'], false, false, false);
        if(strlen($csvFile) < 4 || strtolower(substr($csvFile, -4)) != '.csv') {
            $csvFile = $csvFile.".csv";
        }
        /* Try to read csv file */
        $completeFile = App()->getConfig("uploaddir")."/surveys/".$oQuestion->sid."/files/".$csvFile;
        if(!is_file($completeFile)) {
            $this->log("file $csvFile not found in survey directory : $completeFile",\CLogger::LEVEL_ERROR);
            return false;
        }
        if(!is_readable($completeFile)) {
            $this->log("file $csvFile found but unreadbale",\CLogger::LEVEL_ERROR);
            return false;
        }
        $filename[$qid] = $completeFile;
        return $filename[$qid];
    }

    /**
     * return a string without any special charater
     * @param string
     * @return string
     */
    private function _removeSpecialCharacter($string)
    {
        if(empty($string)) {
            return $string;
        }
        if(class_exists('Transliterator')) { /* @todo : check if we really need to test */
            /* @see https://www.matthecat.com/supprimer-les-accents-dune-chaine-en-php/ */
            $transliterator = Transliterator::createFromRules("::Latin-ASCII; ::Lower; [^[:L:][:N:]]+ > '-';");
            return trim($transliterator->transliterate($string),'-');
        }
        $string = mb_strtolower($string, 'UTF-8');
        $string = str_replace(
            array(
                'à', 'â', 'ä', 'á', 'ã', 'å',
                'î', 'ï', 'ì', 'í',
                'ô', 'ö', 'ò', 'ó', 'õ', 'ø',
                'ù', 'û', 'ü', 'ú',
                'é', 'è', 'ê', 'ë',
                'ç', 'ÿ', 'ñ',
                'œ',
            ),
            array(
                'a', 'a', 'a', 'a', 'a', 'a',
                'i', 'i', 'i', 'i',
                'o', 'o', 'o', 'o', 'o', 'o',
                'u', 'u', 'u', 'u',
                'e', 'e', 'e', 'e',
                'c', 'y', 'n',
                'oe',
            ),
            $string
        );
        return $string;
    }
}
