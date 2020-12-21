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
    /* HOOK METHODS  - migrated from hook for pid10466                                                                 */
    /***************************************************************************************************************** */

    public function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance = 1 ) {

        if (in_array($instrument, array('mentoring_support_form')) ) {
            ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    var btn = '<button id="print_button" class="btn btn-primary pull-right hidden-print" onclick="window.print();"><span class="glyphicon glyphicon-print" aria-hidden="true"></span> Print Form</button>';
                    $('.surveysubmit').after(btn);
                });
            </script>
            <?php
        }
    }

    public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {

        if (in_array($instrument, array('admin_reviewer_reminders')) AND $event_id == $this->getFirstEventId()) {
            //get reviewers for this record
            //$reviewer_fields = array('reviewer_1', 'reviewer_2','reviewer_3', 'reviewer_4','reviewer_5');
            $reviewer_fields = $this->getReviewerFields();
            $q = REDCap::getData('json',$record,$reviewer_fields,$event_id);
            $results = json_decode($q,true);
            $result = $results[0];

            $data = array();
            foreach ($reviewer_fields as $candidate) {
                if (!empty($result[$candidate])) {
                    //check existing data for this reviewer
                    $already_assigned = $this->filterForReviewer($result[$candidate], $event_id, $record);

                    $data[$candidate . '_sunet'] = $result[$candidate];
                    $data[$candidate . '_assigned'] = $already_assigned;
                } else {
                    $data[$candidate . '_sunet'] = null;
                    $data[$candidate . '_assigned'] = null;
                }

            }

            //now display the reviewer report in the field
            ?>
            <script type='text/javascript'>
                $(document).ready(function() {

                    $('#reviewer_1_assigned').val(<?php print json_encode($data['reviewer_1_assigned']); ?>);
                    $('#reviewer_2_assigned').val(<?php print json_encode($data['reviewer_2_assigned']); ?>);
                    $('#reviewer_3_assigned').val(<?php print json_encode($data['reviewer_3_assigned']); ?>);
                    $('#reviewer_4_assigned').val(<?php print json_encode($data['reviewer_4_assigned']); ?>);
                    $('#reviewer_5_assigned').val(<?php print json_encode($data['reviewer_5_assigned']); ?>);
                    $('#reviewer_6_assigned').val(<?php print json_encode($data['reviewer_6_assigned']); ?>);

                    $('input[name="reviewer_1_sunet"]').val('<?php print ($data['reviewer_1_sunet']);  ?>').blur();
                    $('input[name="reviewer_2_sunet"]').val('<?php print ($data['reviewer_2_sunet']);  ?>').blur();
                    $('input[name="reviewer_3_sunet"]').val('<?php print ($data['reviewer_3_sunet']);  ?>').blur();
                    $('input[name="reviewer_4_sunet"]').val('<?php print ($data['reviewer_4_sunet']);  ?>').blur();
                    $('input[name="reviewer_5_sunet"]').val('<?php print ($data['reviewer_5_sunet']);  ?>').blur();
                    $('input[name="reviewer_6_sunet"]').val('<?php print ($data['reviewer_6_sunet']);  ?>').blur();
                    console.log("Done!");

                });
            </script>

            <?php

        }
    }

    public function redcap_every_page_top() {
        // The goal of this hook is to prevent save-and-return-late from sending emails to the designated email address
        if (PAGE == "surveys/index.php" AND isset($_GET['__return'])) {
            // We want to prevent the end of survey email from getting triggered via an ajax call.
            // \Plugin::log("Injecting Proxy Hook!","DEBUG",__FUNCTION__);
            ?>
            <script>

                // Override the redcap_validate with an anonymous function
                (function () {
                    // Cache the original function under another name
                    var proxied = emailReturning;

                    // Redefine the original
                    emailReturning = function () {
                        // Examine the arguments to this function so you can do your own thing:

                        // If provideEmail is visible, then honor request
                        if ( $('#provideEmail').is(":visible") ) {
                            // Do the original proxied function
                            $result = proxied.apply(this, arguments);
                        } else {
                            // Suppress the autoEmail and make the provideEmail visible
                            $('#autoEmail').hide();
                            $('#provideEmail').show();
                            $result = false;
                        }
                        return $result;
                    }
                })()

            </script>
            <?php
        }

    }

    /*******************************************************************************************************************/
    /* HELPER METHODS FOR HOOKS                                                                                        */
    /***************************************************************************************************************** */


    /**
     * @param $reviewer
     * @param $event_id
     */
    function filterForReviewer($reviewer, $event_id, $current_id) {
        //Plugin::log($reviewer, "DEBUG","LOOKING FOR ");
        //get event name from the $event_id
        $event = REDCap::getEventNames(true, true,$event_id);

        //get all the other records where $reviewer is a reviewer
        //filter for this reviewer in all the reviewer fields
        $filter = "[{$event}][reviewer_1] = '{$reviewer}'";
        $filter .= " OR [{$event}][reviewer_2] = '{$reviewer}'";
        $filter .= " OR [{$event}][reviewer_3] = '{$reviewer}'";
        $filter .= " OR [{$event}][reviewer_4] = '{$reviewer}'";
        $filter .= " OR [{$event}][reviewer_5] = '{$reviewer}'";
        $filter .= " OR [{$event}][reviewer_6] = '{$reviewer}'";
        //Plugin::log($filter, "DEBUG", "FILTER");

        //$reviewer_fields = array('reviewer_1', 'reviewer_2','reviewer_3', 'reviewer_4','reviewer_5');
        $reviewer_fields = $this->getReviewerFields();

        $send_fields = array(REDCap::getRecordIdField(),
            'send_reviewer_1', 'send_reviewer_2','send_reviewer_3', 'send_reviewer_4','send_reviewer_5', 'send_reviewer_6',
            'send_reviewer_1_5_wk_reminder','send_reviewer_2_5_wk_reminder','send_reviewer_3_5_wk_reminder','send_reviewer_4_5_wk_reminder','send_reviewer_5_5_wk_reminder',
            'send_reviewer_1_2_wk_reminder','send_reviewer_2_2_wk_reminder','send_reviewer_3_2_wk_reminder','send_reviewer_4_2_wk_reminder','send_reviewer_5_2_wk_reminder',
            'send_reviewer_1_1_wk_reminder','send_reviewer_2_1_wk_reminder','send_reviewer_3_1_wk_reminder','send_reviewer_4_1_wk_reminder','send_reviewer_5_1_wk_reminder');
        $get_fields = array_merge($reviewer_fields, $send_fields);

        //construct the filed list of fields to get dat for
        foreach ($reviewer_fields as $candidate) {
            //add fields
            $get_fields_2[] = $candidate;
            $get_fields_2[] = 'send_'.$candidate;
            $get_fields_2[] = 'send_'.$candidate.'_5_wk_reminder';
            $get_fields_2[] = 'send_'.$candidate.'_2_wk_reminder';
            $get_fields_2[] = 'send_'.$candidate.'_1_wk_reminder';
        }

        $records = REDCap::getData('array', null, $get_fields, null, null, false, false, false, $filter);
        //Plugin::log($records, "DEBUG", "FILTER FOR REVIEW ALL RECORDS");

        $complete_review_records = REDCap::getData('array', null, array('review_marked_complete'));
        //Plugin::log($complete_review_records, "DEBUG", "COMPLETION STATUS REVIEW ALL RECORDS");

        $str = '';
        foreach ($records as $v) {
            $rec_id = $v[$event_id]['record_id'];

            //Plugin::log($v[$event_id], "DEBUG", "Looking for reviewer $reviewer in ========RECORD ID: ".$rec_id);

            //If it's current record, then don't report
            if ($current_id == $rec_id) continue;

            //locate the reviewer position for this reviewer? is this reviewer_1, reviewer_2, etc?
            $reviewer_position = '';
            foreach ($reviewer_fields as $k) {
                if ($v[$event_id][$k] == $reviewer) {
                    $reviewer_position = $k;
                    break;
                }
            }

            //Check if the review is complete for this $reviewer in this $reviewer_position, if it is, continue
            $reviewer_event = REDCap::getEventIdFromUniqueEvent($reviewer_position.'_arm_1');
            $review_complete = $complete_review_records[$rec_id][$reviewer_event]['review_marked_complete']['1'];

            if ($review_complete == '1') {
                //$str.="COMPLETED \t";
                continue;
            }

            $send_status = $v[$event_id]['send_'.$reviewer_position][1] ? 'Yes' : 'No';
            $send_status_5_wk = $v[$event_id]['send_'.$reviewer_position.'_5_wk_reminder'][1] ? 'Yes' : 'No';
            $send_status_2_wk = $v[$event_id]['send_'.$reviewer_position.'_2_wk_reminder'][1] ? 'Yes' : 'No';
            $send_status_1_wk = $v[$event_id]['send_'.$reviewer_position.'_1_wk_reminder'][1] ? 'Yes' : 'No';

            $str .="$rec_id: Already a reviewer for $rec_id.\t";
            $str .="Sent reviewer email?: ".$send_status."\t";
            $str .="Sent 5 wk?: ".$send_status_5_wk."\t";
            $str .="Sent 2 wk?: ".$send_status_2_wk."\t";
            $str .="Sent 1 wk?: ".$send_status_1_wk."\n";
        }
        return $str;
    }


    /*******************************************************************************************************************/
    /* REVIEWER LANDING PAGE METHODS                                                                                                    */
    /***************************************************************************************************************** */

    function generateReviewGrid($sunet_id, $data) {

        $select = array("record_id", "program", "fy", "cycle", "applicant_name", "dept", "division",
            "reviewer_num", "budget", "proposal");
        $header = array('Reviewer Form', 'Program', 'FY', 'Cycle', 'Full Name', 'Department', 'Division',
            'Reviewer Number', 'Budget Worksheet', 'MCHRI Proposal');

        foreach ($data as $key => $row) {

            //CHANGE REQUEST: each row now allows reviewer to be in multiple review slots
            $id = $row['record_id'];
            $rev_num = $row['reviewer_num'];
            $row_id = $id."_".$rev_num;

            foreach ($select as $k_item) {

                $item = $row[$k_item];

                switch ($k_item) {
                    case "budget":
                        if ($item == null) {
                            $table_data[$id][$k_item] = null;
                            break;
                        }
                        //the returned valued from getData is the edocID!
                        $cell = $this->renderDownloadButton('budget_worksheet', $row['record_id'], $item, $sunet_id);
                        $table_data[$row_id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;
                    case "proposal":
                        if ($item == null) {
                            $table_data[$row_id][$k_item] = null;
                            break;
                        }
                        //the returned valued from getData is the edocID!
                        $cell = $this->renderDownloadButton('chri_proposal', $row['record_id'], $item, $sunet_id);
                        $table_data[$row_id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
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
                            '<input id="redirect_survey_' . $item . '" type="button" class="btn btn-primary" value="Go to ' . $item . '" onclick="redirectToSurvey(\'' . $survey_link . '\',\'' . $return_code . '\');" />';


                        $table_data[$row_id][$k_item] = array('CUSTOM' => 'plain', 'DISPLAY' => $cell);
                        break;

                    default:

                        $table_data[$row_id][$k_item] = $item;
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

                // Check for custom fieldtype
                if (is_array($this_col)  && (array_key_exists('CUSTOM', $this_col) === true)) {
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
     * return array of all the reviewer fields
     * @return array
     */
    function getReviewerFields() {
        $reviewer_fields = array();

        $reviewer_list = $this->getSubSettings('reviewer-list');

        foreach ($reviewer_list as $k =>  $r_field) {
            $reviewer_fields[] = $r_field['reviewer-field'];
        }
        return $reviewer_fields;
    }

    /**
     * return array of all the reviewer event ids
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

        $reviewer_fields = array();

        //first get list of record ids which have the suentID as a reviewer
        //create filter to get only rows where sunet is a reviewer
        $filter = "";
        $i = count($reviewer_list);
        foreach ($reviewer_list as $r_field) {
            $rf = $r_field['reviewer-field'];
            $reviewer_fields[] = $rf; //add to list of reviewer fields

            //create filter to get all the reviewer records
            $filter .= "([{$first_event_name}][{$rf}] = '$target_sunet') ";

            if (next($reviewer_list)) {
                //there is more so add an OR
                $filter .= " OR ";
            }
        }
        $rec_id = $this->getRecordId(); //this doesn't work?
        $rec_id = 'record_id';

        $params = array(
            'project_id'    => $this->getProjectId(),
            'return_format' => 'array',
            'fields'        => array($rec_id),
            'filterLogic'   => $filter
        );

        //$this->emDebug("sunet search params are", $params);
        $reviewer_array = REDCap::getData($params);  // this is the array of records which have the sunet id as a reviewer

        //need to split out the get in two two gets because with a filter, it only seems to return the first event
        //we need the reviewer event to get the status

        $table_col = array("record_id","program","fy","cycle","applicant_name","dept","division",
            "reviewer_1","reviewer_2","reviewer_3","reviewer_4","reviewer_5","reviewer_6",
            "budget_worksheet","chri_proposal","review_marked_complete");


        //Using the reviewer list, get the data from the reviewer events

        $event_params = array(
            'project_id'    => $this->getProjectId(),
            'return_format' => 'array',
            'fields'        => $table_col,
            'records'       => array_keys($reviewer_array)
        );
        //$this->emDebug("record search params are", $event_params);
        //filter limits to records where the sunet_id is a reviewer
        $q = REDCap::getData($event_params);

        $this->emDebug("found ". count($q) . " records for which $target_sunet is the reviewer.");

        //i should replace this with a sql query where it retrieves only the records where the sunet is a reviewer in any of the reviewer slot
        //$result = REDCap::getData($pid, 'array', null, $table_col, null, null);

        //iterate over the each of the records
        foreach ($q as $key => $all) {

            // the assignment of reviewer is in the first EVENT
            $value = $all[$first_event];
            $first_event = $all[$this->getFirstEventId()];

            //create temp array of all the reviewers

            //get the event of the reviewer and check if the review is complete
            //this only works if $target_sunet does not show up for any of the other fields in $table_col
            //$found = array_search($target_sunet, $value);

            //CHANGE REQUEST from 2020Dec: allow reviewer to be in multiple events
            //iterate over the reviewer fields
            foreach ($reviewer_fields as $reviewer_field) {
                $found             = $first_event[$reviewer_field];
                if (!empty($found) AND ($found == $target_sunet)) {

                    $reviewer_event    = $reviewer_field.'_arm_1';
                    $reviewer_event_id = REDCap::getEventIdFromUniqueEvent($reviewer_event);

                    //check completed field
                    $complete_status   = $all[$reviewer_event_id]['review_marked_complete'][1];
                    // if the review has been marked complete, don't add to table. 'review_marked_complete' is field checked by reviewer in chri_reviewer_form
                    if ($complete_status == 1) {
                        continue;
                    }

                    $array = array(
                        "record_id"             =>$key,
                        "review_marked_complete"=> $complete_status ,  //$value['review_marked_complete']['1'],
                        "program"               =>$first_event['program'],
                        "fy"                    =>$first_event['fy'],
                        "cycle"                 =>$first_event['cycle'],
                        "applicant_name"        =>$first_event['applicant_name'],
                        "dept"                  =>$first_event['dept'],
                        "division"              =>$first_event['division'],
                        "reviewer_num"          =>  substr( $reviewer_field, 9, 1 ), //$reviewer_num,
                        'budget'                =>$first_event['budget_worksheet'],
                        'proposal'              =>$first_event['chri_proposal']);
                    array_push($returnarray, $array);
                }
            }

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

        //$this->emDebug($href);

        $cell =  '<a target="_blank" id="'.$fieldname.'" name="'.$fieldname.'" href="'.$href.'" class="btn btn-primary">Download</a>';
        //$cell = '<button type="submit" id="'.$fieldname.'" name="'.$fieldname.'" class="btn btn-primary btn-block" value="true">Download</button>';
        //$cell = '<button type="submit" id="'.$fieldname.'" name="download" data-id="'.$edoc_id.'" class="btn btn-primary btn-block" value="'.$fieldname.'">Download</button>';
        //$cell = '<button type="submit" id="'.$fieldname.'"  name="download" data-id="'.$edoc_id.'" class="btn btn-primary btn-block btn-download" value="true">Download</button>';

        return $cell;
    }



}