(function($, window, document, undefined) {
	"use strict";

	var RFSFEAL = {

		utils: {

			formData: function(form) {
				var	formData 	= {},
					formType 	= form.parent().attr('id'),
					input 		= form.find('.form-control');
				
				input.each(function(index, element) {
					var that = $(element);
					var key = that.attr('name');
					formData[key] = that.val();
				});

				formData.type = formType;
				
				return formData;
			},

			formMessages: function(formType, msgType, response, html, append) {
				var msgContainer = $('#'+formType).find('.msg-container'),
					msg 		 = rfsfealajax.messages[msgType][response],
					alertClass;

				html 	= false || html;
				append 	= false || append;

				switch(msgType) {
					case 'ok':
						alertClass = 'alert alert-success';
						break;
					case 'warning':
						alertClass = 'alert alert-warning';
						break;
					default:
						alertClass = 'alert alert-danger';
						break;
				}
					

				var alertBox = $('<div></div>', {
					class: 'alert text-center ' + alertClass
				});

				if( html ) {
					alertBox.html(msg);
				}else {
					alertBox.text(msg);
				}

				if( append ) {
					msgContainer.append(alertBox);
				}else {
					msgContainer.html(alertBox);
				}
			},

			updateFieldsValue: function(form, fields) {
				if( !$(form).length || $.type(fields) != 'object' ) return;
				
				$.each(fields, function(field, value) {
					form.find('#'+field).val(value);
				});
			}

		},

		routing: function() {
			var page 	= $('.rfs-feal-page'),
				link 	= page.find('a'),
				pages 	= [];

			page.each(function(index, page) {
				pages.push( '#' + $(page).attr('id') );
			});

			// clear hash after logging in
			if( window.location.hash == '#loggedin' ) {
				if( history.pushState ) {
					history.pushState(null, null, window.location.pathname);
				}else {
					window.location.hash = '';
				}
			}
			
			// bail early if no pages found
			if( !pages.length ) return;

			function _openPage(newPage, e, updateHistory) {
				var pageExists 	= _pageExists(newPage);

				if( pageExists != -1 ) {
					if(e) {
						e.preventDefault();
					}

					page.removeClass('active');
					$(newPage).addClass('active');

					if(updateHistory) {
						_history(newPage);
					}
				}
			}

			function _pageExists(page) {
				return $.inArray(page, pages);
			}

			function _history(page) {
				if( history.pushState ) {
					history.pushState(null, null, page);
				}else {
					window.location.hash = page;
				}
			}

			// show correct page if redirected via url
			if(window.location.hash) {
				_openPage(window.location.hash, false, true);
			}

			// set login page hash on default
			if( page.hasClass('active') && window.location.hash === '' ) {
				window.location.hash = page.attr('id');
			}
			
			link.on('click', function(e) {
				var that 		= $(this),
					hash 		= that.attr('href');
					
				_openPage(hash, e, true);	
			});

			// show page on going back and forward
			window.onpopstate = function() {
				_openPage(window.location.hash, false, false);
			};
		},

		proccessForms: function() {
			var submitBtn 		= $('.rfs-feal-form button'),
				sending 		= false,
				msgContainer 	= $('.msg-container');

			var _isLoading = function(onoff, that, loader) {
				switch(onoff) {
					case 'on':
						loader.css('opacity', 1);
						that.attr('disabled', true);
						msgContainer.html('');
						break;
					case 'off':
						loader.css('opacity', 0);
						that.attr('disabled', false);
						break;
				}
			};
				
			submitBtn.on('click', function() {
				if( sending ) return;

				var that 		= $(this),
					form 		= that.closest('form'),
					formData 	= RFSFEAL.utils.formData(form),
					loader 		= form.find('.loader');

				_isLoading('on', that, loader);

				var data = {
					action: 'login_profile_actions',
					nonce: rfsfealajax.feal_nonce,
					formData: formData
				};

				$.post(rfsfealajax.ajaxurl, data)
					.done(function(response) {
						
						switch(response[0]) {
							case 'ok':
								switch(formData.type) {
									case 'login':
										window.location.reload();
										break;
									case 'register':
									case 'remind':
										RFSFEAL.utils.formMessages(formData.type, 'ok', response[1]);
										break;
									case 'setpassword':
										window.location.replace(response[1]);
										break;
									case 'profile':
										RFSFEAL.utils.formMessages(formData.type, 'ok', response[1]);
										if( response[2] ) { // if password has been changed
											RFSFEAL.utils.formMessages(formData.type, 'warning', 'password_changed', true, true);

											var countdown 	= $('.feal-countdown'),
												seconds 	= 5;

											countdown.text(seconds);
											setInterval(function() {
												if( seconds === 0) {
													window.location.reload();
													return false;
												}
												seconds--;
												countdown.text(seconds);
											}, 1000);
											
										}
										break;
								}
								break;
							case 'error':
								RFSFEAL.utils.formMessages(formData.type, 'error', response[1]);
								break;
							default:
								RFSFEAL.utils.formMessages(formData.type, 'error', 'error');
								break;
						}

						if( formData.type == 'login' && response[0] == 'ok' ) {
							// do nothing
						}else {
							_isLoading('off', that, loader);
							sending = false;
						}
					})
					.fail(function(xhr, status, error) {
						RFSFEAL.utils.formMessages(formData.type, 'error', 'error');
					});
				
				sending = true;
			});
		},

		passwordHint: function() {
			var trigger = $('#rfs-feal .password-hint'),
				hint 	= $('#rfs-feal .password-conditions');

			trigger.on('click', function() {
				hint.slideToggle();
			});
		},

		userBar: function() {
			if( rfsfealajax.loggedin != 1 || rfsfealajax.show_bar != 1 ) return;

			var data = {
				action: 'add_user_bar',
				nonce: rfsfealajax.feal_nonce
			};

			$.post(rfsfealajax.ajaxurl, data, function(response) {
				if( response[0] == 'ok' ) {
					$('body').prepend(response[1]);
				}
			});
		}

	};

	$(document).ready(function(e) {
		RFSFEAL.proccessForms();
		RFSFEAL.routing();
		RFSFEAL.passwordHint();
		RFSFEAL.userBar();
	});

})(jQuery, window, document);