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
 * @copyright   2023 Ascensio System SIA <integration@onlyoffice.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlyoffice\output;

class content implements \renderable, \templatable {
    /** @var \stdClass $data */
    private $data;

    public function __construct(string $documentserverurl,
                                int $contextid,
                                string $itemid,
                                bool $readonly = false,
                                string $tmplkey = null) {

        $this->data = new \stdClass();

        $this->data->documentserverurl = $documentserverurl;
        $this->data->contextid = $contextid;
        $this->data->itemid = $itemid;
        $this->data->readonly = $readonly;
        $this->data->tmplkey = $tmplkey;
    }

    public function export_for_template(\renderer_base $output) {
        global $PAGE;

        $jsparams = [
            $this->data->documentserverurl,
            $this->data->contextid,
            $this->data->itemid,
            $this->data->readonly,
            $this->data->tmplkey
        ];

        $PAGE->requires->js_call_amd('assignsubmission_onlyoffice/editor', 'init', $jsparams);

        return $this->data;
    }
}
