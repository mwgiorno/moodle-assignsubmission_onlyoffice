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

    var openEditor = function(contextid, itemid, readonly, tmplkey = null, format = null, templatetype = null) {
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
        if (format) {
            params.format = format;
        }
        if (templatetype) {
            params.templatetype = templatetype;
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

    const reopenEditor = function(contextid, itemid, readonly, format, templatetype) {
        if (docEditor) {
            docEditor.destroyEditor();
        }

        const generatekeyurl = M.cfg.wwwroot + '/mod/assign/submission/onlyoffice/api/generatekey.php';
        $.getJSON(generatekeyurl).done(function(key) {
            const tmplkeyelement = document.querySelector("input[name='assignsubmission_onlyoffice_tmplkey']");
            tmplkeyelement.value = key;

            openEditor(contextid, itemid, readonly, key, format, templatetype);

            $("#app-onlyoffice").show();
        });
    };

    return {
        init: function(documentserverurl, contextid, itemid, readonly, tmplkey, templatetype) {
            var docsapijs = document.createElement('script');
            docsapijs.type = 'text/javascript';

            $(docsapijs).appendTo('#app-onlyoffice');
            $(docsapijs).on('load', function() {
                if (!tmplkey) {
                    openEditor(contextid, itemid, readonly, null, null, templatetype);
                } else {
                    let formatelement = $('input#id_assignsubmission_onlyoffice_format');
                    let selectformat = $('select#id_assignsubmission_onlyoffice_format');
                    let selecttemplatetype = $('select#id_assignsubmission_onlyoffice_template_type');
                    let templatetypeelement = $('input#id_assignsubmission_onlyoffice_template_type');
                    let enableapptoggle = $('input#id_assignsubmission_onlyoffice_enabled');
                    let tmplkeyelement = document.querySelector("input[name='assignsubmission_onlyoffice_tmplkey']");

                    if (enableapptoggle.length == 0) {
                        return;
                    }

                    if (
                        enableapptoggle[0].checked
                        && templatetypeelement.length > 0
                        && templatetypeelement.val() === 'custom'
                    ) {
                        openEditor(contextid, itemid, readonly, tmplkey);
                    }

                    enableapptoggle.change(function(e) {
                        if (
                            e.currentTarget.checked
                            && (formatelement.length > 0 && formatelement.val() !== 'upload'
                                || selectformat.length > 0 && selectformat.val() !== 'upload')
                            && selecttemplatetype === 'custom'
                        ) {
                            if (docEditor === null) {
                                openEditor(contextid, itemid, readonly, tmplkeyelement.val(), selectformat.val());
                            }

                            $("#app-onlyoffice").show();

                            return;
                        }

                        $("#app-onlyoffice").hide();
                    });

                    if (selectformat.length > 0) {
                        selectformat.change(function(e) {
                            if (e.currentTarget.value === 'upload') {
                                selecttemplatetype.val('custom').change();
                            } else {
                                if (selecttemplatetype.val() === 'custom') {
                                    reopenEditor(contextid, itemid, readonly, selectformat.val(), selecttemplatetype.val());
                                }
                            }
                        });
                    }

                    selecttemplatetype.change(function(e) {
                        if (e.currentTarget.value === 'custom' && selectformat.val() !== 'upload') {
                            reopenEditor(contextid, itemid, readonly, selectformat.val(), e.currentTarget.value);
                        } else {
                            if (docEditor) {
                                docEditor.destroyEditor();
                            }
                        }
                    });
                }
            });

            docsapijs.src = documentserverurl + '/web-apps/apps/api/documents/api.js';
        }
    };
});
