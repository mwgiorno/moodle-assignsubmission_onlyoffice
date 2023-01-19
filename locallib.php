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
 * The assign_submission_onlyoffice class
 *
 * @package    assignsubmission_onlyoffice
 * @copyright  2022 Ascensio System SIA <integration@onlyoffice.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_onlyoffice\filemanager;
use assignsubmission_onlyoffice\output\content;

/**
 * Library class for onlyoffice submission plugin extending submission plugin base class
 */
class assign_submission_onlyoffice extends assign_submission_plugin {

    /**
     * Should return the name of this plugin type.
     *
     * @return string - the name
     */
    public function get_name() {
        return get_string('pluginname', 'assignsubmission_onlyoffice');
    }

    /**
     * Get the default setting for submission plugin.
     *
     * @param MoodleQuickForm $mform - The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $assignconfig = new stdClass();
        $assignformat = [
            'docx' => get_string('docxformname', 'assignsubmission_onlyoffice'),
            'xlsx' => get_string('xlsxformname', 'assignsubmission_onlyoffice'),
            'pptx' => get_string('pptxformname', 'assignsubmission_onlyoffice'),
        ];

        $mform->addElement('select', 'assignsubmission_onlyoffice_format',
            get_string('assignformat', 'assignsubmission_onlyoffice'), $assignformat);

        if ($this->assignment->has_instance()) {
            $assignconfig = $this->get_config();

            if (isset($assignconfig->format)
                && array_key_exists($assignconfig->format, $assignformat)) {
                $mform->getElement('assignsubmission_onlyoffice_format')->setSelected($assignconfig->format);
            }
        }

        $mform->hideif('assignsubmission_onlyoffice_format', 'assignsubmission_onlyoffice_enabled', 'notchecked');
    }

    /**
     * Save the settings for submission plugin.
     *
     * @param stdClass $data - the form data.
     * @return bool - on error the subtype should call set_error and return false.
     */
    public function save_settings(stdClass $data) {
        $this->set_config('format', $data->assignsubmission_onlyoffice_format);

        return true;
    }

    /**
     * Get any additional fields for the submission form for this assignment.
     *
     * @param stdClass $submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data - the form data.
     * @param int $userid
     * @return boolean - true if we added anything to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data, $userid = null) {
        global $USER;
        global $OUTPUT;

        $cfg = $this->get_config();

        if (is_null($userid)) {
            $userid = !empty($data->userid) ? $data->userid : $USER->id;
        }

        $groupmode = !!$submission->groupid;

        $itemid = $userid;
        if ($groupmode) {
            $itemid = $submission->groupid;
        }

        $documentserverurl = get_config('onlyofficeeditor', 'documentserverurl');
        $submissionid = $submission->id;
        $contextid = $this->assignment->get_context()->id;

        $submissionfile = filemanager::get($contextid, $itemid, $groupmode);
        if ($submissionfile === null) {
            $submissionfile = filemanager::create($contextid, $itemid, $cfg->format, $groupmode);
        }

        $mform->addElement('html', $OUTPUT->render(
            new content($documentserverurl, $contextid, $itemid, $groupmode)
        ));

        return true;
    }

    /**
     * Is this assignment plugin empty?(ie no submission)
     *
     * @param stdClass $submission assign_submission.
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return false;
    }

    /**
     * Remove any saved data from this submission.
     *
     * @since Moodle 3.6
     * @param stdClass $submission - assign_submission data
     * @return void
     */
    public function remove(stdClass $submission) {
        $itemid = $submission->userid;

        $groupmode = !!$submission->groupid;
        if ($groupmode) {
            $itemid = $submission->groupid;
        }

        $contextid = $this->assignment->get_context()->id;

        filemanager::delete($contextid, $itemid, $groupmode);
    }
}