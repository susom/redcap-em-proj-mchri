<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 9/18/17
 * Time: 8:13 PM
 */

namespace Stanford\ProjMCHRI;
/** @var \Stanford\ProjMCHRI\ProjMCHRI $module */

use REDCap;

$pid =isset($_REQUEST['pid']) ? $_REQUEST['pid'] : "";
$sunet_id =isset($_REQUEST['sunet_id']) ? $_REQUEST['sunet_id'] : "";
$field_name =isset($_REQUEST['field_name']) ? $_REQUEST['field_name'] : "";
$record =isset($_REQUEST['record']) ? $_REQUEST['record'] : "";
$event_id =isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : "";
$edoc_id =isset($_REQUEST['edoc_id']) ? $_REQUEST['edoc_id'] : "";

DEFINE ('NOAUTH',true);
$_GET['pid'] = $pid;

$module->emDebug("Reviewer portal: Started review filedown loaded by ".$sunet_id . " for project: $pid  record: $record for field $field_name and edoc : $edoc_id");
REDCap::logEvent("Reviewer portal", "Review file downloaded by <$sunet_id> for field $field_name", null, $record, $event_id, $pid);

//Adapted code from DataEntry/file_download.php
//Need to lookup the application_type and doc_name
//Download file from the "edocs" web server directory

downloadfile($edoc_id);


function downloadfile($edoc_id) {
    global $module;

    $pid = $module->getProjectId();

    $sql = "select * from redcap_edocs_metadata where doc_id = '" . $edoc_id . "' and delete_date is null";
    if (!empty($pid)) {
        $sql .= " and project_id = " . db_escape($pid);
    }
    $q = db_query($sql);

    $module->emDebug($sql);
    $this_file = db_fetch_assoc($q);

    //Download from "edocs" folder (use default or custom path for storage)
    $local_file = EDOC_PATH . $this_file['stored_name'];
    //$local_file = 'foo.txt';

    if (file_exists($local_file) && is_file($local_file)) {

        header('Pragma: anytextexeptno-cache', true);
        header('Content-Type: ' . $this_file['mime_type'] . '; name="' . $this_file['doc_name'] . '"');
        header('Content-Disposition: attachment; filename="' . $this_file['doc_name'] . '"');
        header('Content-Length: ' . filesize($local_file));

        ob_end_flush();
        readfile_chunked($local_file);

        $module->emDebug("Just completed download: Reviewer portal: Review file downloaded by  for project: $local_file");

    } else {
        die('<b>ERROR</b> The file <b>"' . $local_file .
            '"</b> ("' . $this_file['doc_name'] . '") ' . 'does not exist!');
    }

    $module->emDebug("file is $local_file with filename is " . $this_file['doc_name']);
}
