<?php
/**
 * Utility class to help get labels from DataDictionary
 */


class DataDictionaryHelper {
    public $project_id;
    public $data_dict;
    
    //instantiate object with pid and data dictionary for pid
    public function __construct($project_id) {
      //Plugin::log("Creating DataDictionaryHelper for project_id $project_id", "DEBUG");
        if ($project_id) {
            $this->project_id = intval($project_id);
        } else {
            Plugin::log("Need project_id to be passed in to constructor", "ERROR");
            exit();
        }
    
        //retrieve data dictionary from project_id
        $this->data_dict = $this->getDataDict($project_id);
        //Plugin::log("this is result from getDataDictionary for project id $project_id ".print_r($this->data_dict, true), "DEBUG");
    }
    
    public function getDataDict($project_id) {
        // Get the data dictionary with labels
        $dd_array = REDCap::getDataDictionary($project_id, 'array');
    
        return $dd_array;
    }
    
    public function getFieldType($field) {
        $field_type = $this->data_dict[$field]['field_type'];
        return $field_type;
    }


    /**
     * Given parameters of the field_name and the field value, return
     * the label for that selection
     * @param unknown $field
     * @param unknown $value
     */
    public function getLabel($field, $value) {
        $vis_study_choices  =  $this->getDictChoices($field);
        $label = $vis_study_choices[$value];
        return $label;
    }

    /**
     * First get the fieldtype and then get label
     * @param unknown $field
     * @param unknown $value
     */
    public function getLabelFromFieldType($field, $value) {
        $field_type = $this->getFieldType($field);
        switch ($field_type) {
            case "checkbox":
                $label = $this->getLabelCheckbox($field,$value);
                break;
            case "radio":
                $label = $this->getLabel($field,$value);
                break;
            case "dropdown":
                $label = $this->getLabel($field,$value);
                break;
            case "yesno":
                $label = $this->getYesNo($field,$value);
                break;
            case "truefalse":
                $label = $this->getTrueFalse($field,$value);
                break;
            default:
                $label = $value;
        }

        return $label;
    }

    public function getFieldLabel($field) {
        $field_label = $this->data_dict[$field]['field_label'];
        return $field_label;
    }

    public function getYesNo($field, $value) {
        $label = null;
        switch ($value) {
            case 1:
                $label = "Yes";
                break;
            case 0:
                $label = "No";
                break;
            default:
                return null;
        }
        return $label;
    }

    public function getTrueFalse($field, $value) {
        $label = null;
        switch ($value) {
            case 1:
                $label = "True";
                break;
            case 0:
                $label = "False";
                break;
            default:
                return null;
        }
        return $label;
    }


    /**
     * Given parameters of the field_name and the field value, return
     * the label for that selection
     * @param unknown $field
     * @param unknown $value
     * (
        [1] => 0
        [2] => 1
        [3] => 0
    )
     */
    public function getLabelCheckbox($field, $value) {
        //$value is an array, if 1 show value
        $label = '';

        $checkbox_choices  =  $this->getDictChoices($field);
    //    logIt( "<pre> CHECKBOX".print_r($checkbox_choices, true)."</pre>");

        foreach ($value as $key => $selection) {

            if ($selection == '1') {
                //it's been selected so add it to the queue
                $label .= $checkbox_choices[$key]."<br>";
            }
        }
        return $label;
    }

    public function getLabelCheckboxSingleValue($field, $value) {
        //$value is an array, if 1 show value
        $label = '';

        $checkbox_choices  =  $this->getDictChoices($field);
        $label = $checkbox_choices[$value];

        return $label;
    }


    /**
     * This is construct of dictionary
     [vis_study] => Array
     (
     [field_name] => vis_study
     [form_name] => appointment
     [section_header] =>
     [field_type] => dropdown
     [field_label] => Study:
     [select_choices_or_calculations] => 1, 22871|2, 22872|27, AnaptysBio|26, ASTELLAS|28, Control|4, EAP|5, HONEY|6, ITN|7, MAP-X|8, MILES|9, Milk Xolair|29, Mindset|10, M-TAX|11, P2MAX|12, PALISADES|13, PEPITES|14, POISED|15, PRROTECT|25, REALISE|16, Screener|17, Starfish|18, STP|19, Super 16|20, Twin|21, UA|22, Unspecified|23, VIPES/OLFUS|24, Wheat OIT|75, Waitlist
     ...
     * @param unknown $fields
     */
    public function getDictChoices($field) {
        if (empty($field)) {
            throw new Exception("The variable list is undefined.");
        }

        $choices = array();

        //lookup each field in data dictionary

        //       echo "item is $item and this is the the datadict: ".print_r($this->data_dict, true);
        $choice_list = $this->data_dict[$field]['select_choices_or_calculations'];
        //

        $exploded = explode('|',$choice_list);

        foreach ($exploded as $value) {
            $temp = explode(',',$value);
            $choices[trim($temp[0])]= trim($temp[1]);
        }
        return $choices;
    }
}