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

use stored_file;

class filemanager {

    const FILEAREA_USER = 'user';
    const FILEAREA_GROUP = 'group';
    const FILEAREA_DRAFT = 'draft';

    const COMPONENT_NAME = 'assignsubmission_onlyoffice';

    public static function get(int $contextid, string $itemid, bool $isgroupmode = false) {
        $fs = get_file_storage();

        $files = $fs->get_area_files($contextid, self::COMPONENT_NAME,
            $isgroupmode ? self::FILEAREA_GROUP : self::FILEAREA_USER,
            $itemid, '', false, 0, 0, 1);

        $file = reset($files);
        if (!$file) {
            return null;
        }

        return $file;
    }

    public static function create(int $contextid, string $itemid, string $ext, bool $isgroupmode = false) {
        global $USER;
        global $CFG;

        $pathlocale = \mod_onlyofficeeditor\util::PATH_LOCALE[$USER->lang];
        if ($pathlocale === null) {
            $pathlocale = 'en-US';
        }

        $pathname = $CFG->dirroot . '/mod/onlyofficeeditor/newdocs/' . $pathlocale . '/new.' . $ext;

        $fs = get_file_storage();
        $newfile = $fs->create_file_from_pathname((object)[
            'contextid' => $contextid,
            'component' => self::COMPONENT_NAME,
            'filearea' => $isgroupmode ? self::FILEAREA_GROUP : self::FILEAREA_USER,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $itemid . '.' . $ext
        ], $pathname);

        return $newfile;
    }

    public static function write(stored_file $file, string $url) {
        $fs = get_file_storage();

        $fr = array(
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => self::FILEAREA_DRAFT,
            'itemid' => $file->get_itemid(),
            'filename' => $file->get_filename() . '_temp',
            'filepath' => '/',
            'userid' => $file->get_userid(),
            'timecreated' => $file->get_timecreated()
        );

        $tmpfile = $fs->create_file_from_url($fr, $url);
        $file->replace_file_with($tmpfile);
        $file->set_timemodified(time());
        $tmpfile->delete();
    }
}