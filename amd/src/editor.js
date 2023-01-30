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
    var docEditor = null;

    var openEditor = function(contextid, itemid, readonly, emptytmplkey = null) {
        if (typeof DocsAPI === 'undefined') {
            return;
        }

        var params = {
            action: 'config',
            contextid: contextid,
            itemid: itemid
        };

        if (readonly) {
            params.readonly = readonly;
        }
        if (emptytmplkey) {
            params.emptytmplkey = emptytmplkey;
        }

        var ajaxUrl = M.cfg.wwwroot + '/mod/assign/submission/onlyoffice/api/editor.php';
        $.getJSON(ajaxUrl, params).done(function(config) {

            // eslint-disable-next-line no-undef
            docEditor = new DocsAPI.DocEditor('onlyoffice-editor', config);
        });
    };

    return {
        init: function(contextid, itemid, readonly, emptytmplkey) {
            if (!emptytmplkey) {
                openEditor(contextid, itemid, readonly);
            } else {
                $('#id_assignsubmission_onlyoffice_format').change(function(e){
                    if (e.target.value != 'docxf') {
                        if ($("#app").is(":visible")) {
                            $("#app").hide();
                        }
                        return;
                    }

                    if (docEditor === null) {
                        openEditor(contextid, itemid, readonly, emptytmplkey);
                    }

                    $("#app").show();
                });
            }
        }
    };
});
