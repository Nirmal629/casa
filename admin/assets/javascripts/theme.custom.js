/* Add here all your JS customizations */
(function($) {
	'use strict';

	function wrapWideTables() {
		$('table').each(function() {
			var $table = $(this);
			var isDataTableChild = $table.closest('.dataTables_scroll, .dataTables_wrapper').length;
			var isAlreadyWrapped = $table.parent('.table-responsive, .admin-table-scroll').length;

			if (isDataTableChild || isAlreadyWrapped) {
				return;
			}

			$table.wrap('<div class="admin-table-scroll"></div>');
		});
	}

	function normalizePageHeaders() {
		$('.page-header').each(function() {
			var $header = $(this);
			var $title = $header.children('h2').first();

			if (!$title.length) {
				var headingText = $.trim(document.title);

				if (headingText) {
					$title = $('<h2/>', { text: headingText });
					$header.prepend($title);
				}
			}

			$header.find('.sidebar-right-toggle').remove();
			$header.find('.left-wrapper, .right-wrapper').filter(function() {
				return $.trim($(this).text()) === '' && !$(this).children().length;
			}).remove();
		});
	}

	$(function() {
		normalizePageHeaders();
		wrapWideTables();

		if ($.fn.dataTable) {
			$.extend(true, $.fn.dataTable.defaults, {
				autoWidth: false,
				scrollX: true
			});
		}

		$(document).on('click', '.sidebar-left a[href]:not([href="#"]):not([href^="javascript"])', function() {
			if (window.innerWidth < 768) {
				$('html').removeClass('sidebar-left-opened');
			}
		});
	});
})(jQuery);
