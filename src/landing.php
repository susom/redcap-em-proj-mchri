<?php

namespace Stanford\ProjMCHRI;
/** @var \Stanford\ProjMCHRI\ProjMCHRI $module */

use REDCap;



$sunet_id = $_SERVER['WEBAUTH_USER'];
//$sunet_id = 'test1';

$pid = $_GET['projectId'] ? $_GET['projectId']: $_GET['pid'];
$_GET['pid']=$pid;

//$module->emDebug($module->getUrl('src/landing.php', true, true));


$debug = $module->getProjectSetting('test', $pid);

if ($debug) {

    if (SUPER_USER) {
        $sunet_id = $module->getProjectSetting('test-user', $pid);
        $module->emDebug("User " . $sunet_id . "  is a superuser and is mimicking $sunet_id.");
    }

}


$module->emDebug("Starting MCHRI landing page for project $pid for reviewer $sunet_id");

//if sunet ID not set leave
if (!isset($sunet_id) && !$debug) {
    die("SUNet ID was not available. Please webauth in and try again!");
}

if (isset($_POST['download'])) {
    $module->emDebug("About to download downloading file");

    $edoc_id = $_POST['edoc_id'];
    $dl_status = $module->downloadfile($edoc_id);

    if ($dl_status == true) {
        $result = array(
            'result' => 'success',
            'msg' => 'Succesfully downloaded'
        );
    } else {
        $result = array(
            'result' => 'fail',
            'msg' => 'Not able to download'
        );
    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();
}

# loop through keeping those where sunet_fields contain $sunet_id
$flex_data = $module->prepareRows($sunet_id,$pid);

if ($flex_data == null) {
    ?>
    <div class="red" style="text-align: center"><b>Project ID is missing. Please notify your admin.</b> </div>
    <?php
    exit;
}

if (empty($flex_data)) {
    ?>
    <div class="red" style="text-align: center"><b>You have no applicants under review.</b> </div>
    <?php
    exit;
}



//do a datatable version
$review_grid = $module->generateReviewGrid($pid, $sunet_id, $flex_data);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MCHRI Application Review</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

<!-- Bootstrap core CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.css"/>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("css/mchri.css", true, true) ?>" />
<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.20/datatables.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
<script type='text/javascript' src='<?php echo $module->getUrl("js/mchri.js", true, true)?>'></script>
</head>
<body>
<header class="header-global">
    <nav class="container">
        <a class="som-logo" href="http://med.stanford.edu/mchri.html">Stanford Medicine</a>
    </nav>
</header>
<div class="container-fluid">
    <h3 style="text-align: center">Applicant Review for <?php echo $sunet_id ?> </h3>
    <br>
    <form method="POST" id="reviewer_update_form" action="">
    <?php print $review_grid;?>
    </form>
</div>

</body>
<script type="text/javascript">


    function redirectToSurvey(survey_link, return_code) {

        console.log("foo: "+survey_link);
        console.log("foo: "+return_code);

        var newForm = $('<form>', {
            'method': 'POST',
            'action': survey_link
        }).append($('<input>', {
            'name': '__code',
            'value': return_code,
            'type': 'hidden'
        })).appendTo('body');
        newForm.submit();

    };

    $(document).ready(function() {
        $('#review_table').DataTable( {
            "dom": '<f<t>i>'
        } );



    });
</script>
<style>
    .header-global nav a.som-logo{
        background:url(<?php echo $module->getUrl("img/MCHRI_Logo_LeftAligned_TwoColor.png", true, true) ?>) 50% 50% no-repeat;
        border-right: none;
        text-indent: -9999px;
        display: inline-block;
        width: 300px;
        /* width: 14.285714285714286rem; */
        height: 120px;
        height: 4.5rem;
        background-size: 90%;
    }
</style>

</html>


