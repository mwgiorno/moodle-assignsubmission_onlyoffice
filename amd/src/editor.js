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
 * @copyright  2024 Ascensio System SIA <integration@onlyoffice.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
define(['jquery'], function($) {
    var docEditor = null;

    var openEditor = function(contextid, itemid, readonly, tmplkey = null) {
        if (typeof DocsAPI === 'undefined') {
            return;
        }

        var params = {
            contextid: contextid,
            itemid: itemid
        };

        if (readonly) {
            params.readonly = readonly;
        }
        if (tmplkey) {
            params.tmplkey = tmplkey;
        }

        var ajaxUrl = M.cfg.wwwroot + '/mod/assign/submission/onlyoffice/api/editorconfig.php';
        $.getJSON(ajaxUrl, params).done(function(config) {

            // eslint-disable-next-line no-undef
            docEditor = new DocsAPI.DocEditor('onlyoffice-editor', config);

            if ($('div#topofscroll').length > 0) {
                $('div#topofscroll').addClass('assignsubmission-onlyoffice-display');
            }
        });
    };

    return {
        init: function(documentserverurl, contextid, itemid, readonly, tmplkey) {
            var docsapijs = document.createElement('script');
            docsapijs.type = 'text/javascript';

            $(docsapijs).appendTo('#app-onlyoffice');
            $(docsapijs).on('load', function() {
                if (!tmplkey) {
                    openEditor(contextid, itemid, readonly);
                } else {
                    let formatelement = $('input#id_assignsubmission_onlyoffice_format');
                    let selectformat = $('select#id_assignsubmission_onlyoffice_format');
                    let enableapptoggle = $('input#id_assignsubmission_onlyoffice_enabled');

                    if (enableapptoggle.length == 0) {
                        return;
                    }

                    if (enableapptoggle[0].checked
                        && formatelement.length > 0
                        && formatelement[0].value == 'pdf') {
                        openEditor(contextid, itemid, readonly, tmplkey);
                    }

                    enableapptoggle.change(function(e) {
                        if (e.currentTarget.checked
                            && (formatelement.length > 0
                                && formatelement[0].value == 'pdf'
                                || selectformat.length > 0
                                && selectformat[0].value == 'pdf')) {

                            if (docEditor === null) {
                                openEditor(contextid, itemid, readonly, tmplkey);
                            }

                            $("#app-onlyoffice").show();

                            return;
                        }

                        $("#app-onlyoffice").hide();
                    });

                    if (selectformat.length > 0) {
                        selectformat.change(function(e) {
                            if (e.target.value != 'pdf') {
                                if ($("#app-onlyoffice").is(":visible")) {
                                    $("#app-onlyoffice").hide();
                                }
                                return;
                            }

                            if (docEditor === null) {
                                openEditor(contextid, itemid, readonly, tmplkey);
                            }

                            $("#app-onlyoffice").show();
                        });
                    }
                }
            });

            docsapijs.src = documentserverurl + '/web-apps/apps/api/documents/api.js';
        }
    };
});
