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

$pid =isset($_REQUEST['projectId']) ? $_REQUEST['projectId'] : "";
$sunet_id =isset($_REQUEST['sunet_id']) ? $_REQUEST['sunet_id'] : "";
$field_name =isset($_REQUEST['field_name']) ? $_REQUEST['field_name'] : "";
$record =isset($_REQUEST['record']) ? $_REQUEST['record'] : "";
$edoc_id =isset($_REQUEST['eid']) ? $_REQUEST['eid'] : "";

DEFINE ('NOAUTH',true);
$_GET['pid'] = $pid;

$module->emDebug("Reviewer portal: Started review filedown loaded by ".$sunet_id . " for project: $pid  record: $record for field $field_name and edoc : $edoc_id");
REDCap::logEvent("Reviewer portal", "Review file downloaded by <$sunet_id> for field $field_name", null, $record, null, $pid);

//Adapted code from DataEntry/file_download.php
//Need to lookup the application_type and doc_name
//Download file from the "edocs" web server directory

downloadfile($edoc_id, $pid);


function downloadfile($edoc_id, $pid)
{
    global $module;

    //TODO i should be able to get this from redcap settings
    //$edoc_storaage_option = '0'; "LOCAL";
    $edoc_storage_option = '5'; //"GCP";

    $sql = "select * from redcap_edocs_metadata where doc_id = '" . $edoc_id . "' and delete_date is null";
    if (!empty($pid)) {
        $sql .= " and project_id = " . db_escape($pid);
    }
    $q = db_query($sql);

    //$module->emDebug($sql);
    $this_file = db_fetch_assoc($q);

    //Download from "edocs" folder (use default or custom path for storage)
    $local_file = EDOC_PATH . $this_file['stored_name'];
    //$local_file = 'foo.txt';

    if ($edoc_storage_option == '0') {

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
    } elseif ($edoc_storage_option == '5') {

        //$module->emDebug("GLOBALS", $GLOBALS['google_cloud_storage_api_bucket_name']);
        //$module->emDebug("stored_name", $this_file['stored_name']);

        //UPDATE FOR GOOGLECLOUD
        $googleClient = \Files::googleCloudStorageClient();
        $bucket = $googleClient->bucket($GLOBALS['redcap-edocs-dev']);
        $googleClient->registerStreamWrapper();

        //$data = file_get_contents('gs://'.$GLOBALS['google_cloud_storage_api_bucket_name'].'/' . $this_file['stored_name']);
        // example: $data = file_get_contents('gs://'.$GLOBALS['redcap-edocs-dev'].'/' . '21085/20201130113010_pid21085_zyaQHi.xlsx';

        //from REDCap : DataEntry/file_download.php
        header('Pragma: anytextexeptno-cache', true);
        // Set CSP header (very important to prevent reflected XSS)
        header("Content-Security-Policy: script-src 'none'");
        if (isset($_GET['stream'])) {
            // Stream the file (e.g. audio)
            header('Content-Type: '.mime_content_type(APP_PATH_TEMP . $this_file['stored_name']));
            header('Content-Disposition: inline; filename="'.$this_file['doc_name'].'"');
            header('Content-Length: ' . $this_file['doc_size']);
            header("Content-Transfer-Encoding: binary");
            header('Accept-Ranges: bytes');
            header('Connection: Keep-Alive');
            header('X-Pad: avoid browser bug');
            header('Content-Range: bytes 0-'.($this_file['doc_size']-1).'/'.$this_file['doc_size']);
        } else {
            // Download
            header('Content-Type: '.$this_file['mime_type'].'; name="'.$this_file['doc_name'].'"');
            header('Content-Disposition: attachment; filename="'.$this_file['doc_name'].'"');
        }
        ob_start();ob_end_flush();
        readfile_chunked('gs://'.$GLOBALS['google_cloud_storage_api_bucket_name'].'/' . $this_file['stored_name']);

        $module->emDebug("Just completed GCP download: Reviewer portal: Review file downloaded by  for project: $local_file");
        //$module->emDebug('gs://'.$GLOBALS['redcap-edocs-dev'].'/' . $this_file['stored_name']);

    }
}
