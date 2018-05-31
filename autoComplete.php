<?php
/**
 * select2package add a package select2 for public survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2017 Denis Chenu <www.sondages.pro>
 * @license AGPL v3
 * @version 0.1.0
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

  static protected $description = 'Use devbridgeAutocomplete for single choice and short text (with CSV), tested on 2.73 only.';
  static protected $name = 'autoComplete';


  public function init()
  {
    $this->subscribe('beforeQuestionRender','launchAutoComplete');
    $this->subscribe('newQuestionAttributes','addAutoCompleteAttribute');
    $this->subscribe('newDirectRequest');
  }

  /**
   * Launch autocmplete for question
   */
  public function launchAutoComplete()
  {
    $oEvent=$this->getEvent();
    $qid = $oEvent->get('qid');
    $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
    if(isset($aAttributes['autoComplete']) && $aAttributes['autoComplete']){
        $this->_registerScript();
        /* This part for testing since can not reset single …*/
        //~ App()->getClientScript()->registerScriptFile(Yii::app()->request->getBaseUrl()."/plugins/autoComplete/assets/limesurvey-autocomplete/limesurvey-autocomplete.js");
        //~ App()->getClientScript()->registerCssFile(Yii::app()->request->getBaseUrl()."/plugins/autoComplete/assets/limesurvey-autocomplete/limesurvey-autocomplete.css");
        $sgq = $oEvent->get('surveyId')."X".$oEvent->get('gid')."X".$oEvent->get('qid');
        $filterBy = (isset($aAttributes['autoCompleteFilter']) && $aAttributes['autoCompleteFilter']) ? "{".trim($aAttributes['autoCompleteFilter'])."}" : "";
        $filterBy = CHtml::tag("div",array(
                'class'=>"hidden hide",
                'style'=>"display:none",
                'id'=>"filter".$sgq,
            ),$filterBy
        );
        $oEvent->set("answers",$oEvent->get("answers").$filterBy);
        switch ($oEvent->get('type')) {
            case '!':
                // @TODO
                $script = "";
                break;
            case "S":
            default:
                if(!$this->_getFileName($qid)) {
                    return;
                }
                $currentValue = $_SESSION['survey_'.$oEvent->get('surveyId')][$sgq];
                
                $replaceValue = "";
                $function = "setAutoCompleteCode";
                if($aAttributes['autoCompleteOneColumn']) {
                    $function = "setAutoCompleteText";
                }
                if($currentValue && !$aAttributes['autoCompleteOneColumn']) {
                    $replaceValue = $this->_getCurrentString($qid,$currentValue);
                }
                $minChar = intval($aAttributes['autoCompleteMinChar']);
                $minChar = ($minChar >= 0) ? $minChar : 1;
                $options = array(
                    "serviceUrl" => $this->api->createUrl('plugins/direct', array('plugin' => get_class($this),'function'=>'getData','qid'=>$oEvent->get('qid'))),
                    "minChar" => $minChar ,
                    "replaceValue" => $replaceValue,
                );
                $script = $function."('".$sgq."',".json_encode($options).");\n";
                break;
        }
        App()->getClientScript()->registerScript("autoComplete{$oEvent->get('qid')}",$script,CClientScript::POS_END);
    }
  }

    public function newDirectRequest()
    {
        if($this->getEvent()->get('target') != get_class($this)) {
            $this->_renderJson();
        }
        $qid = $this->api->getRequest()->getParam('qid');
        $oQuestion = Question::model()->find("qid =:qid", array(":qid"=>$qid));
        if(!$oQuestion) {
            $this->_renderJson("no question $qid");
        }
        $aAttributes=QuestionAttribute::model()->getQuestionAttributes($qid);
        if(empty($aAttributes['autoComplete'])){
            $this->_renderJson();
        }
        if(empty($aAttributes['autoCompleteCsvFile'])){
            $this->_renderJson();
        }
        $csvFile = trim($aAttributes['autoCompleteCsvFile']);
        if(strlen($csvFile) < 4 || strtolower(substr($csvFile, -4)) != ".csv") {
            $csvFile =  $csvFile.".csv";
        }
        $oneColumn = !empty($aAttributes['autoCompleteOneColumn']);
        $suggestion = array();

        $completeFile = $this->_getFileName($qid);
        if(!$completeFile) {
            /* Except with hack can not happen */
            $suggestion[] = array("value"=> "Invalid file for this question", "data"=>"invalid");
            $this->_renderSuggestion($suggestion);
        }
        $filter = $this->api->getRequest()->getParam('filter');
        $search = $this->api->getRequest()->getParam('query');
        $handle = fopen($completeFile, "r");
        $headerDone = false;
        while (($line = fgetcsv($handle, 10000, ",")) !== false) {
            if (!$headerDone) {
                $headerDone = true;
                continue;
            }
            $data = $line[0];
            $value = isset($line[1]) ? $line[1] : "";
            if(empty($filter) || substr($data, 0, strlen($filter)) == $filter) {
                
                if($oneColumn) {
                    if(!$search || strpos($data,$search)!==false) {
                        $suggestion[] = $data;
                    }
                } else {
                    if(!$search || strpos($value,$search)!==false) {
                        $suggestion[] = array(
                            'data'=>$data,
                            'value'=>$value,
                        );
                    }
                }
            }
        }
        fclose($handle);
        $this->_renderSuggestion($suggestion);
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
    $autoCompleteAttributes = array(
      'autoComplete' => array(
        'types'     => 'S',//'!S', /* List radio and short text */
        'category'  => gT('Display'),
        'sortorder' => 300,
        'inputtype' => 'switch',
        'default'   => 0,
        'caption' => $this->gT("Use autocomplete"),
      ),
      'autoCompleteCsvFile'=>array(
        'types'=>'S', /* Short text */
        'category'=>gT('Display'),
        'sortorder'=>301,
        'inputtype'=>'text',
        'default'=>'', /* not needed (it's already the default) */
        'help'=>$this->gT("The CSV file must be in this survey files directory, it was readed in UTF8 with comma."),
        'caption'=>$this->gT('CSV file to be used'),
      ),
      'autoCompleteOneColumn'=>array(
        'types'=>'S', /* Short text */
        'category'=>gT('Display'),
        'sortorder'=>302,
        'inputtype'=>'switch',
        'default'=>1,
        'help'=>$this->gT("Use only the first column in the csv file."),
        'caption'=>$this->gT('Use only one column'),
      ),
      'autoCompleteFilter'=>array(
        'types'=>'S',//'!S', /* Short text */
        'category'=>gT('Display'),
        'sortorder'=>302,
        'inputtype'=>'text',
        'expression'=>2, // Forced expression
        'default'=>'', /* not needed (it's already the default) */
        'help'=>$this->gT("Entere the expression manager for filtering, filter is done on first column, search the value of question code, and search at start of code."),
        'caption'=>$this->gT('Filter by (expression)'),
      ),
      'autoCompleteMinChar'=>array(
        'types'=>'S',//'!S', /* Short text */
        'category'=>gT('Display'),
        'sortorder'=>303,
        'inputtype'=>'integer',
        'default'=>1,
        'caption'=>$this->gT('Minimum character to start search'),
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

  private function _registerScript()
  {
    Yii::setPathOfAlias('autoComplete', dirname(__FILE__));
    /* Quit if is done */
    if(array_key_exists('devbridge-autocomplete-limesurvey',Yii::app()->getClientScript()->packages)) {
        return;
    }
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

  public function gT($string) {
    if(Yii::app()->getConfig('versionnumber') >=3) {
        return parent::gT($string);
    }
    return gT($string);
  }

    /**
     * @inheritdoc
     * Adding message to vardump if user activate debug mode
     */
    public function log($message, $level = \CLogger::LEVEL_TRACE)
    {
        // parent::log($message, $level); (LimeSurvey version API)
        Yii::log("[".get_class($this)."] ".$message, $level, 'vardump');
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
}
