<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The assign_submission_onlyoffice download handler
 *
 * @package    assignsubmission_onlyoffice
 * @copyright  2022 Ascensio System SIA <integration@onlyoffice.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../locallib.php');

use curl;

use mod_onlyofficeeditor\util;
use assignsubmission_onlyoffice\filemanager;
use assignsubmission_onlyoffice\templatekey;

global $USER;
global $DB;

$doc = required_param('doc', PARAM_RAW);

$crypt = new \mod_onlyofficeeditor\hasher();
list($hash, $error) = $crypt->read_hash($doc);

if ($error || $hash === null) {
    http_response_code(403);
    die();
}

if ($hash->action !== 'track') {
    http_response_code(400);
    die();
}

$contextid = $hash->contextid;
$itemid = $hash->itemid;
$groupmode = $hash->groupmode;
$tmplkey = $hash->tmplkey;

$bodyStream = file_get_contents('php://input');
$data = json_decode($bodyStream);

$status = $data->status;
$url = isset($data->url) ? $data->url : null;
$users = isset($data->users) ? $data->users : null;

$modconfig = get_config('onlyofficeeditor');
if (!empty($modconfig->documentserversecret)) {
    if (!empty($data->token)) {
        try {
            $payload = \Firebase\JWT\JWT::decode($data->token, $modconfig->documentserversecret, array('HS256'));
        } catch (\UnexpectedValueException $e) {
            $response['status'] = 'error';
            $response['error'] = '403 Access denied';
            die(json_encode($response));
        }
    } else {
        $token = substr(getallheaders()['Authorization'], strlen('Bearer '));
        try {
            $decodedheader = \Firebase\JWT\JWT::decode($token, $modconfig->documentserversecret, array('HS256'));

            $payload = $decodedheader->payload;
        } catch (\UnexpectedValueException $e) {
            $response['status'] = 'error';
            $response['error'] = '403 Access denied';
            die(json_encode($response));
        }
    }

    $status = $payload->status;
    $url = isset($payload->url) ? $payload->url : null;
    $users = isset($payload->users) ? $payload->users : null;
}

$status = $data->status;
$url = isset($data->url) ? $data->url : null;
$users = isset($data->users) ? $data->users : null;

$result = 1;
switch ($status) {
    case util::STATUS_MUSTSAVE:
    case util::STATUS_ERRORSAVING:
        $file = null;
        $canwrite = false;

        $userid = $users[0];
        $user = \core_user::get_user($userid);
        if ($user) {
            $USER = $user;
        }

        if ($contextid === 0) {
            $contextid = templatekey::get_contextid($tmplkey);
        }
        if ($contextid === 0) {
            http_response_code(400);
            die();
        }

        list($context, $course, $cm) = get_context_info_array($contextid);
        if (isset($tmplkey)) {
            $canwrite = has_capability('moodle/course:manageactivities', $context);
        } else {
            $assing = new assign($context, $cm, $course);
            $submission = $DB->get_record('assign_submission', array('id' => $itemid));
            if ($submission) {
                $canwrite = !$groupmode ? $assing->can_edit_submission($submission->userid) : $assing->can_edit_group_submission($submission->groupid);
            }
        }

        if (!$canwrite) {
            http_response_code(403);
            die();
        }

        $file = !isset($tmplkey) ? filemanager::get($contextid, $itemid, $groupmode) : filemanager::get_template($contextid);
        if (empty($file) && isset($tmplkey)) {
            $file = filemanager::create_template($contextid, 'docxf', $itemid);
        }

        if (empty($file)) {
            http_response_code(404);
            die();
        }

        filemanager::write($file, $url);

        $filename = $file->get_filename();
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === 'docxf') {
            $curl = new curl();
            $curl->setHeader(['Accept: application/json']);

            $crypt = new \mod_onlyofficeeditor\hasher();
            $downloadhash = $crypt->get_hash([
                'action' => 'download',
                'contextid' => $contextid,
                'itemid' => 0,
                'tmplkey' => $tmplkey
            ]);

            $documenturi = $CFG->wwwroot . '/mod/assign/submission/onlyoffice/download.php?doc=' . $downloadhash;

            $conversionbody = (object)[
                "async" => false,
                "url" => $documenturi,
                "outputtype" => 'oform',
                "filetype" => 'docxf',
                "title" => $filename,
                "key" => $contextid . $itemid . $file->get_timemodified()
            ];

            $conversionbody = json_encode($conversionbody);

            $documentserverurl = get_config('onlyofficeeditor', 'documentserverurl');
            $conversionurl = $documentserverurl . '/ConvertService.ashx';

            $response = $curl->post($conversionurl, $conversionbody);

            $conversionjson = json_decode($response);
            if ($conversionjson->error) {
                break;
            }

            $initialfile = filemanager::get_initial($contextid);
            if ($initialfile === null) {
                filemanager::create_initial($contextid, 'oform', 0, $conversionjson->fileUrl);
            } else {
                filemanager::write($initialfile, $conversionjson->fileUrl);
            }
        }

        $result = 0;
        break;

    case util::STATUS_EDITING:
    case util::STATUS_CLOSEDNOCHANGES:
        $result = 0;
        break;
}

http_response_code(200);
echo(json_encode(['error' => $result]));