<?php

declare(strict_types=1);

/**
 * Teampass - a collaborative passwords manager.
 * ---
 * This file is part of the TeamPass project.
 * 
 * TeamPass is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 * 
 * TeamPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * 
 * Certain components of this file may be under different licenses. For
 * details, see the `licenses` directory or individual file headers.
 * ---
 * @file      utilities.logs.js.php
 * @author    Nils Laumaillé (nils@teampass.net)
 * @copyright 2009-2025 Teampass.net
 * @license   GPL-3.0
 * @see       https://www.teampass.net
 */


use TeampassClasses\PerformChecks\PerformChecks;
use TeampassClasses\ConfigManager\ConfigManager;
use TeampassClasses\SessionManager\SessionManager;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use TeampassClasses\Language\Language;

// Load functions
require_once __DIR__.'/../sources/main.functions.php';

// init
loadClasses();
$session = SessionManager::getSession();
$request = SymfonyRequest::createFromGlobals();
$lang = new Language($session->get('user-language') ?? 'english');

if ($session->get('key') === null) {
    die('Hacking attempt...');
}

// Load config
$configManager = new ConfigManager();
$SETTINGS = $configManager->getAllSettings();

// Do checks
$checkUserAccess = new PerformChecks(
    dataSanitizer(
        [
            'type' => htmlspecialchars($request->request->get('type', ''), ENT_QUOTES, 'UTF-8'),
        ],
        [
            'type' => 'trim|escape',
        ],
    ),
    [
        'user_id' => returnIfSet($session->get('user-id'), null),
        'user_key' => returnIfSet($session->get('key'), null),
    ]
);
// Handle the case
echo $checkUserAccess->caseHandler();
if ($checkUserAccess->checkSession() === false || $checkUserAccess->userAccessPage('utilities.logs') === false) {
    // Not allowed page
    $session->set('system-error_code', ERR_NOT_ALLOWED);
    include $SETTINGS['cpassman_dir'] . '/error.php';
    exit;
}
?>


<script type='text/javascript'>
    //<![CDATA[

    // Init
    var oTableConnections;
    var oTableItems;
    var oTableFailed;
    var oTableCopy;
    var oTableAdmin;
    var oTableErrors;

    // What type of form? Edit or new user
    browserSession(
        'init',
        'teampassApplication', {
            logData: 'connections',
        }
    );
    store.update(
        'teampassApplication',
        function(teampassApplication) {
            teampassApplication.logData = 'connections';
        }
    );

    // Prepare tooltips
    $('.infotip').tooltip();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        $('#selector-purge-action option[value="all"]').prop('selected', true);
        if (e.target.hash === '#connections') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'connections';
                }
            );
            $('#selector-purge-action').addClass('hidden');
        } else if (e.target.hash === '#failed') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'failed';
                }
            );
            $('#selector-purge-action').addClass('hidden');
            showFailed();
        } else if (e.target.hash === '#errors') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'errors';
                }
            );
            $('#selector-purge-action').addClass('hidden');
            showErrors();
        } else if (e.target.hash === '#copy') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'copy';
                }
            );
            $('#selector-purge-action').addClass('hidden');
            showCopy();
        } else if (e.target.hash === '#admin') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'admin';
                }
            );
            $('#selector-purge-action').addClass('hidden');
            showAdmin();
        } else if (e.target.hash === '#items') {
            store.update(
                'teampassApplication',
                function(teampassApplication) {
                    teampassApplication.logData = 'items';
                }
            );
            $('#selector-purge-action').removeClass('hidden');
            showItems();
        }
    });

    //Launch the datatables pluggin
    oTableConnections = $('#table-connections').dataTable({
        'retrieve': false,
        'orderCellsTop': true,
        'fixedHeader': true,
        'paging': true,
        'retrieve': true,
        'sPaginationType': 'listbox',
        'searching': true,
        'order': [
            [1, 'asc']
        ],
        'info': true,
        'processing': false,
        'serverSide': true,
        'responsive': true,
        'stateSave': true,
        'autoWidth': true,
        'ajax': {
            url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=connections',
            data: function(filter) {
                var val = $("select", "#table-items_filter").val();
                filter.search.column = val;
                return filter;
            }
        },
        'language': {
            'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
        },
        'preDrawCallback': function() {
            toastr.remove();
            toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
        },
        'drawCallback': function() {
            // Inform user
            toastr.remove();
            toastr.success(
                '<?php echo $lang->get('done'); ?>',
                '', {
                    timeOut: 1000
                }
            );
        },
    });

    /**
     * Undocumented function
     *
     * @return void
     */
    function showFailed() {
        oTableFailed = $('#table-failed').dataTable({
            'retrieve': false,
            'orderCellsTop': true,
            'fixedHeader': true,
            'paging': true,
            'retrieve': true,
            'sPaginationType': 'listbox',
            'searching': true,
            'order': [
                [1, 'asc']
            ],
            'info': true,
            'processing': false,
            'serverSide': true,
            'responsive': true,
            'stateSave': true,
            'autoWidth': true,
            'ajax': {
                url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=failed_auth',
                /*data: function(d) {
                    d.letter = _alphabetSearch
                }*/
            },
            'language': {
                'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
            },
            'preDrawCallback': function() {
                toastr.remove();
                toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
            },
            'drawCallback': function() {
                // Inform user
                toastr.remove();
                toastr.success(
                    '<?php echo $lang->get('done'); ?>',
                    '', {
                        timeOut: 1000
                    }
                );
            },
        });
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    function showErrors() {
        oTableErrors = $('#table-errors').dataTable({
            'retrieve': false,
            'orderCellsTop': true,
            'fixedHeader': true,
            'paging': true,
            'retrieve': true,
            'sPaginationType': 'listbox',
            'searching': true,
            'order': [
                [1, 'asc']
            ],
            'info': true,
            'processing': false,
            'serverSide': true,
            'responsive': true,
            'stateSave': true,
            'autoWidth': true,
            'ajax': {
                url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=errors',
                /*data: function(d) {
                    d.letter = _alphabetSearch
                }*/
            },
            'language': {
                'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
            },
            'preDrawCallback': function() {
                toastr.remove();
                toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
            },
            'drawCallback': function() {
                // Inform user
                toastr.remove();
                toastr.success(
                    '<?php echo $lang->get('done'); ?>',
                    '', {
                        timeOut: 1000
                    }
                );
            },
        });
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    function showCopy() {
        oTableCopy = $('#table-copy').dataTable({
            'retrieve': false,
            'orderCellsTop': true,
            'fixedHeader': true,
            'paging': true,
            'retrieve': true,
            'sPaginationType': 'listbox',
            'searching': true,
            'order': [
                [1, 'asc']
            ],
            'info': true,
            'processing': false,
            'serverSide': true,
            'responsive': true,
            'stateSave': true,
            'autoWidth': true,
            'ajax': {
                url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=copy',
                /*data: function(d) {
                    d.letter = _alphabetSearch
                }*/
            },
            'language': {
                'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
            },
            'preDrawCallback': function() {
                toastr.remove();
                toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
            },
            'drawCallback': function() {
                // Inform user
                toastr.remove();
                toastr.success(
                    '<?php echo $lang->get('done'); ?>',
                    '', {
                        timeOut: 1000
                    }
                );
            },
        });
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    function showAdmin() {
        oTableAdmin = $('#table-admin').dataTable({
            'retrieve': false,
            'orderCellsTop': true,
            'fixedHeader': true,
            'paging': true,
            'retrieve': true,
            'sPaginationType': 'listbox',
            'searching': true,
            'order': [
                [1, 'asc']
            ],
            'info': true,
            'processing': false,
            'serverSide': true,
            'responsive': true,
            'stateSave': true,
            'autoWidth': true,
            'ajax': {
                url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=admin',
                /*data: function(d) {
                    d.letter = _alphabetSearch
                }*/
            },
            'language': {
                'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
            },
            'preDrawCallback': function() {
                toastr.remove();
                toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
            },
            'drawCallback': function() {
                // Inform user
                toastr.remove();
                toastr.success(
                    '<?php echo $lang->get('done'); ?>',
                    '', {
                        timeOut: 1000
                    }
                );
            },
        });
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    function showItems() {
        /*$('#table-items thead tr').clone(true).appendTo( '#table-items thead' );
    $('#table-items thead tr:eq(1) th').each( function (i) {
        var title = $(this).text();
        $(this).html( '<input type="text" placeholder="Search '+title+'" style="width:100%;">' );
 
        $( 'input', this ).on( 'keyup change', function () {
            if ( table.column(i).search() !== this.value ) {
                table
                    .column(i)
                    .search( this.value )
                    .draw();
            }
        } );
    } );*/

        var columns = [{
                title: 'Date',
                column: 'l.date'
            },
            {
                title: 'ID',
                column: 'i.id'
            },
            {
                title: 'Label',
                column: 'i.label'
            },
            {
                title: 'Folder',
                column: 't.title'
            },
            {
                title: 'User',
                column: 'u.login'
            },
            {
                title: 'Action',
                column: 'l.action'
            }
        ];
        $("#table-items").one("preInit.dt", function() {
            $sel = $('<select class="form-control" id="items-search-column"></select>');
            $sel.html("<option value='all'>All Columns</option>");
            $.each(columns, function(i, opt) {
                $sel.append("<option value='" + opt.column + "'>" + opt.title + "</option>");
            });
            $("#table-items_filter label").append($sel);
        });

        oTableItems = $('#table-items').DataTable({
            'retrieve': false,
            'orderCellsTop': true,
            'fixedHeader': true,
            'paging': true,
            'retrieve': true,
            'sPaginationType': 'listbox',
            'searching': true,
            'order': [
                [1, 'asc']
            ],
            'info': true,
            'processing': false,
            'serverSide': true,
            'responsive': true,
            'stateSave': true,
            'autoWidth': true,
            'ajax': {
                url: '<?php echo $SETTINGS['cpassman_url']; ?>/sources/logs.datatables.php?action=items',
                data: function(filter) {
                    var val = $("select", "#table-items_filter").val();
                    filter.search.column = val;
                    return filter;
                }
            },
            'language': {
                'url': '<?php echo $SETTINGS['cpassman_url']; ?>/includes/language/datatables.<?php echo $session->get('user-language'); ?>.txt'
            },
            'preDrawCallback': function() {
                toastr.remove();
                toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');
            },
            'drawCallback': function() {
                // Inform user
                toastr.remove();
                toastr.success(
                    '<?php echo $lang->get('done'); ?>',
                    '', {
                        timeOut: 1000
                    }
                );
            },
        });

        $('#myTabContent').on('change', '#items-search-column', function() {
            oTableItems.ajax.reload();
        });
    }

    // iCheck for checkbox and radio inputs
    $('.card-footer input[type="checkbox"]').iCheck({
        checkboxClass: 'icheckbox_flat-blue'
    });

    // Build date range picker
    var dateRangeStart = '',
        dateRangeEnd = '';
    $('#purge-date-range')
        .daterangepicker({
            locale: {
                format: '<?php echo str_replace(['Y', 'm', 'd'], ['YYYY', 'MM', 'DD'], $SETTINGS['date_format']); ?>',
                applyLabel: '<?php echo $lang->get('apply'); ?>',
                cancelLabel: '<?php echo $lang->get('cancel'); ?>',
            }
        })
        .bind('keypress', function(e) {
            e.preventDefault();
        })
        .on('apply.daterangepicker', function(ev, picker) {
            dateRangeStart = picker.startDate.format('YYYY-MM-DD');
            dateRangeEnd = picker.endDate.format('YYYY-MM-DD');
        });;

    // Clear date range
    $('#clear-purge-date').click(function() {
        $('#purge-date-range').val('');
        $('.group-confirm-purge').addClass('hidden');
        $('#checkbox-purge-confirm').iCheck('uncheck');
    })

    // Show confirm purge
    $('.card-footer').on('change', '#purge-date-range', function() {
        if ($(this).val() !== '') {
            $('#checkbox-purge-confirm').iCheck('uncheck');
            $('.group-confirm-purge').removeClass('hidden');
        } else {
            $('.group-confirm-purge').addClass('hidden');
        }
    });

    // Now purge
    $('#button-perform-purge').click(function() {
        if ($('#checkbox-purge-confirm').prop('checked') === true) {
            // inform user
            toastr.remove();
            toastr.info('<?php echo $lang->get('loading_data'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');

            // Prepare data
            var data = {
                'dataType': store.get('teampassApplication').logData,
                'dateStart': dateRangeStart,
                'dateEnd': dateRangeEnd,
                'filter_user': $('#purge-filter-user').val(),
                'filter_action': $('#purge-filter-action').val(),
            }
            console.log(data);
            // Send query
            $.post(
                "sources/utilities.queries.php", {
                    type: "purge_logs",
                    data: prepareExchangedData(JSON.stringify(data), "encode", "<?php echo $session->get('key'); ?>"),
                    key: "<?php echo $session->get('key'); ?>"
                },
                function(data) {
                    data = prepareExchangedData(data, 'decode', '<?php echo $session->get('key'); ?>');
                    console.log(data);

                    if (data.error !== false) {
                        // Show error
                        toastr.error(
                            data.message,
                            '<?php echo $lang->get('caution'); ?>', {
                                timeOut: 5000,
                                progressBar: true
                            }
                        );
                    } else {
                        //console.log(store.get('teampassApplication').logData);
                        $('#checkbox-purge-confirm').iCheck('uncheck');
                        // Reload table
                        if (store.get('teampassApplication').logData === 'errors') {
                            oTableErrors.api().ajax.reload();
                        } else if (store.get('teampassApplication').logData === 'admin') {
                            oTableAdmin.api().ajax.reload();
                        } else if (store.get('teampassApplication').logData === 'connections') {
                            oTableConnections.api().ajax.reload();
                        } else if (store.get('teampassApplication').logData === 'failed') {
                            oTableFailed.api().ajax.reload();
                        } else if (store.get('teampassApplication').logData === 'items') {
                            oTableItems.ajax.reload();
                        } else if (store.get('teampassApplication').logData === 'copy') {
                            oTableCopy.api().ajax.reload();
                        }
                    }
                }
            );
        } else {
            toastr.remove();
            toastr.warning(
                '<?php echo $lang->get('please_confirm_by_clicking_checkbox'); ?>',
                '', {
                    timeOut: 5000,
                    progressBar: true
                }
            );
        }
    });

    //]]>
</script>
