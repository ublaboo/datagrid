# Non-ajax confirmation
#
$(document).on('click', '[data-datagrid-confirm]:not(.ajax)', (e) ->
	if not confirm($(e.target).closest('a').attr('data-datagrid-confirm'))
		e.stopPropagation()
		e.preventDefault()
)

# Ajax confirmation
#
$.nette.ext('datagrid.confirm', {
	before: (xhr, settings) ->
		if settings.nette
			confirm_message = settings.nette.el.data('datagrid-confirm')
			if confirm_message
				return confirm(confirm_message)
})

# Datagrid auto submit
#
$(document).on('change', 'select[data-autosubmit-per-page]', ->
	button = $(this).parent().find('input[type=submit]')
	if (button.length == 0)
		button = $(this).parent().find('button[type=submit]');

	button.click()
).on('change', 'select[data-autosubmit]', ->
	$(this).closest('form').first().submit()
).on('change', 'input[data-autosubmit][data-autosubmit-change]', (e) ->
	code = e.which || e.keyCode || 0

	clearTimeout(window.datagrid_autosubmit_timer)
	$this = $(this)
	window.datagrid_autosubmit_timer = setTimeout =>
		$this.closest('form').first().submit()
	, 200
).on('keyup', 'input[data-autosubmit]', (e) ->
	code = e.which || e.keyCode || 0

	if (code != 13) and ((code >= 9 and code <= 40) or (code >= 112 and code <= 123))
		return

	clearTimeout(window.datagrid_autosubmit_timer)
	$this = $(this)
	window.datagrid_autosubmit_timer = setTimeout =>
		$this.closest('form').first().submit()
	, 200
).on('keydown', '.datagrid-inline-edit input', (e) ->
	code = e.which || e.keyCode || 0

	if (code == 13)
		e.stopPropagation()
		e.preventDefault()

		$(this).closest('tr').find('.col-action-inline-edit [name="inline_edit[submit]"]').click()
)

# Datagrid manual submit
#
$(document).on('keydown', 'input[data-datagrid-manualsubmit]', (e) ->
	code = e.which || e.keyCode || 0

	if (code == 13)
		e.stopPropagation()
		e.preventDefault()


		$(this).closest('form').first().submit()
)


getEventDomPath = (e) ->
	if path in e
		return e.path

	path = []
	node = e.target

	while node != document.body
		if node == null
			break
		path.push(node)
		node = node.parentNode

	return path


datagridShiftGroupSelection = ->
	last_checkbox = null

	document.addEventListener 'click', (e) ->
		for el in getEventDomPath(e)
			if $(el).is('.col-checkbox') && last_checkbox && e.shiftKey
				current_checkbox_row = $(el).closest('tr')

				last_checkbox_row = last_checkbox.closest('tr')
				last_checkbox_tbody = last_checkbox_row.closest('tbody')

				checkboxes_rows = last_checkbox_tbody.find('tr').toArray()

				if current_checkbox_row.index() > last_checkbox_row.index()
					rows = checkboxes_rows.slice(last_checkbox_row.index(), current_checkbox_row.index())

				else if current_checkbox_row.index() < last_checkbox_row.index()
					rows = checkboxes_rows.slice(current_checkbox_row.index() + 1, last_checkbox_row.index())

				if !rows
					return

				for row in rows
					input = $(row).find('.col-checkbox input[type=checkbox]')[0]

					if input
						input.checked = true

						ie = window.navigator.userAgent.indexOf("MSIE ")

						if ie
							event = document.createEvent('Event')
							event.initEvent('change', true, true);
						else
							event = new Event('change', {'bubbles': true})

						input.dispatchEvent(event)


		for el in getEventDomPath(e)
			if $(el).is('.col-checkbox')
				last_checkbox = $(el)

datagridShiftGroupSelection()


document.addEventListener 'change', (e) ->
	grid = e.target.getAttribute('data-check')

	if grid
		checked_inputs = document.querySelectorAll('input[data-check-all-' + grid + ']:checked')

		select = document.querySelector('.datagrid-' + grid + ' select[name="group_action[group_action]"]')

		if select
			counter = document.querySelector('.datagrid-' + grid + ' .datagrid-selected-rows-count')

			if checked_inputs.length
				select.disabled = false

				total = document.querySelectorAll('input[data-check-all-' + grid + ']').length

				if counter
					counter.innerHTML = checked_inputs.length + '/' + total

			else
				select.disabled = true
				select.value = ""

				if counter
					counter.innerHTML = ""

		ie = window.navigator.userAgent.indexOf("MSIE ")

		if ie
			event = document.createEvent('Event')
			event.initEvent('change', true, true);
		else
			event = new Event('change', {'bubbles': true})

		if select
			select.dispatchEvent(event)


	grid = e.target.getAttribute('data-check-all')

	if grid
		inputs = document.querySelectorAll('input[type=checkbox][data-check-all-' + grid + ']')

		for input in inputs
			input.checked = e.target.checked

			ie = window.navigator.userAgent.indexOf("MSIE ")

			if ie
				event = document.createEvent('Event')
				event.initEvent('change', true, true);
			else
				event = new Event('change', {'bubbles': true})

			input.dispatchEvent(event)

`
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
`

datagridSortable = ->
	if typeof $.fn.sortable == 'undefined'
		return

	$('.datagrid [data-sortable]').sortable({
		handle: '.handle-sort',
		items: 'tr',
		axis: 'y'
		update: (event, ui) ->
			row = ui.item.closest('tr[data-id]')

			item_id = row.data('id')
			prev_id = null
			next_id = null

			if row.prev().length
				prev_id = row.prev().data('id')

			if row.next().length
				next_id = row.next().data('id')

			url = $(this).data('sortable-url')

			data = {};
			component_prefix = row.closest('.datagrid').find('tbody').attr('data-sortable-parent-path')

			data[(component_prefix + '-item_id').replace(/^-/, '')] = item_id

			if (prev_id != null)
				data[(component_prefix + '-prev_id').replace(/^-/, '')] = prev_id

			if (next_id != null)
				data[(component_prefix + '-next_id').replace(/^-/, '')] = next_id

			$.nette.ajax({
				type: 'GET',
				url: url,
				data: data,
				error: (jqXHR, textStatus, errorThrown) ->
					alert(jqXHR.statusText)
			})
		,
		helper: (e, ui) ->
			ui.children().each ->
				$(this).width($(this).width())

			return ui;
	})

$ ->
	datagridSortable()

if typeof datagridSortableTree == 'undefined'
	datagridSortableTree = ->
		if typeof $('.datagrid-tree-item-children').sortable == 'undefined'
			return

		$('.datagrid-tree-item-children').sortable({
			handle: '.handle-sort',
			items: '.datagrid-tree-item:not(.datagrid-tree-header)',
			toleranceElement: '> .datagrid-tree-item-content',
			connectWith: '.datagrid-tree-item-children',
			update: (event, ui) ->
				$('.toggle-tree-to-delete').remove()

				row = ui.item.closest('.datagrid-tree-item[data-id]')

				item_id = row.data('id')
				prev_id = null
				next_id = null
				parent_id = null

				if row.prev().length
					prev_id = row.prev().data('id')

				if row.next().length
					next_id = row.next().data('id')

				parent = row.parent().closest('.datagrid-tree-item')

				if parent.length
					parent.find('.datagrid-tree-item-children').first().css({display: 'block'})
					parent.addClass('has-children')

					parent_id = parent.data('id')

				url = $(this).data('sortable-url')

				if !url
					return

				parent.find('[data-toggle-tree]').first().removeClass('hidden')

				component_prefix = row.closest('.datagrid-tree').attr('data-sortable-parent-path')
				data = {}

				data[(component_prefix + '-item_id').replace(/^-/, '')] = item_id

				if (prev_id != null)
					data[(component_prefix + '-prev_id').replace(/^-/, '')] = prev_id

				if (next_id != null)
					data[(component_prefix + '-next_id').replace(/^-/, '')] = next_id

				data[(component_prefix + '-parent_id').replace(/^-/, '')] = parent_id

				$.nette.ajax({
					type: 'GET',
					url: url,
					data: data,
					error: (jqXHR, textStatus, errorThrown) ->
						if errorThrown != 'abort'
							alert(jqXHR.statusText)
				})
		, stop: (event, ui) ->
			$('.toggle-tree-to-delete').removeClass('toggle-tree-to-delete')
		, start: (event, ui) ->
			parent = ui.item.parent().closest('.datagrid-tree-item')

			if parent.length
				if parent.find('.datagrid-tree-item').length == 2
					parent.find('[data-toggle-tree]').addClass('toggle-tree-to-delete')
		})

$ ->
	datagridSortableTree();

$.nette.ext('datagrid.happy', {
	success: ->
		if window.happy
			window.happy.reset()

		grids = $('.datagrid')

		for grid in grids
			classes = grid.classList
			class_selector = '';

			for c in classes
				class_selector = class_selector + '.' + c


			checked_rows = document.querySelectorAll(class_selector + ' ' + 'input[data-check]:checked')

			if checked_rows.length == 1 and checked_rows[0].getAttribute('name') == 'toggle-all'
				input = document.querySelector(class_selector + ' input[name=toggle-all]')

				if input
					input.checked = false
					ie = window.navigator.userAgent.indexOf("MSIE ")

					if ie
						event = document.createEvent('Event')
						event.initEvent('change', true, true);
					else
						event = new Event('change', {'bubbles': true})

					input.dispatchEvent(event)
})

$.nette.ext('datagrid.sortable', {
	success: ->
		datagridSortable()
})

$.nette.ext('datagrid.forms', {
	success: ->
		$('.datagrid').find('form').each ->
			window.Nette.initForm(this)
})

$.nette.ext('datagrid.url', {
	success: (payload) ->
		if payload._datagrid_url
			if window.history.pushState
				host = window.location.protocol + "//" + window.location.host
				path = window.location.pathname
				query = window.datagridSerializeUrl(payload.state).replace(/&+$/gm,'')

				if query
					url = host + path + "?"+ query.replace(/\&*$/, '')
				else
					url = host + path

				url += window.location.hash

				if window.location.href != url
					window.history.pushState({path: url}, '', url)
})

$.nette.ext('datagrid.sort', {
	success: (payload) ->
		if payload._datagrid_sort
			for key, href of payload._datagrid_sort
				$('#datagrid-sort-' + key).attr('href', href)
})

$.nette.ext('datargid.item_detail', {
	before: (xhr, settings) ->
		if settings.nette and settings.nette.el.attr('data-toggle-detail')
			id = settings.nette.el.attr('data-toggle-detail')
			grid = $('.datagrid-' + settings.nette.el.attr('data-toggle-detail-grid'))
			row_detail = grid.find('.item-detail-' + id)
			#row = row_detail.closest('.datagrid').find('tr[data-id=' + id + ']')

			#if row_detail.hasClass('loaded') and row.hasClass('detail-loaded')
			if row_detail.hasClass('loaded')
				if not row_detail.find('.item-detail-content').length
					row_detail.removeClass('toggled')

					return true

				if row_detail.hasClass('toggled')
					row_detail.find('.item-detail-content').slideToggle('fast', =>
						row_detail.toggleClass('toggled')
					)
				else
					row_detail.toggleClass('toggled')
					row_detail.find('.item-detail-content').slideToggle('fast')

				return false
			else
				#row.addClass('detail-loaded')
				row_detail.addClass('loaded')

	success: (payload) ->
		if payload._datagrid_toggle_detail
			id = payload._datagrid_toggle_detail
			grid = $('.datagrid-' + payload._datagrid_name)
			row_detail = grid.find('.item-detail-' + id)

			row_detail.toggleClass('toggled')
			row_detail.find('.item-detail-content').slideToggle('fast')
})

$.nette.ext('datagrid.tree', {
	before: (xhr, settings) ->
		if settings.nette and settings.nette.el.attr('data-toggle-tree')
			settings.nette.el.toggleClass('toggle-rotate')
			children_block = settings.nette.el.closest('.datagrid-tree-item').find('.datagrid-tree-item-children').first()

			if children_block.hasClass('loaded')
				children_block.slideToggle('fast')

				return false

		return true

	success: (payload) ->
		if payload._datagrid_tree
			id = payload._datagrid_tree
			children_block = $('.datagrid-tree-item[data-id="' + id + '"]').find('.datagrid-tree-item-children').first()
			children_block.addClass('loaded')

			for name, snippet of payload.snippets
				content = $(snippet)
				template = $('<div class="datagrid-tree-item" id="' + name + '">')
				template.attr('data-id', content.attr('data-id'))
				template.append(content)

				if content.data('has-children')
					template.addClass('has-children')


				children_block.append(template)

			children_block.addClass('loaded')
			children_block.slideToggle('fast')

			$.nette.load();

		datagridSortableTree()
})

$(document).on('click', '[data-datagrid-editable-url]', (event) ->
	cell = $(this)

	if event.target.tagName.toLowerCase() == 'a'
		return

	if cell.hasClass('datagrid-inline-edit')
		return

	if !cell.hasClass('editing')
		cell.addClass('editing')

		cellValue = cell.html().trim().replace('<br>', '\n')

		if cell.attr('data-datagrid-editable-value')
			valueToEdit = cell.data('datagrid-editable-value')
		else
			valueToEdit = cellValue

		cell.data('originalValue', cellValue)
		cell.data('valueToEdit', valueToEdit)

		if cell.data('datagrid-editable-type') == 'textarea'
			input = $('<textarea>' + valueToEdit + '</textarea>')

			cell_padding = parseInt(cell.css('padding').replace(/[^-\d\.]/g, ''), 10)
			cell_height = cell.outerHeight()
			line_height = Math.round(parseFloat(cell.css('line-height')))

			cell_lines = (cell_height - (2 * cell_padding)) / line_height

			input.attr('rows', Math.round((cell_lines)))

		else if cell.data('datagrid-editable-type') == 'select'
			input = $(cell.data('datagrid-editable-element'));

			input.find('option').each ->
				if $(this).text() == valueToEdit
					input.find("option[value='" + valueToEdit + "']").prop('selected', true)
		else
			input = $('<input type="' + cell.data('datagrid-editable-type') + '">')
			input.val(valueToEdit)

		attrs = cell.data('datagrid-editable-attrs')

		for attr_name, attr_value of attrs
			input.attr(attr_name, attr_value)

		cell.removeClass('edited')
		cell.html(input)

		submit = (cell, el) ->
			value = el.val()

			if value != cell.data('valueToEdit')
				$.nette.ajax({
					url: cell.data('datagrid-editable-url'),
					data: {
						value: value
					},
					method: 'POST',
					success: (payload) ->
						if cell.data('datagrid-editable-type') == 'select'
							cell.html(input.find("option[value='" + value + "']").html())
						else
							if payload._datagrid_editable_new_value
								value = payload._datagrid_editable_new_value

							cell.html(value)

						cell.addClass('edited')
					,
					error: () ->
						cell.html(cell.data('originalValue'))
						cell.addClass('edited-error')
				})
			else
				cell.html(cell.data('originalValue'))

			setTimeout ->
				cell.removeClass('editing')
			, 1200

		cell.find('input,textarea,select').focus().on('blur', ->
			submit(cell, $(this))
		).on('keydown', (e) ->
			if cell.data('datagrid-editable-type') != 'textarea'
				if e.which == 13
					e.stopPropagation()
					e.preventDefault()

					return submit(cell, $(this))

			if e.which == 27
				e.stopPropagation()
				e.preventDefault()

				cell.removeClass('editing');
				cell.html(cell.data('originalValue'));
		)
		cell.find('select').on('change', ->
			submit(cell, $(this))
		)
)

# Datagrid after big inline edit notification
#
$.nette.ext('datagrid.after_inline_edit', {
	success: (payload) ->
		grid = $('.datagrid-' + payload._datagrid_name)

		if payload._datagrid_inline_edited
			grid.find('tr[data-id=' + payload._datagrid_inline_edited + '] > td').addClass('edited')
			grid.find('.datagrid-inline-edit-trigger').removeClass('hidden')
		else if payload._datagrid_inline_edit_cancel
			grid.find('.datagrid-inline-edit-trigger').removeClass('hidden')
})


# Inline add
#
$(document).on('click', '[data-datagrid-toggle-inline-add]', (e) ->
	e.stopPropagation()
	e.preventDefault()

	row = $(this).closest('.datagrid').find('.datagrid-row-inline-add')

	if row.hasClass('datagrid-row-inline-add-hidden')
		row.removeClass('datagrid-row-inline-add-hidden')

	row.find('input:not([readonly]),textarea:not([readonly])').first().focus()
)

$(document).on('mouseup', '[data-datagrid-cancel-inline-add]', (e) ->
	code = e.which || e.keyCode || 0

	if code == 1
		e.stopPropagation()
		e.preventDefault()

		$('.datagrid-row-inline-add').addClass('datagrid-row-inline-add-hidden')
)

$.nette.ext('datagrid-toggle-inline-add', {
	success: (payload) ->
		if payload._datagrid_inline_added
			$('.datagrid-row-inline-add').find('textarea').html('')
			$('.datagrid-row-inline-add').find('input[type!=submit]').val('')

			$('.datagrid-row-inline-add').addClass('datagrid-row-inline-add-hidden')
})


# FilterMultiSelect
#

datagridFitlerMultiSelect = ->
	select = $('.selectpicker').first()

	if $.fn.selectpicker
		$.fn.selectpicker.defaults = {
			countSelectedText: select.data('i18n-selected'),
			iconBase: '',
			tickIcon: select.data('selected-icon-check')
		}

$ ->
	datagridFitlerMultiSelect()


datagridGroupActionMultiSelect = ->
	if !$.fn.selectpicker
		return;

	selects = $('[data-datagrid-multiselect-id]');

	selects.each ->
		if $(this).hasClass('selectpicker')
			$(this).removeAttr('id')
			id = $(this).data('datagrid-multiselect-id')

			$(this).on('loaded.bs.select', (e) ->
				$(this).parent().attr('style', 'display:none;')
				$(this).parent().find('.hidden').removeClass('hidden').addClass('btn-default btn-secondary')
			)

			$(this).on('rendered.bs.select', (e) ->
				$(this).parent().attr('id', id)
			)

$ ->
	datagridGroupActionMultiSelect()


$.nette.ext('datagrid.fitlerMultiSelect', {
	success: ->
		datagridFitlerMultiSelect()

		if $.fn.selectpicker
			$('.selectpicker').selectpicker({iconBase: 'fa'})
})


$.nette.ext('datagrid.groupActionMultiSelect', {
	success: ->
		datagridGroupActionMultiSelect()
})


$.nette.ext('datagrid.inline-editing', {
	success: (payload) ->
		if payload._datagrid_inline_editing
			grid = $('.datagrid-' + payload._datagrid_name)
			grid.find('.datagrid-inline-edit-trigger').addClass('hidden')
})


$.nette.ext('datagrid.redraw-item', {
	success: (payload) ->
		if payload._datagrid_redraw_item_class
			row = $('tr[data-id=' + payload._datagrid_redraw_item_id + ']')
			row.attr('class', payload._datagrid_redraw_item_class)
})


$.nette.ext('datagrid.reset-filter-by-column', {
	success: (payload) ->
		if !payload._datagrid_name
			return

		grid = $('.datagrid-' + payload._datagrid_name)

		# Show/hide reset-fitler indecators
		#
		grid.find('[data-datagrid-reset-filter-by-column]').addClass('hidden')

		if payload.non_empty_filters && payload.non_empty_filters.length
			for key in payload.non_empty_filters
				grid.find('[data-datagrid-reset-filter-by-column='+key+']').removeClass('hidden')
})
