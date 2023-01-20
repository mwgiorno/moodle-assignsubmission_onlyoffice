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

$action = required_param('action', PARAM_STRINGID);
$contextid = required_param('contextid', PARAM_INT);
$itemid = required_param('itemid', PARAM_ALPHANUMEXT);
$groupmode = !!optional_param('groupmode', 0, PARAM_BOOL);
$readonly = !!optional_param('readonly', 0, PARAM_BOOL);

$modconfig = get_config('onlyofficeeditor');

$submissionfile = filemanager::get($contextid, $itemid, $groupmode);
if ($submissionfile === null) {
    http_response_code(404);
    die();
}

list($context, $course, $cm) = get_context_info_array($contextid);
$assing = new assign($context, $cm, $course);

$filename = $submissionfile->get_filename();
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$crypt = new \mod_onlyofficeeditor\hasher();
$downloadhash = $crypt->get_hash(['action' => 'download', 'contextid' => $contextid, 'itemid' => $itemid, 'groupmode' => $groupmode]);

$config = [
    'document' => [
        'fileType' => $ext,
        'key' => $contextid . $itemid . $submissionfile->get_timemodified(),
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
if (!$groupmode) {
    $editable = $assing->can_edit_submission($itemid);
} else {
    $editable = $assing->can_edit_group_submission($itemid);
}

$config['document']['permissions']['edit'] = $editable;
if ($editable && $canedit && !$readonly) {
    $callbackhash = $crypt->get_hash(['action' => 'track', 'contextid' => $contextid, 'itemid' => $itemid, 'groupmode' => $groupmode]);
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