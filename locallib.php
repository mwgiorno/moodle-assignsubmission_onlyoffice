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
        global $OUTPUT;

        $contextid = 0;
        $initeditor = true;
        $emptytmplkey = null;

        $assignconfig = new stdClass();
        $assignformat = [
            'docx' => get_string('docxformname', 'assignsubmission_onlyoffice'),
            'xlsx' => get_string('xlsxformname', 'assignsubmission_onlyoffice'),
            'pptx' => get_string('pptxformname', 'assignsubmission_onlyoffice'),
            'docxf' => get_string('docxfformname', 'assignsubmission_onlyoffice'),
        ];

        $mform->addElement('select', 'assignsubmission_onlyoffice_format',
            get_string('assignformat', 'assignsubmission_onlyoffice'), $assignformat);

        if ($this->assignment->has_instance()) {
            $assignconfig = $this->get_config();

            if (isset($assignconfig->format)
                && array_key_exists($assignconfig->format, $assignformat)) {
                $mform->getElement('assignsubmission_onlyoffice_format')->setSelected($assignconfig->format);
                $mform->freeze('assignsubmission_onlyoffice_format');

                $initeditor = isset($assignconfig->format) && $assignconfig->format === 'docxf';
            }

            $contextid = $this->assignment->get_context()->id;
        }

        if ($initeditor) {
            $tmplkey = uniqid();
            $mform->addElement('hidden', 'assignsubmission_onlyoffice_tmplkey', $tmplkey);

            $documentserverurl = get_config('onlyofficeeditor', 'documentserverurl');
            $mform->addElement('html', $OUTPUT->render(
                new content($documentserverurl, $contextid, 0, false, $tmplkey)
            ));
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

        if (isset($data->assignsubmission_onlyoffice_tmplkey)
            && $data->assignsubmission_onlyoffice_format === 'docxf') {
            $this->set_config('tmplkey', $data->assignsubmission_onlyoffice_tmplkey . '_' . $this->assignment->get_context()->id);
        }

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
        global $OUTPUT;

        $cfg = $this->get_config();

        $documentserverurl = get_config('onlyofficeeditor', 'documentserverurl');
        $contextid = $this->assignment->get_context()->id;

        $submissionfile = filemanager::get($contextid, $submission->id);
        if ($submissionfile === null) {
            $submissionfile = filemanager::create($contextid, $submission->id, $cfg->format, $submission->userid);
        }

        $mform->addElement('html', $OUTPUT->render(
            new content($documentserverurl, $contextid, $submission->id)
        ));

        return true;
    }

    /**
     * View submission - the submission file will always be read only.
     *
     * @param stdClass $submission
     * @return string - html frame of the submitted file.
     */
    public function view(stdClass $submission) {
        global $OUTPUT;

        $documentserverurl = get_config('onlyofficeeditor', 'documentserverurl');
        $contextid = $this->assignment->get_context()->id;

        $submissionfile = filemanager::get($contextid, $submission->id);
        if ($submissionfile === null) {
            return get_string('filenotfound', 'assignsubmission_onlyoffice');
        }

        $html = $OUTPUT->render(new content($documentserverurl, $contextid, $submission->id, true));

        return $html;
    }

    /**
     * View the submission summary and sets whether a view link be provided.
     *
     * @param stdClass $submission
     * @param bool $showviewlink - whether or not to have a link to view the submission file.
     * @return string view text.
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $showviewlink = true;

        return get_string('expanddocument', 'assignsubmission_onlyoffice');
    }

    /**
     * Is this assignment plugin empty?(ie no submission)
     *
     * @param stdClass $submission assign_submission.
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $contextid = $this->assignment->get_context()->id;

        $submissionfile = filemanager::get($contextid, $submission->id);
        if ($submissionfile === null) {
            return true;
        }

        return false;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     *
     * @return array - An array of fileareas(keys) and descriptions(values)
     */
    public function get_file_areas() {
        return array(
            filemanager::FILEAREA_ONLYOFFICE_SUBMISSION_FILE => $this->get_name()
        );
    }

    /**
     * Produce a list of files suitable for export that represent this submission.
     *
     * @param stdClass $submission
     * @param stdClass $user
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = [];

        $contextid = $this->assignment->get_context()->id;

        $submissionfile = filemanager::get($contextid, $submission->id);
        if ($submissionfile !== null) {
            $filename = $submissionfile->get_filename();
            $result[$filename] = $submissionfile;
        }

        return $result;
    }

    /**
     * Remove any saved data from this submission.
     *
     * @param stdClass $submission - assign_submission data
     * @return void
     */
    public function remove(stdClass $submission) {
        $contextid = $this->assignment->get_context()->id;

        filemanager::delete($contextid, $submission->id);
    }
}