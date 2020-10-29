<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 9/18/17
 * Time: 8:13 PM
 */

$pid =isset($_REQUEST['pid']) ? $_REQUEST['pid'] : "";
$sunet_id =isset($_REQUEST['sunet_id']) ? $_REQUEST['sunet_id'] : "";
$field_name =isset($_REQUEST['field_name']) ? $_REQUEST['field_name'] : "";
$record =isset($_REQUEST['record']) ? $_REQUEST['record'] : "";
$event_id =isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : "";
$edoc_id =isset($_REQUEST['edoc_id']) ? $_REQUEST['edoc_id'] : "";

DEFINE ('NOAUTH',true);
$_GET['pid'] = $pid;

$module->emDebug("Reviewer portal: Started review filedown loaded by ".$sunet_id . " for project: $pid  record: $record for field $field_name and edoc : $doc_id");
REDCap::logEvent("Reviewer portal", "Review file downloaded by <$sunet_id> for field $field_name", null, $record, $event_id, $pid);

//Adapted code from DataEntry/file_download.php
//Need to lookup the application_type and doc_name
//Download file from the "edocs" web server directory

downloadfile($edoc_id);

/**
//Download from "edocs" folder (use default or custom path for storage)
$local_file = EDOC_PATH . $this_file['stored_name'];
if (file_exists($local_file) && is_file($local_file)) {
    header('Pragma: anytextexeptno-cache', true);
    if (isset($_GET['stream'])) {
        // Stream the file
        header('Content-Type: ' . mime_content_type($local_file));
        header('Content-Disposition: inline; filename="' . $this_file['doc_name'] . '"');
        header('Content-Length: ' . $this_file['doc_size']);
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');
        header('Connection: Keep-Alive');
        header('X-Pad: avoid browser bug');
        header('Content-Range: bytes 0-' . ($this_file['doc_size'] - 1) . '/' . $this_file['doc_size']);
    } else {
        // Download
        header('Content-Type: ' . $this_file['mime_type'] . '; name="' . $this_file['doc_name'] . '"');
        header('Content-Disposition: attachment; filename="' . $this_file['doc_name'] . '"');
    }
    ob_end_flush();
    //readfile_chunked($local_file);
    readfile($local_file);
    sleep(5);
    $module->emDebug("Just completed download: Reviewer portal: Review filedown loaded by ".$sunet_id . " for project: $pid  record: $record for field $field_name");

} else {
    die('<b>' . $lang['global_01'] . $lang['colon'] . '</b> ' . $lang['file_download_08'] . ' <b>"' . $local_file .
        '"</b> ("' . $this_file['doc_name'] . '") ' . $lang['file_download_09'] . '!');
}

$module->emDebug("file is $local_file with filename is " . $this_file['doc_name']);
 */



function downloadfile($edoc_id) {
    global $module;

    $pid = $module->getProjectId();

    $sql = "select * from redcap_edocs_metadata where doc_id = '" . $edoc_id . "' and delete_date is null";
    if (!empty($pid)) {
        $sql .= " and project_id = " . db_escape($pid);
    }
    $q = db_query($sql);

    $module->emDebug("locaal file $local_file exitst: ".file_exists($local_file) );
    $module->emDebug($sql);
    $this_file = db_fetch_assoc($q);

    //Download from "edocs" folder (use default or custom path for storage)
    $local_file = EDOC_PATH . $this_file['stored_name'];
    //$local_file = 'foo.txt';
    $module->emDebug("locaal file $local_file exitst: ".file_exists($local_file) );
    $module->emDebug("locaal file $local_file is_File: ".is_file($local_file) );

    if (file_exists($local_file) && is_file($local_file)) {

        header('Pragma: anytextexeptno-cache', true);
        header('Content-Type: ' . $this_file['mime_type'] . '; name="' . $this_file['doc_name'] . '"');
        header('Content-Disposition: attachment; filename="' . $this_file['doc_name'] . '"');
        ob_end_flush();
        readfile_chunked($local_file);
        //readfile($local_file);
        //sleep(5);
        $module->emDebug("Just completed download: Reviewer portal: Review filedown loaded by ".$sunet_id . " for project: $local_file");

    } else {
        die('<b>' . $lang['global_01'] . $lang['colon'] . '</b> ' . $lang['file_download_08'] . ' <b>"' . $local_file .
            '"</b> ("' . $this_file['doc_name'] . '") ' . $lang['file_download_09'] . '!');
    }

    $module->emDebug("file is $local_file with filename is " . $this_file['doc_name']);
}
