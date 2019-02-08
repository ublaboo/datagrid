import $ from 'jquery';

import ajaxCall from './utils/ajaxCall';

import sortable from './actions/sortable';
import shiftGroupSelection from './actions/shiftGroupSelection';
import groupActionMultiSelect from './actions/groupActionMultiSelect';
import filterMultiSelect from './actions/filterMultiSelect';
import sortableTree from './actions/sortableTree';

import './extensions';

import './datagrid.scss';

// Non-ajax confirmation
$(document).on('click', '[data-datagrid-confirm]:not(.ajax)', function (e) {
    if (!confirm($(e.target).closest('a').attr('data-datagrid-confirm'))) {
        e.stopPropagation();
        e.preventDefault();
    }
});

//  Datagrid auto submit
$(document).on('change', 'select[data-autosubmit-per-page]', function () {
    var button;
    button = $(this).parent().find('input[type=submit]');
    if (button.length === 0) {
        button = $(this).parent().find('button[type=submit]');
    }
    return button.click();
}).on('change', 'select[data-autosubmit]', function () {
    return $(this).closest('form').first().submit();
}).on('change', 'input[data-autosubmit][data-autosubmit-change]', function () {
    clearTimeout(window.datagrid_autosubmit_timer);
    const $this = $(this);
    return window.datagrid_autosubmit_timer = setTimeout((function () {
        return function () {
            return $this.closest('form').first().submit();
        };
    })(this), 200);
}).on('keyup', 'input[data-autosubmit]', function (e) {
    var $this, code;
    code = e.which || e.keyCode || 0;
    if ((code !== 13) && ((code >= 9 && code <= 40) || (code >= 112 && code <= 123))) {
        return;
    }
    clearTimeout(window.datagrid_autosubmit_timer);
    $this = $(this);
    return window.datagrid_autosubmit_timer = setTimeout((function () {
        return function () {
            return $this.closest('form').first().submit();
        };
    })(this), 200);
}).on('keydown', '.datagrid-inline-edit input', function (e) {
    var code;
    code = e.which || e.keyCode || 0;
    if (code === 13) {
        e.stopPropagation();
        e.preventDefault();
        return $(this).closest('tr').find('.col-action-inline-edit [name="inline_edit[submit]"]').click();
    }
});

// Datagrid manual submit
$(document).on('keydown', 'input[data-datagrid-manualsubmit]', function (e) {
    var code;
    code = e.which || e.keyCode || 0;
    if (code === 13) {
        e.stopPropagation();
        e.preventDefault();
        return $(this).closest('form').first().submit();
    }
});



shiftGroupSelection();

document.addEventListener('change', function (e) {
    var checked_inputs, counter, event, grid, i, ie, input, inputs, len, results, select, total;
    grid = e.target.getAttribute('data-check');
    if (grid) {
        checked_inputs = document.querySelectorAll('input[data-check-all-' + grid + ']:checked');
        select = document.querySelector('.datagrid-' + grid + ' select[name="group_action[group_action]"]');
        if (select) {
            counter = document.querySelector('.datagrid-' + grid + ' .datagrid-selected-rows-count');
            if (checked_inputs.length) {
                select.disabled = false;
                total = document.querySelectorAll('input[data-check-all-' + grid + ']').length;
                if (counter) {
                    counter.innerHTML = checked_inputs.length + '/' + total;
                }
            } else {
                select.disabled = true;
                select.value = '';
                if (counter) {
                    counter.innerHTML = '';
                }
            }
        }
        ie = window.navigator.userAgent.indexOf('MSIE ');
        if (ie) {
            event = document.createEvent('Event');
            event.initEvent('change', true, true);
        } else {
            event = new Event('change', {
                'bubbles': true
            });
        }
        if (select) {
            select.dispatchEvent(event);
        }
    }
    grid = e.target.getAttribute('data-check-all');
    if (grid) {
        inputs = document.querySelectorAll('input[type=checkbox][data-check-all-' + grid + ']');
        results = [];
        for (i = 0, len = inputs.length; i < len; i++) {
            input = inputs[i];
            input.checked = e.target.checked;
            ie = window.navigator.userAgent.indexOf('MSIE ');
            if (ie) {
                event = document.createEvent('Event');
                event.initEvent('change', true, true);
            } else {
                event = new Event('change', {
                    'bubbles': true
                });
            }
            results.push(input.dispatchEvent(event));
        }
        return results;
    }
});

$(sortable);


$(sortableTree);

$(document).on('click', '[data-datagrid-editable-url]', function (event) {
    var attr_name, attr_value, attrs, cell, cellValue, cell_height, cell_lines, cell_padding, input, line_height, submit, valueToEdit;
    cell = $(this);
    if (event.target.tagName.toLowerCase() === 'a') {
        return;
    }
    if (cell.hasClass('datagrid-inline-edit')) {
        return;
    }
    if (!cell.hasClass('editing')) {
        cell.addClass('editing');
        cellValue = cell.html().trim().replace('<br>', '\n');
        if (cell.attr('data-datagrid-editable-value')) {
            valueToEdit = cell.data('datagrid-editable-value');
        } else {
            valueToEdit = cellValue;
        }
        cell.data('originalValue', cellValue);
        cell.data('valueToEdit', valueToEdit);
        if (cell.data('datagrid-editable-type') === 'textarea') {
            input = $('<textarea>' + valueToEdit + '</textarea>');
            cell_padding = parseInt(cell.css('padding').replace(/[^-\d\.]/g, ''), 10);
            cell_height = cell.outerHeight();
            line_height = Math.round(parseFloat(cell.css('line-height')));
            cell_lines = (cell_height - (2 * cell_padding)) / line_height;
            input.attr('rows', Math.round(cell_lines));
        } else if (cell.data('datagrid-editable-type') === 'select') {
            input = $(cell.data('datagrid-editable-element'));
            input.find('option').each(function () {
                if ($(this).text() === valueToEdit) {
                    return input.find('option[value=\'' + valueToEdit + '\']').prop('selected', true);
                }
            });
        } else {
            input = $('<input type="' + cell.data('datagrid-editable-type') + '">');
            input.val(valueToEdit);
        }
        attrs = cell.data('datagrid-editable-attrs');
        for (attr_name in attrs) {
            attr_value = attrs[attr_name];
            input.attr(attr_name, attr_value);
        }
        cell.removeClass('edited');
        cell.html(input);
        submit = function (cell, el) {
            var value;
            value = el.val();
            if (value !== cell.data('valueToEdit')) {
                ajaxCall({
                    url: cell.data('datagrid-editable-url'),
                    data: {
                        value: value
                    },
                    type: 'POST',
                    success: function (payload) {
                        if (cell.data('datagrid-editable-type') === 'select') {
                            cell.html(input.find('option[value=\'' + value + '\']').html());
                        } else {
                            if (payload._datagrid_editable_new_value) {
                                value = payload._datagrid_editable_new_value;
                            }
                            cell.html(value);
                        }
                        return cell.addClass('edited');
                    },
                    error: function () {
                        cell.html(cell.data('originalValue'));
                        return cell.addClass('edited-error');
                    }
                });
            } else {
                cell.html(cell.data('originalValue'));
            }
            return setTimeout(function () {
                return cell.removeClass('editing');
            }, 1200);
        };
        cell.find('input,textarea,select').focus().on('blur', function () {
            return submit(cell, $(this));
        }).on('keydown', function (e) {
            if (cell.data('datagrid-editable-type') !== 'textarea') {
                if (e.which === 13) {
                    e.stopPropagation();
                    e.preventDefault();
                    return submit(cell, $(this));
                }
            }
            if (e.which === 27) {
                e.stopPropagation();
                e.preventDefault();
                cell.removeClass('editing');
                return cell.html(cell.data('originalValue'));
            }
        });
        return cell.find('select').on('change', function () {
            return submit(cell, $(this));
        });
    }
});



$(document).on('click', '[data-datagrid-toggle-inline-add]', function (e) {
    e.stopPropagation();
    e.preventDefault();

    const row = $(this).closest('.datagrid').find('.datagrid-row-inline-add');
    row.removeClass('datagrid-row-inline-add-hidden');
    row.find('input:not([readonly]),textarea:not([readonly])').first().focus();
});

$(document).on('mouseup', '[data-datagrid-cancel-inline-add]', function (e) {
    var code;
    code = e.which || e.keyCode || 0;
    if (code === 1) {
        e.stopPropagation();
        e.preventDefault();
        return $('.datagrid-row-inline-add').addClass('datagrid-row-inline-add-hidden');
    }
});



$(filterMultiSelect);
$(groupActionMultiSelect);

