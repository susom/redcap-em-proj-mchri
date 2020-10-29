<?php

namespace Stanford\ProjMCHRI;
/** @var \Stanford\ProjMCHRI\ProjMCHRI $module */

use REDCap;

$refer = $_SERVER['HTTP_REFERER'];
parse_str(parse_url($refer,PHP_URL_QUERY), $parts);

$record   = $parts['id'];
$instance = $parts['instance'];
$event_id = $parts['event_id'];

// Validate
if (!$record) {
    echo "<div class=“blue” style=\"text-align: center\"><b>Please use the Reviewer Meeting Report in context of a record.</b> </div>";
    exit();
}

// Verify user rights
$all_rights = REDCap::getUserRights(USERID);
$my_rights = $all_rights[USERID];

if (!SUPER_USER) {
    // Only project setup rights holders can use this
    if (!$my_rights['design']) {
        showError('Project Setup rights are required to use this plugin.');
        exit();
    }
    // Don't let expired users get in either
    if ($my_rights['expiration'] != "" && $my_rights['expiration'] < TODAY) {
        showError('Your user account has expired for this project.  Please contact the project admin.');
        exit();
    }
}

//TODO: this is not needed?
$meeting_list = $module->getSubSettings('report-field-list');
$get_fields = array();
foreach ($meeting_list as $k => $r_field) {
    $get_fields[] = $r_field['report-field'];
}

//Getdata for the summarize field
$get_fields = array('applicant_name', 'program', 'reviewer_summary', 'project_summary','mentor_summary_no_table',
    'record_mentor', 'reviewer_name', 'impact');

$q = REDCap::getData('array',array($record), $get_fields);
//$results = json_decode($q, true);

$record_result = $q[$record];
//$review_events = array(67556, 67557, 67558, 67559, 67560);
$review_events = $module->getReviewerEvents();


$main_event_id = $module->getFirstEventId();
$main_event_name = REDCap::getEventNames(true, false, $main_event_id);

$name = $record_result[$main_event_id]['applicant_name'];
$program_code = $record_result[$main_event_id]['program'];
$project_summary = $record_result[$main_event_id]['project_summary'];
$mentor_summary = $record_result[$main_event_id]['mentor_summary_no_table'];
$record_mentor = $record_result[$main_event_id]['record_mentor'];

$mentor_table = $module->getMentorTable($record);

$md      = $Proj->metadata;
$enums   = parseEnum($md['program']['element_enum']);
$program = $enums[$program_code];

//get the decoded value for the impact (overall evaluation

$enums   = parseEnum($md['impact']['element_enum']);


$i = 0;
$reviewer_reports = '';
foreach ($review_events as $event_id) {
    $i++;
    //    Plugin::log($event_id, "DEBUG", "event id at $i");

    $review = $record_result[$event_id]['reviewer_summary'];
    $reviewer = $record_result[$event_id]['reviewer_name'];
    $impact_code = $record_result[$event_id]['impact'];
    $impact = $enums[$impact_code];

    //143 seems to be the char length of an empty summarize field
    if (strlen($review) < 144) {
        continue;
    }


    ${"reviewer_$i"} = $record_result[$event_id];
    $reviewer_reports .= "<div class=\"container\">";
    $reviewer_reports .= "<h4>Reviewer ".$i.": ".$reviewer."</h4>";
    $reviewer_reports .= "<h4>Overall Evaluation : ".$impact."</h4>";
    $reviewer_reports .= $review;
    $reviewer_reports .= "</div>";

}


//Plugin::log($record_result,"DEBUG",  "GET DATA "); exit;
//print "<pre>" . print_r($record_result,  true) . "</pre>";

// Get Variable Or Empty string Fom _REQUEST
function voefr($var) {
    $result = isset($_REQUEST[$var]) ? $_REQUEST[$var] : "";
    $result = filter_var($_REQUEST[$var], FILTER_SANITIZE_STRING);
    return $result;
}

?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Reviewer Report for <?php echo $record?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link
            href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'
            rel='stylesheet' media='screen,print'>
</head>
<body>
<div class="container">
    <header class="page-header">
        <img src=<?php echo $module->getUrl("img/MCHRI_Logo_LeftAligned_TwoColor.png") ?> />
        <div class="col-xs-12">
            <div class="chri_logo"></div>
            <h1>Review Meeting Report</h1>
            <p class="lead">Program: <?php echo $program?></p>
            <p class="lead">Applicant name: <?php echo $name?></p>

        </div>
    </header>

    <div class="col-xs-12">
        <h3>Project Summary:</h3>
        <p class="summaries"><?php echo $project_summary?></p>
        <h3>Mentor Support Form:</h3>
        <div class="foo lead"><?php echo $mentor_summary?></div>
        <div class="foo lead"><?php echo $mentor_table?></div>
    </div>
    <div class="row hidden-print">
    </div>



    <div class="col-xs-12">
        <h3>Reviewer Reports:</h3>
        <?php echo $reviewer_reports;?>
    </div>
</div>
</body>

<style>
    img {
        background:url(<?php echo $module->getUrl("img/MCHRI_Logo_LeftAligned_TwoColor.png") ?>) 50% 50% no-repeat;
        height:100px;
        background-size:100%;
        margin-top: 10px;
    }
</style>