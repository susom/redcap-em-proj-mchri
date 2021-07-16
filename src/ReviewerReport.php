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
    echo "<div class=“blue” style=\"text-align: center\"><b>Please use the Reviewer Report in context of a record.</b> </div>";
    exit();
}

//Getdata for the summarize field

$get_fields = array('applicant_name', 'reviewer_summary', 'program_v2');
$q = REDCap::getData('array',array($record), $get_fields);
//$module->emDebug($q);

$record_result = $q[$record];
//$review_events = array(67556, 67557, 67558, 67559, 67560);
$review_events = $module->getReviewerEvents();

$main_event_id = $module->getFirstEventId();
$main_event_name = REDCap::getEventNames(true, false, $main_event_id);

$name    = $record_result[$main_event_id]['applicant_name'];
$round_2 = $record_result[$main_event_id]['program_v2'];

$i = 0;

//change request July2021: if round 2 is set (not empty), then suppress 1-3
//change request 11Mar2021: if round 2 for program+v2 = Trainnee, suppress rounds  1-3
//Using program_v2 as proxy to signal that round 2 is triggered. in which case only display reviewers 4-6
//9mar2021: only suppress if round_2 is trainee (2)

//TODO: remove after UAT
$test_pid = $module->getProjectSetting('test-pid');

if ( in_array($module->getProjectId(), array($test_pid))) {

    if (!empty($round_2)) {
        $review_events = $module->getSubsettingFields('reviewer-r2-list', 'reviewer-r2-field');
        $i = 3;
    }
} else {
    if ($round_2 == "2") {
        $review_events = $module->getSubsettingFields('reviewer-r2-list', 'reviewer-r2-field');
        $i=3;
    }
}



$str = '';
foreach ($review_events as $event_id) {
    $i++;
    //$module->emDebug("$event_id event id at $i");

    $review = $record_result[$event_id]['reviewer_summary'];
    //143 seems to be the char length of an empty summarize field
    if (strlen($review) < 144) {
        continue;
    }
    //    Plugin::log(strlen($review), "DEBUG", "size thingy");
    ${"reviewer_$i"} = $record_result[$event_id];
    $str .= "<div class=\"container\">";
    $str .= "<h4>Reviewer ".$i."</h4>";
    $str .= $review;
    $str .= "</div>";

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
            <h1>Reviewer Report</h1>
            <p class="lead">Applicant name: <?php echo $name?></p>
        </div>
    </header>
    <div class="row hidden-print">
    </div>
</div>
<?php echo $str;?>
</body>

<style>
    img {
        background:url(<?php echo $module->getUrl("img/MCHRI_Logo_LeftAligned_TwoColor.png") ?>) 50% 50% no-repeat;
        height:100px;
        background-size:100%;
        margin-top: 10px;
    }
</style>