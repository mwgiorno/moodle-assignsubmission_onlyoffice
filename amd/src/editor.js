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
 * @module assignsubmission_onlyoffice/editor
 * @copyright  2022 Ascensio System SIA <integration@onlyoffice.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
define(['jquery'], function($) {
    return {
        init: function(contextid, itemid, groupmode) {
            // eslint-disable-next-line no-console
            console.log(contextid);
            // eslint-disable-next-line no-console
            console.log(itemid);
            // eslint-disable-next-line no-console
            console.log(groupmode);
            if (typeof DocsAPI === 'undefined') {
                return;
            }

            var ajaxUrl = M.cfg.wwwroot + '/mod/assign/submission/onlyoffice/api/editor.php';
            $.getJSON(ajaxUrl, {
                action: 'config',
                contextid: contextid,
                itemid: itemid,
                groupmode: groupmode
            }).done(function(config) {
                var docEditor = null;
                // eslint-disable-next-line no-console
                console.log(config);
                // eslint-disable-next-line no-undef
                docEditor = new DocsAPI.DocEditor("onlyoffice-editor", config);
                // eslint-disable-next-line no-console
                console.log(docEditor);
            });
        }
    };
});
