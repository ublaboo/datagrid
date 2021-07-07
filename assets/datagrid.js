var dataGridRegisterExtension, dataGridRegisterAjaxCall, dataGridLoad, dataGridSubmitForm;

if (typeof naja !== "undefined") {
	var isNaja2 = function () { return naja && naja.VERSION && naja.VERSION >= 2 };
	var najaEventParams = function (params) { return isNaja2() ? params.detail : params };
	var najaRequest = function (params) { return isNaja2() ? params.detail.request : params.xhr };
	dataGridRegisterExtension = function (name, extension) {
		var init = extension.init;
		var success = extension.success;
		var before = extension.before;
		var complete = extension.complete;
		var interaction = extension.interaction;


		var NewExtension = function NewExtension(naja, name) {
			this.name = name;

			this.initialize = function (naja) {
				if(init) {
					naja.addEventListener('init', function (params)  {
						init(najaEventParams(params).defaultOptions);
					});
				}

				if(success) {
					naja.addEventListener('success', function (params)  {
						var payload = isNaja2() ? params.detail.payload : params.response;
						success(payload, najaEventParams(params).options);
					});
				}

				var interactionTarget = naja;
				if (isNaja2()) {
					interactionTarget = interactionTarget.uiHandler;
				}

				interactionTarget.addEventListener('interaction', function (params) {
					if (isNaja2()) {
						params.detail.options.nette = {
							el: $(params.detail.element)
						}
					} else {
						params.options.nette = {
							el: $(params.element)
						}
					}
					if (interaction) {
						if (!interaction(najaEventParams(params).options)){
							params.preventDefault();
						}
					}
				});

				if(before) {
					naja.addEventListener('before', function (params) {
						if (!before(najaRequest(params), najaEventParams(params).options))
							params.preventDefault();
					});
				}

				if(complete) {
					naja.addEventListener('complete', function (params) {
						complete(najaRequest(params), najaEventParams(params).options);
					});
				}
			}
			if (!isNaja2()) {
				this.initialize(naja);
			}
			return this;
		}

		if (isNaja2()) {
			naja.registerExtension(new NewExtension(null, name));
		} else {
			naja.registerExtension(NewExtension, name);
		}
	};


	dataGridRegisterAjaxCall = function (params) {
        var method = params.type || 'GET';
        var data = params.data || null;

		naja.makeRequest(method, params.url, data, {})
			.then(params.success)
			.catch(params.error);
	};

	dataGridLoad = function () {
		naja.load();
	};

	dataGridSubmitForm = function (form) {
		return naja.uiHandler.submitForm(form.get(0));
	};
} else if ($.nette) {
	dataGridRegisterExtension = function (name, extension) {
		$.nette.ext(name, extension);
	};
	dataGridRegisterAjaxCall = function (params) {
		$.nette.ajax(params);
	};
	dataGridLoad = function () {
		$.nette.load();
	};
	dataGridSubmitForm = function (form) {
		return form.submit();
	};
} else {
	throw new Error("Include Naja.js or nette.ajax for datagrids to work!")
}


var datagridFitlerMultiSelect, datagridGroupActionMultiSelect, datagridShiftGroupSelection, datagridSortable, datagridSortableTree, getEventDomPath,
	indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

$(document).on('click', '[data-datagrid-confirm]:not(.ajax)', function(e) {
	if (!confirm($(e.target).closest('a').attr('data-datagrid-confirm'))) {
		e.stopPropagation();
		return e.preventDefault();
	}
});

if (typeof naja !== "undefined") {
	dataGridRegisterExtension('datagrid.confirm', {
		interaction: function(settings) {
			var confirm_message;
			if (settings.nette) {
				confirm_message = settings.nette.el.data('datagrid-confirm');
				if (confirm_message) {
					return confirm(confirm_message);
				}
			}
			return true;
		}
	});
} else {
	dataGridRegisterExtension('datagrid.confirm', {
		before: function(xhr, settings) {
			var confirm_message;
			if (settings.nette) {
				confirm_message = settings.nette.el.data('datagrid-confirm');
				if (confirm_message) {
					return confirm(confirm_message);
				}
			}
			return true;
		}
	});
}


$(document).on('change', 'select[data-autosubmit-per-page]', function() {
	var button;
	button = $(this).parent().find('input[type=submit]');
	if (button.length === 0) {
		button = $(this).parent().find('button[type=submit]');
	}
	return button.click();
}).on('change', 'select[data-autosubmit]', function() {
	return dataGridSubmitForm($(this).closest('form').first());
}).on('change', 'input[data-autosubmit][data-autosubmit-change]', function(e) {
	var $this, code;
	code = e.which || e.keyCode || 0;
	clearTimeout(window.datagrid_autosubmit_timer);
	$this = $(this);
	return window.datagrid_autosubmit_timer = setTimeout((function(_this) {
		return function() {
			return dataGridSubmitForm($this.closest('form').first());
		};
	})(this), 200);
}).on('keyup', 'input[data-autosubmit]', function(e) {
	var $this, code;
	code = e.which || e.keyCode || 0;
	if ((code !== 13) && ((code >= 9 && code <= 40) || (code >= 112 && code <= 123))) {
		return;
	}
	clearTimeout(window.datagrid_autosubmit_timer);
	$this = $(this);
	return window.datagrid_autosubmit_timer = setTimeout((function(_this) {
		return function() {
			return dataGridSubmitForm($this.closest('form').first());
		};
	})(this), 200);
}).on('keydown', '.datagrid-inline-edit input', function(e) {
	var code;
	code = e.which || e.keyCode || 0;
	if (code === 13) {
		e.stopPropagation();
		e.preventDefault();
		return $(this).closest('tr').find('.col-action-inline-edit [name="inline_edit[submit]"]').click();
	}
});

$(document).on('keydown', 'input[data-datagrid-manualsubmit]', function(e) {
	var code;
	code = e.which || e.keyCode || 0;
	if (code === 13) {
		e.stopPropagation();
		e.preventDefault();
		return dataGridSubmitForm($(this).closest('form').first());
	}
});

getEventDomPath = function(e) {
	var node, path;
	if (indexOf.call(e, path) >= 0) {
		return e.path;
	}
	path = [];
	node = e.target;
	while (node !== document.body) {
		if (node === null) {
			break;
		}
		path.push(node);
		node = node.parentNode;
	}
	return path;
};

datagridShiftGroupSelection = function() {
	var last_checkbox;
	last_checkbox = null;
	return document.addEventListener('click', function(e) {
		var checkboxes_rows, current_checkbox_row, el, event, i, ie, input, j, k, last_checkbox_row, last_checkbox_tbody, len, len1, len2, ref, ref1, results, row, rows;
		ref = getEventDomPath(e);
		for (i = 0, len = ref.length; i < len; i++) {
			el = ref[i];
			if ($(el).is('.col-checkbox') && last_checkbox && e.shiftKey) {
				current_checkbox_row = $(el).closest('tr');
				last_checkbox_row = last_checkbox.closest('tr');
				last_checkbox_tbody = last_checkbox_row.closest('tbody');
				checkboxes_rows = last_checkbox_tbody.find('tr').toArray();
				if (current_checkbox_row.index() > last_checkbox_row.index()) {
					rows = checkboxes_rows.slice(last_checkbox_row.index(), current_checkbox_row.index());
				} else if (current_checkbox_row.index() < last_checkbox_row.index()) {
					rows = checkboxes_rows.slice(current_checkbox_row.index() + 1, last_checkbox_row.index());
				}
				if (!rows) {
					return;
				}
				for (j = 0, len1 = rows.length; j < len1; j++) {
					row = rows[j];
					input = $(row).find('.col-checkbox input[type=checkbox]')[0];
					if (input) {
						input.checked = true;
						ie = window.navigator.userAgent.indexOf("MSIE ");
						if (ie) {
							event = document.createEvent('Event');
							event.initEvent('change', true, true);
						} else {
							event = new Event('change', {
								'bubbles': true
							});
						}
						input.dispatchEvent(event);
					}
				}
			}
		}
		ref1 = getEventDomPath(e);
		results = [];
		for (k = 0, len2 = ref1.length; k < len2; k++) {
			el = ref1[k];
			if ($(el).is('.col-checkbox')) {
				results.push(last_checkbox = $(el));
			} else {
				results.push(void 0);
			}
		}
		return results;
	});
};

datagridShiftGroupSelection();

document.addEventListener('change', function(e) {
	var buttons, checked_inputs, counter, event, grid, i, ie, input, inputs, len, results, select, total;
	grid = e.target.getAttribute('data-check');
	if (grid) {
		checked_inputs = document.querySelectorAll('input[data-check-all-' + grid + ']:checked');
		select = document.querySelector('.datagrid-' + grid + ' select[name="group_action[group_action]"]');
		buttons = document.querySelectorAll('.datagrid-' + grid + ' .row-group-actions *[type="submit"]');
		counter = document.querySelector('.datagrid-' + grid + ' .datagrid-selected-rows-count');

		if (checked_inputs.length) {
			if (buttons) {
				buttons.forEach(function (button) {
					button.disabled = false;
				});
			}
			if (select) {
				select.disabled = false;
			}
			total = document.querySelectorAll('input[data-check-all-' + grid + ']').length;
			if (counter) {
				counter.innerHTML = checked_inputs.length + '/' + total;
			}
		} else {
			if (buttons) {
				buttons.forEach(function (button) {
					button.disabled = true;
				});
			}
			if (select) {
				select.disabled = true;
				select.value = "";
			}
			if (counter) {
				counter.innerHTML = "";
			}
		}
		ie = window.navigator.userAgent.indexOf("MSIE ");
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
			ie = window.navigator.userAgent.indexOf("MSIE ");
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


window.datagridSerializeUrl = function(obj, prefix) {
var str = [];
for(var p in obj) {
	if (obj.hasOwnProperty(p)) {
		var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
		if (v !== null && v !== "") {
			if (typeof v == "object") {
				var r = window.datagridSerializeUrl(v, k);
					if (r) {
						str.push(r);
					}
			} else {
				str.push(encodeURIComponent(k) + "=" + encodeURIComponent(v));
			}
		}
	}
}
return str.join("&");
}
;

datagridSortable = function() {
	if (typeof $.fn.sortable === 'undefined') {
		return;
	}
	return $('.datagrid [data-sortable]').sortable({
		handle: '.handle-sort',
		items: 'tr',
		axis: 'y',
		update: function(event, ui) {
			var component_prefix, data, item_id, next_id, prev_id, row, url;
			row = ui.item.closest('tr[data-id]');
			item_id = row.data('id');
			prev_id = null;
			next_id = null;
			if (row.prev().length) {
				prev_id = row.prev().data('id');
			}
			if (row.next().length) {
				next_id = row.next().data('id');
			}
			url = $(this).data('sortable-url');
			data = {};
			component_prefix = row.closest('.datagrid').find('tbody').attr('data-sortable-parent-path');
			data[(component_prefix + '-item_id').replace(/^-/, '')] = item_id;
			if (prev_id !== null) {
				data[(component_prefix + '-prev_id').replace(/^-/, '')] = prev_id;
			}
			if (next_id !== null) {
				data[(component_prefix + '-next_id').replace(/^-/, '')] = next_id;
			}
			return dataGridRegisterAjaxCall({
				type: 'GET',
				url: url,
				data: data,
				error: function(jqXHR, textStatus, errorThrown) {
					return alert(jqXHR.statusText);
				}
			});
		},
		helper: function(e, ui) {
			ui.children().each(function() {
				return $(this).width($(this).width());
			});
			return ui;
		}
	});
};

$(function() {
	return datagridSortable();
});

if (typeof datagridSortableTree === 'undefined') {
	datagridSortableTree = function() {
		if (typeof $('.datagrid-tree-item-children').sortable === 'undefined') {
			return;
		}
		return $('.datagrid-tree-item-children').sortable({
			handle: '.handle-sort',
			items: '.datagrid-tree-item:not(.datagrid-tree-header)',
			toleranceElement: '> .datagrid-tree-item-content',
			connectWith: '.datagrid-tree-item-children',
			update: function(event, ui) {
				var component_prefix, data, item_id, next_id, parent, parent_id, prev_id, row, url;
				$('.toggle-tree-to-delete').remove();
				row = ui.item.closest('.datagrid-tree-item[data-id]');
				item_id = row.data('id');
				prev_id = null;
				next_id = null;
				parent_id = null;
				if (row.prev().length) {
					prev_id = row.prev().data('id');
				}
				if (row.next().length) {
					next_id = row.next().data('id');
				}
				parent = row.parent().closest('.datagrid-tree-item');
				if (parent.length) {
					parent.find('.datagrid-tree-item-children').first().css({
						display: 'block'
					});
					parent.addClass('has-children');
					parent_id = parent.data('id');
				}
				url = $(this).data('sortable-url');
				if (!url) {
					return;
				}
				parent.find('[data-toggle-tree]').first().removeClass('hidden');
				component_prefix = row.closest('.datagrid-tree').attr('data-sortable-parent-path');
				data = {};
				data[(component_prefix + '-item_id').replace(/^-/, '')] = item_id;
				if (prev_id !== null) {
					data[(component_prefix + '-prev_id').replace(/^-/, '')] = prev_id;
				}
				if (next_id !== null) {
					data[(component_prefix + '-next_id').replace(/^-/, '')] = next_id;
				}
				data[(component_prefix + '-parent_id').replace(/^-/, '')] = parent_id;
				return dataGridRegisterAjaxCall({
					type: 'GET',
					url: url,
					data: data,
					error: function(jqXHR, textStatus, errorThrown) {
						if (errorThrown !== 'abort') {
							return alert(jqXHR.statusText);
						}
					}
				});
			},
			stop: function(event, ui) {
				return $('.toggle-tree-to-delete').removeClass('toggle-tree-to-delete');
			},
			start: function(event, ui) {
				var parent;
				parent = ui.item.parent().closest('.datagrid-tree-item');
				if (parent.length) {
					if (parent.find('.datagrid-tree-item').length === 2) {
						return parent.find('[data-toggle-tree]').addClass('toggle-tree-to-delete');
					}
				}
			}
		});
	};
}

$(function() {
	return datagridSortableTree();
});

dataGridRegisterExtension('datagrid.happy', {
	success: function() {
		var c, checked_rows, class_selector, classes, event, grid, grids, i, ie, input, j, len, len1, results;
		if (window.happy) {
			window.happy.reset();
		}
		grids = $('.datagrid');
		results = [];
		for (i = 0, len = grids.length; i < len; i++) {
			grid = grids[i];
			classes = grid.classList;
			class_selector = '';
			for (j = 0, len1 = classes.length; j < len1; j++) {
				c = classes[j];
				class_selector = class_selector + '.' + c;
			}
			checked_rows = document.querySelectorAll(class_selector + ' ' + 'input[data-check]:checked');
			if (checked_rows.length === 1 && checked_rows[0].getAttribute('name') === 'toggle-all') {
				input = document.querySelector(class_selector + ' input[name=toggle-all]');
				if (input) {
					input.checked = false;
					ie = window.navigator.userAgent.indexOf("MSIE ");
					if (ie) {
						event = document.createEvent('Event');
						event.initEvent('change', true, true);
					} else {
						event = new Event('change', {
							'bubbles': true
						});
					}
					results.push(input.dispatchEvent(event));
				} else {
					results.push(void 0);
				}
			} else {
				results.push(void 0);
			}
		}
		return results;
	}
});

dataGridRegisterExtension('datagrid.sortable', {
	success: function() {
		return datagridSortable();
	}
});

dataGridRegisterExtension('datagrid.forms', {
	success: function() {
		return $('.datagrid').find('form').each(function() {
			return window.Nette.initForm(this);
		});
	}
});

dataGridRegisterExtension('datagrid.url', {
	success: function(payload) {
		var host, path, query, url;
		if (payload._datagrid_url) {
			if (window.history.replaceState) {
				host = window.location.protocol + "//" + window.location.host;
				path = window.location.pathname;
				query = window.datagridSerializeUrl(payload.state).replace(/&+$/gm, '');
				if (query) {
					url = host + path + "?" + query.replace(/\&*$/, '');
				} else {
					url = host + path;
				}
				url += window.location.hash;
				if (window.location.href !== url) {
					return window.history.replaceState({
						path: url
					}, '', url);
				}
			}
		}
	}
});

dataGridRegisterExtension('datagrid.sort', {
	success: function(payload) {
		var href, key, ref, results;
		if (payload._datagrid_sort) {
			ref = payload._datagrid_sort;
			results = [];
			for (key in ref) {
				href = ref[key];
				results.push($('#datagrid-sort-' + key).attr('href', href));
			}
			return results;
		}
	}
});

dataGridRegisterExtension('datargid.item_detail', {
	before: function(xhr, settings) {
		var id, row_detail, grid_fullname;
		if (settings.nette && settings.nette.el.attr('data-toggle-detail')) {
			id = settings.nette.el.attr('data-toggle-detail');
			grid_fullname = settings.nette.el.attr('data-toggle-detail-grid-fullname');
			row_detail = $('.item-detail-' + grid_fullname + '-id-' + id);
			if (row_detail.hasClass('loaded')) {
				if (!row_detail.find('.item-detail-content').length) {
					row_detail.removeClass('toggled');
					return true;
				}
				if (row_detail.hasClass('toggled')) {
					row_detail.find('.item-detail-content').slideToggle('fast', (function(_this) {
						return function() {
							return row_detail.toggleClass('toggled');
						};
					})(this));
				} else {
					row_detail.toggleClass('toggled');
					row_detail.find('.item-detail-content').slideToggle('fast');
				}
				return false;
			} else {
				return row_detail.addClass('loaded');
			}
		}
		return true;
	},
	success: function(payload) {
		var id, row_detail, grid_fullname;
		if (payload._datagrid_toggle_detail && payload._datagrid_name) {
			id = payload._datagrid_toggle_detail;
			grid_fullname = payload._datagrid_name;
			row_detail = $('.item-detail-' + grid_fullname + '-id-' + id);
			row_detail.toggleClass('toggled');
			return row_detail.find('.item-detail-content').slideToggle('fast');
		}
	}
});

dataGridRegisterExtension('datagrid.tree', {
	before: function(xhr, settings) {
		var children_block;
		if (settings.nette && settings.nette.el.attr('data-toggle-tree')) {
			settings.nette.el.toggleClass('toggle-rotate');
			children_block = settings.nette.el.closest('.datagrid-tree-item').find('.datagrid-tree-item-children').first();
			if (children_block.hasClass('loaded')) {
				children_block.slideToggle('fast');
				return false;
			}
		}
		return true;
	},
	success: function(payload) {
		var children_block, content, id, name, ref, snippet, template;
		if (payload._datagrid_tree) {
			id = payload._datagrid_tree;
			children_block = $('.datagrid-tree-item[data-id="' + id + '"]').find('.datagrid-tree-item-children').first();
			children_block.addClass('loaded');
			ref = payload.snippets;
			for (name in ref) {
				snippet = ref[name];
				content = $(snippet);
				template = $('<div class="datagrid-tree-item" id="' + name + '">');
				template.attr('data-id', content.attr('data-id'));
				template.append(content);
				if (content.data('has-children')) {
					template.addClass('has-children');
				}
				children_block.append(template);
			}
			children_block.addClass('loaded');
			children_block.slideToggle('fast');
			dataGridLoad();
		}
		return datagridSortableTree();
	}
});

$(document).on('click', '[data-datagrid-editable-url]', function(event) {
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
			input.find("option[value='" + valueToEdit + "']").prop('selected', true);
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
		submit = function(cell, el) {
			var value;
			value = el.val();
			if (value !== cell.data('valueToEdit')) {
				dataGridRegisterAjaxCall({
					url: cell.data('datagrid-editable-url'),
					data: {
						value: value
					},
					type: 'POST',
					success: function(payload) {
						if (cell.data('datagrid-editable-type') === 'select') {
							cell.html(input.find("option[value='" + value + "']").html());
						} else {
							if (payload._datagrid_editable_new_value) {
								value = payload._datagrid_editable_new_value;
							}
							cell.html(value);
						}
						return cell.addClass('edited');
					},
					error: function() {
						cell.html(cell.data('originalValue'));
						return cell.addClass('edited-error');
					}
				});
			} else {
				cell.html(cell.data('originalValue'));
			}
			return setTimeout(function() {
				return cell.removeClass('editing');
			}, 1200);
		};
		cell.find('input,textarea,select').focus().on('blur', function() {
			return submit(cell, $(this));
		}).on('keydown', function(e) {
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
		return cell.find('select').on('change', function() {
			return submit(cell, $(this));
		});
	}
});

dataGridRegisterExtension('datagrid.after_inline_edit', {
	success: function(payload) {
		var grid = $('.datagrid-' + payload._datagrid_name);

		if (payload._datagrid_inline_edited) {
			grid.find('tr[data-id=' + payload._datagrid_inline_edited + '] > td').addClass('edited');
			return grid.find('.datagrid-inline-edit-trigger').removeClass('hidden');
		} else if (payload._datagrid_inline_edit_cancel) {
			return grid.find('.datagrid-inline-edit-trigger').removeClass('hidden');
		}
	}
});

$(document).on('mouseup', '[data-datagrid-cancel-inline-add]', function(e) {
	var code = e.which || e.keyCode || 0;
	if (code === 1) {
		e.stopPropagation();
		e.preventDefault();
		return $('.datagrid-row-inline-add').addClass('datagrid-row-inline-add-hidden');
	}
});

dataGridRegisterExtension('datagrid-toggle-inline-add', {
	success: function(payload) {
		var grid = $('.datagrid-' + payload._datagrid_name);

		if (payload._datagrid_inline_adding) {
			var row = grid.find('.datagrid-row-inline-add');

			if (row.hasClass('datagrid-row-inline-add-hidden')) {
				row.removeClass('datagrid-row-inline-add-hidden');
			}

			row.find('input:not([readonly]),textarea:not([readonly])').first().focus();
		}
	}
});

datagridFitlerMultiSelect = function() {
	var select = $('.selectpicker').first();

	if ($.fn.selectpicker) {
		return $.fn.selectpicker.defaults = {
			countSelectedText: select.data('i18n-selected'),
			iconBase: '',
			tickIcon: select.data('selected-icon-check')
		};
	}
};

$(function() {
	return datagridFitlerMultiSelect();
});

datagridGroupActionMultiSelect = function() {
	var selects;

	if (!$.fn.selectpicker) {
		return;
	}

	selects = $('[data-datagrid-multiselect-id]');

	return selects.each(function() {
		var id;
		if ($(this).hasClass('selectpicker')) {
			$(this).removeAttr('id');
			id = $(this).data('datagrid-multiselect-id');
			$(this).on('loaded.bs.select', function(e) {
				$(this).parent().attr('style', 'display:none;');
				return $(this).parent().find('.hidden').removeClass('hidden').addClass('btn-default btn-secondary');
			});
			return $(this).on('rendered.bs.select', function(e) {
				return $(this).parent().attr('id', id);
			});
		}
	});
};

$(function() {
	return datagridGroupActionMultiSelect();
});

dataGridRegisterExtension('datagrid.fitlerMultiSelect', {
	success: function() {
		datagridFitlerMultiSelect();
		if ($.fn.selectpicker) {
			return $('.selectpicker').selectpicker({
				iconBase: 'fa'
			});
		}
	}
});

dataGridRegisterExtension('datagrid.groupActionMultiSelect', {
	success: function() {
		return datagridGroupActionMultiSelect();
	}
});

dataGridRegisterExtension('datagrid.inline-editing', {
	success: function(payload) {
		var grid;
		if (payload._datagrid_inline_editing) {
			grid = $('.datagrid-' + payload._datagrid_name);
			return grid.find('.datagrid-inline-edit-trigger').addClass('hidden');
		}
	}
});

dataGridRegisterExtension('datagrid.redraw-item', {
	success: function(payload) {
		var row;
		if (payload._datagrid_redraw_item_class) {
			row = $('tr[data-id=' + payload._datagrid_redraw_item_id + ']');
			return row.attr('class', payload._datagrid_redraw_item_class);
		}
	}
});

dataGridRegisterExtension('datagrid.reset-filter-by-column', {
	success: function(payload) {
		var grid, href, i, key, len, ref;
		if (!payload._datagrid_name) {
			return;
		}
		grid = $('.datagrid-' + payload._datagrid_name);
		grid.find('[data-datagrid-reset-filter-by-column]').addClass('hidden');
		if (payload.non_empty_filters && payload.non_empty_filters.length) {
			ref = payload.non_empty_filters;
			for (i = 0, len = ref.length; i < len; i++) {
				key = ref[i];
				grid.find('[data-datagrid-reset-filter-by-column=' + key + ']').removeClass('hidden');
			}
			href = grid.find('.reset-filter').attr('href');
			return grid.find('[data-datagrid-reset-filter-by-column]').each(function() {
				var new_href;
				key = $(this).attr('data-datagrid-reset-filter-by-column');
				new_href = href.replace('do=' + payload._datagrid_name + '-resetFilter', 'do=' + payload._datagrid_name + '-resetColumnFilter');
				new_href += '&' + payload._datagrid_name + '-key=' + key;
				return $(this).attr('href', new_href);
			});
		}
	}
});
