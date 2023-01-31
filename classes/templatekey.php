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

namespace assignsubmission_onlyoffice;

class templatekey {
    public static function get_contextid($tmplkey) {
        global $DB;

        $sql = "SELECT *
                FROM {assign_plugin_config}
                WHERE plugin = 'onlyoffice'
                AND name = 'tmplkey'
                AND value LIKE :tmplkey";

        $record = $DB->get_record_sql($sql, ['tmplkey' => $tmplkey . '%']);
        if (!$record) {
            return null;
        }

        $valuestmplkey = explode('_', $record->value);
        if ($valuestmplkey[0] !== $tmplkey) {
            return null;
        }

        $contextid = $valuestmplkey[1];

        return $contextid;
    }
}