var dataGridRegisterExtension;

if (typeof naja !== "undefined") {
	dataGridRegisterExtension = function (name, extension) {
		var init = extension.init;
		var success = extension.success;
		var before = extension.before;
		var complete = extension.complete;


		var NewExtension = function NewExtension(naja, name) {
			this.name = name;

			if(init) {
				naja.addEventListener('init', function (params)  {
					init(params.defaultOptions);
				});
			}

			if(success) {
				naja.addEventListener('success', function (params)  {
					success(params.response, params.options);
				});
			}

			if(before) {
				naja.addEventListener('before', function (params) {
					before(params.xhr, params.options);
				});
			}

			if(complete) {
				naja.addEventListener('complete', function (params) {
					complete(params.xhr, params.options);
				});
			}
		
			return this;
		}

		naja.registerExtension(NewExtension, name);
	};
} else if ($.nette) {
		dataGridRegisterExtension = function (name, extension) {
			$.nette.ext(name, extension);
		};
}

dataGridRegisterExtension('ublaboo-spinners', {
	before: function(xhr, settings) {
		var el, id, row_detail, spinner_template, grid_fullname;
		if (settings.nette) {
			el = settings.nette.el;
			spinner_template = $('<div class="ublaboo-spinner ublaboo-spinner-small"><i></i><i></i><i></i><i></i></div>');
			if (el.is('.datagrid [name="group_action[submit]"]')) {
				return el.after(spinner_template);
			} else if (el.is('.datagrid a') && el.data('toggle-detail')) {
				id = settings.nette.el.attr('data-toggle-detail');
				grid_fullname = settings.nette.el.attr('data-toggle-detail-grid-fullname');
				row_detail = $('.item-detail-' + grid_fullname + '-id-' + id);
				if (!row_detail.hasClass('loaded')) {
					return el.addClass('ublaboo-spinner-icon');
				}
			} else if (el.is('.datagrid .col-pagination a')) {
				return el.closest('.row-grid-bottom').find('.col-per-page').prepend(spinner_template);
			} else if (el.is('.datagrid .datagrid-per-page-submit')) {
				return el.closest('.row-grid-bottom').find('.col-per-page').prepend(spinner_template);
			} else if (el.is('.datagrid .reset-filter')) {
				return el.closest('.row-grid-bottom').find('.col-per-page').prepend(spinner_template);
			}
		}
	},
	complete: function() {
		$('.ublaboo-spinner').remove();
		return $('.ublaboo-spinner-icon').removeClass('ublaboo-spinner-icon');
	}
});
