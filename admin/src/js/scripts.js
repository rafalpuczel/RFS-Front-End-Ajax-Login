(function($, window, document, undefined) {
	"use strict";

	var RFSFEAL = {

		tabsContent: function() {
			var tab = $('.nav-tab'),
				id, active,
				table = $('.form-table');
			
			tab.each(function(index, element) {
				id 		= $(element).attr('href').slice(1);
				active 	= index === 0 ? ' nav-tab-content-active' : '';
				
				$(table[index]).wrap('<div class="nav-tab-content' + active + '" id="' + id + '"></div>');
			});
		},

		tabsRouting: function() {
			var tab = $('.nav-tab');

			tab.on('click', function(e) {
				e.preventDefault();

				var that = $(this);

				if( that.hasClass('nav-tab-active') ) return;

				var id 			= that.attr('href'),
					content 	= $('.nav-tab-content'),
					showContent = $('.nav-tab-content' + id);

				if( !content.length ) return;

				tab.removeClass('nav-tab-active');
				that.addClass('nav-tab-active');
				content.removeClass('nav-tab-content-active');
				showContent.addClass('nav-tab-content-active');

			});
		}

	};

	$(document).ready(function(e) {
		RFSFEAL.tabsContent();
		RFSFEAL.tabsRouting();
	});

})(jQuery, window, document);