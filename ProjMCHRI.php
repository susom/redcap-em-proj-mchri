<?php

namespace Stanford\ProjMCHRI;

use DateInterval;
use DateTime;
use REDCap;
use ExternalModules\ExternalModules;
use Alerts;
use Files;

require_once 'src/DataDictionaryHelper.php';
require_once 'emLoggerTrait.php';

class ProjMCHRI extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    /*******************************************************************************************************************/
    /* HOOK METHODS                                                                                                    */
    /***************************************************************************************************************** */

    public function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1 ) {

        if (in_array($instrument, array('mentoring_support_form')) ) {
            ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    var btn = '<button id="print_button" class="btn btn-primary pull-right hidden-print" onclick="window.print();"><span class="glyphicon glyphicon-print" aria-hidden="true"></span> Print</button>';
                    $('.surveysubmit').after(btn);
                });
            </script>
            <?php
        }
    }


    public function redcap_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance) {
        //
        $this->emDebug("Just saved $instrument in instance $repeat_instance");

        //on save of admin_review form, trigger email to admin to verify the training
        //or Reset to new event instance
        if ($instrument == $this->framework->getProjectSetting('admin-review-form')) {

        }
    }


    function downloadfile($edoc_id) {

        $pid = $this->getProjectId();

        $sql = "select * from redcap_edocs_metadata where doc_id = '" . $edoc_id . "' and delete_date is null";
        if (!empty($pid)) {
            $sql .= " and project_id = " . db_escape($pid);
        }
        $q = db_query($sql);
        $this->emDebug($sql);
        $this_file = db_fetch_assoc($q);

        //Download from "edocs" folder (use default or custom path for storage)
        $local_file = EDOC_PATH . $this_file['stored_name'];
        //$local_file = 'foo.txt';
        $this->emDebug("locaal file $local_file exitst: ".file_exists($local_file) );
        $this->emDebug("locaal file $local_file is_File: ".is_file($local_file) );

        if (file_exists($local_file) && is_file($local_file)) {

            header('Pragma: anytextexeptno-cache', true);
            header('Content-Type: ' . $this_file['mime_type'] . '; name="' . $this_file['doc_name'] . '"');
            header('Content-Disposition: attachment; filename="' . $this_file['doc_name'] . '"');
            ob_end_flush();
            readfile_chunked($local_file);
            //readfile($local_file);
            //sleep(5);
            $this->emDebug("Just completed download: Reviewer portal: Review filedown loaded by ".$sunet_id . " for project: $local_file");

        } else {
            die('<b>' . $lang['global_01'] . $lang['colon'] . '</b> ' . $lang['file_download_08'] . ' <b>"' . $local_file .
                '"</b> ("' . $this_file['doc_name'] . '") ' . $lang['file_download_09'] . '!');
        }

        $this->emDebug("file is $local_file with filename is " . $this_file['doc_name']);
    }

    function generateReviewGrid($sunet_id, $data) {

        $select = array("record_id", "program", "fy", "cycle", "applicant_name", "dept", "division",
            "reviewer_num", "budget", "proposal");
        $header = array('Reviewer Form', 'Program', 'FY', 'Cycle', 'Full Name', 'Department', 'Division',
            'Reviewer Number', 'Budget Worksheet', 'MCHRI Proposal');

        foreach ($data as $key => $row) {
            //Plugin::log($row, "DEBUG", "KEY: $key");
            foreach ($select as $k_item) {
                //Plugin::log($k_item, "DEBUG", "=====K_ITME: $k_item has $row[$k_item]");
                $id = $row['record_id'];
                $item = $row[$k_item];
                switch ($k_item) {
                    case "reviewer":

                        $table_data[$id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;
                    case "budget":
                        if ($item == null) {
                            $table_data[$id][$k_item] = null;
                            break;
                        }
                        //the returned valued from getData is the edocID!
                        $cell = $this->renderDownloadButton('budget_worksheet', $row['record_id'], $item, $sunet_id);
                        $table_data[$id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;
                    case "proposal":
                        if ($item == null) {
                            $table_data[$id][$k_item] = null;
                            break;
                        }
                        //the returned valued from getData is the edocID!
                        $cell = $this->renderDownloadButton('chri_proposal', $row['record_id'], $item, $sunet_id);
                        $table_data[$id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;

                    case "record_id" :
                        $instrument = 'chri_reviewer_form';
                        $unique_event = 'reviewer_' . $row['reviewer_num'] . '_arm_1';
                        $event_id = REDCap::getEventIdFromUniqueEvent($unique_event);

                        // Get the survey link for this record-instrument-event
                        $survey_link = REDCap::getSurveyLink($row['record_id'], 'chri_reviewer_form', $event_id);
                        $return_code = REDCap::getSurveyReturnCode($row['record_id'], 'chri_reviewer_form', $event_id);

                        //$cell = "<a href= ".$survey_link." target='_blank'>".$item."</a>";
                        $cell =
                            '<input id="redirect_survey_' . $item . '" type="button" class="btn btn-default" value="' . $item . '" onclick="redirectToSurvey(\'' . $survey_link . '\',\'' . $return_code . '\');" />';


                        $table_data[$id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;

                    default:

                        $table_data[$id][$k_item] = $item;
                        break;
                }

            }
        }
        $review_grid = $this->getHTMLTable($select, $table_data, "review_table", $header);

        return $review_grid;

    }


    /**
     * Basic table generator - html grid of fields and values in table
     *
     * @param array $fields
     *            list of variables to report (null returns all fields)
     *
     * @param text $table_id
     *            ID for table
     * @throws Exception
     */
    public function getHTMLTable($fields = null, $data = null, $table_id = 'table_id', $header = null) {
        // convert header fields names to label
        if ($header == null) {
            $header = $this->getHeader($fields);
        }
        if ($data == null) {
            $data = $this->getData($this->project_id, $fields);
        }
        // Plugin::log("HTML Table: " . print_r($data, true));

        $grid = self::renderTable($table_id, $header, $data);
        return $grid;

    }

    /**
     *
     * @param unknown $id
     *            String to be used for table id
     * @param array $header
     *            Array of labels for column headers
     * @param unknown $data
     *            Array of data to be displayed in tables
     * @return string
     */
    public function renderTable($id, $header = array(), $data) {

        // Render table
        $grid = '<table id="' . $id . '" class="table table-striped table-bordered table-condensed" cellspacing="0" width="95%">';

        $grid .= self::renderHeaderRow($header, 'thead');
        // $grid .= self::renderHeaderRow($header, 'tfoot');
        $grid .= self::renderTableRows($data);
        $grid .= '</table>';

        return $grid;
    }

    public function renderHeaderRow($header = array(), $tag) {
        $row = '<' . $tag . '><tr>';
        foreach ($header as $col_key => $this_col) {
            $row .= '<th>' . $this_col . '</th>';
        }
        $row .= '</tr></' . $tag . '>';
        return $row;
    }


    /*
     * getData returns this format
     * (
     * [0] => Array
     * (
     * [appointment_id] => 1
     * [vis_ppid] => P456
     * [vis_study] => 1
     * ...
     *
     * 1May2017: support input types sepcified in second item in array
     * 'DATE'
     * 'SELECT' : array of data dictionary
     */
    public function renderTableRows($row_data = array()) {
        $d_helper = new \DataDictionaryHelper($this->getProjectId());

        $rows = '';

        foreach ($row_data as $row_key => $this_row) {
            // Plugin::log("ARRY DEPTH: ". Utility::getArrayDepth($this_row));

            // how to deal with nested array if longitudinal since CUSTOM is in a separate array
            // if ((Utility::getArrayDepth($this_row)>1) ) {
            // $this_row = current($this_row);
            // }
            //
            $rows .= '<tr>';

            // Plugin::log(" THIS ROW ".print_r($this_row, true));
            foreach ($this_row as $col_key => $this_col) {
                // if (isset($this_col['CUSTOM']) === true || empty($this_col['CUSTOM']) === false) {
                // Plugin::log($this_col['CUSTOM'], "DEBUG", " this_col[custom] ");
                // Plugin::log((array_key_exists('CUSTOM', $this_col)), "DEBUG", "ARRAY_KEY_EXISTS(custom, this_col)");
                // Plugin::log((array_key_exists('CUSTOM', $this_col) === true), "DEBUG", "ARRAY_KEY_EXISTS(custom, this_col) === true");
                // Plugin::log((isset($this_col['CUSTOM']) === true), "DEBUG", "isset(this_col['CUSTOM']===true ");
                // Plugin::log((empty($this_col['CUSTOM']) === false), "DEBUG", "empty(this_col['CUSTOM']) === false");
                // Plugin::log($this_col, "DEBUG", "THIS_COL");
                // }
                // Check for custom fieldtype
                if (array_key_exists('CUSTOM', $this_col) === true) {
                    $field_type = $this_col['CUSTOM'];
                    $data = $this_col['DATA'];
                    $display = $this_col['DISPLAY'];
                    // Plugin::log("FIELDTYPE is $fieldtype / $data / $display");
                } else {
                    // Use the REDCap fieldtype to decide rendering
                    // Can't use REDCap::getFieldType on another project
                    // $field_type = REDCap::getFieldType($col_key);
                    $field_type = $d_helper->getFieldType($col_key);
                    // Plugin::log(" REGULAR FIELDTYPE: ".$field_type);
                }
                //Plugin::log("TRAVERSING tablerows: " . $col_key . " and " . $this_col . " and fieldtype " . $field_type);
                switch ($field_type) {
                    case "link" :
                        $label = "<a href= " . $data . " target='_blank'>" . $display . "</a>";
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    case "plain" :
                        $rows .= '<td>' . $display . '</td>';
                        break;
                    case "checkbox" :
                        $label = $d_helper->getLabelCheckbox($col_key, $this_col);
                        // Plugin::log("<br>CHECKBOX RENDERING row: LABEL: " . $label . " rowkey: " . $row_key . " col: " . $col_key . " : " . "<pre>" . print_r($this_col, true) . "</pre>");
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    case "radio" :
                        $label = $d_helper->getLabel($col_key, $this_col);
                        // Plugin::log("<br>RADIO RENDERING row: LABEL: " . $label . " rowkey: " . $row_key . " col: " . $col_key . " : " . "<pre>" . print_r($this_col, true) . "</pre>");
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    case "dropdown" :
                        $label = $d_helper->getLabel($col_key, $this_col);
                        // Plugin::log("<br>DROPDOWN RENDERING row: LABEL: " . $label . " rowkey: " . $row_key . " col: " . $col_key . " : " . "<pre>" . print_r($this_col, true) . "</pre>");
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    case "yesno" :
                        $label = $d_helper->getYesNo($col_key, $this_col);
                        // Plugin::log("<br>YESNO RENDERING row: LABEL: " . $label . " rowkey: " . $row_key . " col: " . $col_key . " : " . "<pre>" . print_r($this_col, true) . "</pre>");
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    case "truefalse" :
                        $label = $d_helper->getTrueFalse($col_key, $this_col);
                        // Plugin::log("<br>TRUEFALSE RENDERING row: LABEL: " . $label . " rowkey: " . $row_key . " col: " . $col_key . " : " . "<pre>" . print_r($this_col, true) . "</pre>");
                        $rows .= '<td>' . $label . '</td>';
                        break;
                    default :
                        $rows .= '<td>' . $this_col . '</td>';
                }

            }
            $rows .= '</tr>';

        }

        return $rows;
    }

    /**
     * return array of all the reviewer arrays
     * @return array
     */
    function getReviewerEvents() {
        $reviewer_events = array();

        $reviewer_list = $this->getSubSettings('reviewer-list');

        foreach ($reviewer_list as $k =>  $r_field) {
            $rf = $r_field['reviewer-field'];
            //$reviewer_events = $rf.'_arm_1';
            $reviewer_events[] = REDCap::getEventIdFromUniqueEvent( $rf.'_arm_1');

        }
        return $reviewer_events;
    }

    function getMentorTable($record) {
        $main_event = $this->getFirstEventId();
        $max_mentors =5;
        //Getdata for the summarize field
        $get_fields = array('rec_faculty_member_1','rec_mentee_1','rec_mentor_period_1','rec_title_1','rec_current_pos_1',
            'rec_faculty_member_2','rec_mentee_2','rec_mentor_period_2','rec_title_2','rec_current_pos_2',
            'rec_faculty_member_3','rec_mentee_3','rec_mentor_period_3','rec_title_3','rec_current_pos_3',
            'rec_faculty_member_4','rec_mentee_4','rec_mentor_period_4','rec_title_4','rec_current_pos_4',
            'rec_faculty_member_5','rec_mentee_5','rec_mentor_period_5','rec_title_5','rec_current_pos_5'
        );

        $q = REDCap::getData('array',array($record), $get_fields, $main_event);
        $record_result = $q[$record][$main_event];

        $mentor_html = '<table id="mentor_record" style=\'width:100%; table-layout:fixed;border-collapse: collapse;\'>';
        $mentor_html .=
            '<tr>
    <th style=\'width:20%;\'><b>Faculty Member / Trainee</b></th>
    <th style=\'width:20%;\'><b>Past / Current Mentee</b></th>
    <th style=\'width:20%;\'><b>Mentoring Period (yyyy - yyyy)</b></th>
    <th style=\'width:20%;\'><b>Title of Research Project</b></th>
    <th style=\'width:20%;\'><b>Current Position of Past Mentees</b></th>
  </tr>';


        for ($i = 1; $i <= $max_mentors; $i++) {
            $mentor_html .= '<tr>';
            $mentor_html .="<td>".$record_result["rec_faculty_member_$i"]."</td>";
            $mentor_html .='<td>'.$record_result["rec_mentee_$i"].'</td>';
            $mentor_html .='<td>'.$record_result["rec_mentor_period_$i"].'</tdz>';
            $mentor_html .='<td>'.$record_result["rec_title_$i"].'</td>';
            $mentor_html .='<td>'.$record_result["rec_current_pos_$i"].'</td>';
            $mentor_html .='</tr>';
        };

        $mentor_html .= '</table>';

        return $mentor_html;
    }


    /**
     *
     *
     * @param $target_sunet
     * @param $pid
     * @return array
     */
    function prepareRows($target_sunet, $pid) {
        $returnarray = array();
        // global $record;
        // global $log_id;
        // $record_only = false; //we are in record context - return only the record's rows
        // global $event_1;

        $reviewer_list = $this->getSubSettings('reviewer-list');
        $first_event   = $this->getFirstEventId();
        $first_event_name = REDCap::getEventNames(true, false, $first_event);


        //create filter to get only rows where sunet is a reviewer
        $filter = "";
        $i = count($reviewer_list);
        foreach ($reviewer_list as $r_field) {
            $rf = $r_field['reviewer-field'];
            $filter .= "([{$first_event_name}][{$rf}] = '$target_sunet') ";

            if (next($reviewer_list)) {
                //there is more so add an OR
                $filter .= " OR ";
            }
        }

        $table_col = array("record_id","program","fy","cycle","applicant_name","dept","division",
            "reviewer_1","reviewer_2","reviewer_3","reviewer_4","reviewer_5","reviewer_6",
            "budget_worksheet","chri_proposal","review_marked_complete");

        $params = array(
            'project_id'    => $this->getProjectId(),
            'return_format' => 'array',
            //'events'        =>  $event_filter_str,
            'fields'        => $table_col,
            'filterLogic'   => $filter
        );

        $q = REDCap::getData($params);



        //i should replace this with a sql query where it retrieves only the records where the sunet is a reviewer in any of the reviewer slot
        $result = REDCap::getData($pid, 'array', null, $table_col, null, null);
        // print "<pre>".print_r($result,true)."</pre>"; exit;
        // print "<pre>".print_r($result[382],true)."</pre>";

        foreach ($q as $key => $all) {

            // the assignment of reviewer is in the first EVENT
            $value = $all[$first_event];

            //create temp array of all the reviewers

            //get the event of the reviewer and check if the review is complete
            //this only works if $target_sunet does not show up for any of the other fields in $table_col
            $found = array_search($target_sunet, $value);

            //sanity check that $found starts with reviewer
            if (strpos( $found,  "reviewer") !== 0) {
                $this->emError("The reviewer sunet id was found in a non-reviewer field: $found");
                break;
            }

            $reviewer_event    = $found.'_arm_1';
            $reviewer_event_id = REDCap::getEventIdFromUniqueEvent($reviewer_event);
            $complete_status   = $value[$reviewer_event_id]['review_marked_complete'][1];

            // if found completed, don't add to table
            if ($complete_status == 1) {
                continue;
            }

            /**
                // original method
            $allowed = array($value['reviewer_1'],$value['reviewer_2'],$value['reviewer_3'],$value['reviewer_4'],$value['reviewer_5'],$value['reviewer_6']);

            if (in_array($target_sunet, $allowed, false)) {
                foreach ($allowed as $position => $taken) {

                    if (isset($taken) && ($taken == $target_sunet)) {
                        // sunet is a reviewer for this record
                        $reviewer_num = $position + 1;

                        // if it is complete, exclude
                        $unique_event = 'reviewer_' . $reviewer_num . '_arm_1';
                        $event_id = REDCap::getEventIdFromUniqueEvent($unique_event);
                        //print "<pre> recid: {$value['record_id']}  sunet: $target_sunet  taken:$taken event: $event_id key : $key is in reviewer_num $reviewer_num " . print_r($allowed, true) . "</pre>";

                        $complete_status = $all[$event_id]['review_marked_complete'][1];
                        //print "<pre> all[event_Id]: $complete_status :  " . print_r($all[$event_id]['review_marked_complete'][1], true) . "</pre>";

                        if ($complete_status == 1) {
                            // this record is already completed, skip it.
                            //print "COMPLETED so stop looking for more reviewer status for event $event_id for $key";
                            // stop looking for reviewers
                            break;
                        }

                    }

                }

                // if found completed, exit record search
                //print "<br><pre> COMPLETED:  $key: completion status is $complete_record </pre>";
                if ($complete_status == 1) {
                    continue;
                }

            } else {
                // print "<pre>{$value['record_id']} skipping $target_sunet in ".print_r($allowed, true)."</pre>";
                continue;
            }
*/
            //print "<br>ENTERING for recordid $key and reviewer_num $reviewer_num";
            $array = array(
                "record_id"             =>$key,
                "review_marked_complete"=> $complete_status ,  //$value['review_marked_complete']['1'],
                "program"               =>$value['program'],
                "fy"                    =>$value['fy'],
                "cycle"                 =>$value['cycle'],
                "applicant_name"        =>$value['applicant_name'],
                "dept"                  =>$value['dept'],
                "division"              =>$value['division'],
                "reviewer_num"          =>  substr( $found, 9, 1 ), //$reviewer_num,
                'budget'                =>$value['budget_worksheet'],
                'proposal'              =>$value['chri_proposal']);
            array_push($returnarray, $array);
        }
        asort($returnarray);
        return $returnarray;
    }

    /**
     *
     * @param unknown $fieldname
     * @param unknown $record
     * @param unknown $edoc_id
     * @return string
     */
    function renderDownloadButton($fieldname, $record, $edoc_id, $sunet_id) {
        //the returned value from the getData call for a upload field is the edoc_id. so need need to sql it.
        //$edoc_id = Utility::getEdocID(PROJECT_PID, EVENT_ID,$record, $fieldname);

        /*
         //DataEntry/file_download wont' work for reviwers as they don't have project permissions
        $doc_id_hash = Files::docIdHash($edoc_id);
        $href = APP_PATH_WEBROOT . 'DataEntry/file_download.php?pid='.PROJECT_PID.'&doc_id_hash='.$doc_id_hash.'&id='.$edoc_id.
            '&s=&page=application&record='.$record.'&event_id='.EVENT_ID.'.&field_name='.$fieldname.'&instance=1';
        */

        //update for EM
        $download_url = $this->getUrl("src/download.php", true, true);
        $project_pid = $this->getProjectId();
        $event_id = $this->getFirstEventId();

        //adapted file_download
        //$href ='download.php?id='.$edoc_id.'&pid='.PROJECT_PID.'&record='.$record.'&event_id='.EVENT_ID.'&field_name='.$fieldname.'&instance=1'.'&sunet_id='.$sunet_id;
        $href =$download_url.'?id='.$edoc_id.'&pid='.$project_pid.'&record='.$record.'&event_id='.$event_id.
            '&field_name='.$fieldname.'&instance=1'.'&sunet_id='.$sunet_id.'&edoc_id='.$edoc_id;

        $this->emDebug($href);

        //$cell =  '<a target="_blank" id="'.$fieldname.'" name="'.$fieldname.'" href="'.$href.'" class="btn btn-default">Download</a>';
        $cell = '<button type="submit" id="'.$fieldname.'" name="'.$fieldname.'" class="btn btn-primary btn-block" value="true">Download</button>';

        return $cell;
    }



}