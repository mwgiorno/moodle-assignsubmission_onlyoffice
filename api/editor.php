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
 * @package    assignsubmission_onlyoffice
 * @subpackage
 * @copyright   2022 Ascensio System SIA <integration@onlyoffice.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../../config.php');
require_once(__DIR__.'/../../../locallib.php');

use mod_onlyofficeeditor\onlyoffice_file_utility;
use assignsubmission_onlyoffice\filemanager;

use Firebase\JWT\JWT;

global $USER;
global $DB;

$action = required_param('action', PARAM_STRINGID);
$contextid = required_param('contextid', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);
$readonly = !!optional_param('readonly', 0, PARAM_BOOL);
$tmplkey = optional_param('tmplkey', null, PARAM_ALPHANUMEXT);

$modconfig = get_config('onlyofficeeditor');

$context = null;
$assing = null;
$submission = null;
$file = null;
$groupmode = false;

$tmpleditmode = isset($tmplkey);

if ($contextid !== 0) {
    list($context, $course, $cm) = get_context_info_array($contextid);
    $assing = new assign($context, $cm, $course);
}

if (!$tmpleditmode) {
    $submission = $DB->get_record('assign_submission', array('id' => $itemid));
    if (!$submission) {
        http_response_code(400);
        die();
    }

    $groupmode = !!$submission->groupid;

    $file = filemanager::get($contextid, $itemid);
} else {
    $file = filemanager::get_template($contextid);
}

if ($file === null
    && !$tmpleditmode
    && $contextid !== 0) {
    http_response_code(404);
    die();
}

$filename = $file !== null ? $file->get_filename() : 'form_template.docxf';
$key = $file !== null ? filemanager::generate_key($file) : $tmplkey;

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$crypt = new \mod_onlyofficeeditor\hasher();
$downloadhash = $crypt->get_hash([
    'action' => 'download',
    'contextid' => $contextid,
    'itemid' => $itemid,
    'groupmode' => $groupmode,
    'tmplkey' => $tmplkey
]);

$config = [
    'document' => [
        'fileType' => $ext,
        'key' => $key,
        'title' => $filename,
        'url' => $CFG->wwwroot . '/mod/assign/submission/onlyoffice/download.php?doc=' . $downloadhash
    ],
    'documentType' => onlyoffice_file_utility::get_document_type('.' . $ext),
    'editorConfig' => [
        'user' => [
            'id' => $USER->id,
            'name' => \fullname($USER)
        ]
    ]
];

$canedit = in_array('.' . $ext, onlyoffice_file_utility::get_editable_extensions());

$editable = false;
if (!empty($assing) && !empty($submission)) {
    $editable = !$groupmode ? $assing->can_edit_submission($submission->userid) : $assing->can_edit_group_submission($submission->groupid);
} elseif ($tmpleditmode) {
    //To do checking permission for creating assign
    $editable = true;
}

$config['document']['permissions']['edit'] = $editable;
if ($editable && $canedit && !$readonly) {
    $callbackhash = $crypt->get_hash([
        'action' => 'track',
        'contextid' => $contextid,
        'itemid' => $itemid,
        'groupmode' => $groupmode,
        'tmplkey' => $tmplkey
    ]);
    $config['editorConfig']['callbackUrl'] = $CFG->wwwroot . '/mod/assign/submission/onlyoffice/callback.php?doc=' . $callbackhash;
} else {
    $viewable = $assing->can_grade() || $editable;

    if (!$viewable) {
        http_response_code(403);
        die();
    }

    $config['editorConfig']['mode'] = 'view';
}

if (!empty($modconfig->documentserversecret)) {
    $token = JWT::encode($config, $modconfig->documentserversecret);
    $config['token'] = $token;
}

echo json_encode($config);