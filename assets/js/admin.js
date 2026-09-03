jQuery( document ).ready( function( $ ) {
	const
		$show_settings_toggler = $('.show-settings'),
		$help = $('.wpsa-help-tab-toggle'),
		wp = window.wp;

	// Opens the WordPress contextual help panel on the requested help tab.
	$help.on(
		'click',
		function () {
			const $this = $(this);
			const tab = '#tab-link-recrawler_' + $this.data('tab');

			if ( $show_settings_toggler.attr('aria-expanded') === 'false' ) {
				$show_settings_toggler.trigger('click');
			}

			$(tab).find('a').trigger('click');
		}
	);

	//Initiate Color Picker.
	$('.color-picker').iris();

	$( '.wposa__group .collapsed' ).each( function() {
		$( this )
			.find( 'input:checked' )
			.parent()
			.parent()
			.parent()
			.nextAll()
			.each( function() {
				if ( $( this ).hasClass( 'last' ) ) {
					$( this ).removeClass( 'hidden' );
					return false;
				}
				$( this )
					.filter( '.hidden' )
					.removeClass( 'hidden' );
			});
	});

	$( '.wpsa-browse' ).on( 'click', function( event ) {
		event.preventDefault();

		var self = $( this );

		// Create the media frame.
		var file_frame = ( wp.media.frames.file_frame = wp.media({
			title: self.data( 'uploader_title' ),
			button: {
				text: self.data( 'uploader_button_text' )
			},
			multiple: false
		}) );

		file_frame.on( 'select', function() {
			attachment = file_frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();

			self
				.prev( '.wpsa-url' )
				.val( attachment.url )
				.change();
		});

		// Finally, open the modal
		file_frame.open();
	});

	$( 'input.wpsa-url' )
		.on( 'change keyup paste input', function() {
			var self = $( this );
			self
				.next()
				.parent()
				.children( '.wpsa-image-preview' )
				.children( 'img' )
				.attr( 'src', self.val() );
		})
		.change();

	const CODE_ENDPOINT = 'https://oauth.yandex.ru/authorize?state=yandex-webmaster&response_type=code&force_confirm=yes&redirect_uri=' + recrawlerSettings.redirect_url + '&client_id=';

	$( '#button_get_token' ).on(
		'click',
		function() {
			const CLIENT_ID = document.getElementById( 'recrawler_yandex_webmaster[client_id]' ).value;

			window.location.href = CODE_ENDPOINT + CLIENT_ID;
		}
	);

	// Warn before leaving a settings page with unsaved changes. The browser
	// picks the wording — a custom message is ignored in every current browser.
	const $settings_form = $( '.wposa__group form' );

	let leaving_is_intentional = false;

	if ( $settings_form.length ) {
		const initial_state = $settings_form.serialize();

		$settings_form.on( 'submit', function() {
			leaving_is_intentional = true;
		});

		$( window ).on( 'beforeunload', function( evt ) {
			if ( leaving_is_intentional || $settings_form.serialize() === initial_state ) {
				return;
			}

			evt.preventDefault();
			evt.originalEvent.returnValue = '';

			return '';
		});
	}

	$( 'input:button[id$="_reset_form"]' ).on(
		'click',
		function() {
			const
				$button = $( this ),
				$nonce  = $( this ).parents( 'form' ).find( '#_wpnonce' );

			if ( confirm( recrawlerLocalize.are_you_sure ) ) {
				wp.ajax.post(
					'recrawler_reset_form',
					{
						section: $button.data( 'section' ),
						nonce:   $nonce.val(),
					}
				).always( function ( response ) {
					if ( response === 'ok' ) {
						leaving_is_intentional = true;
						document.location.reload();
					} else {
						console.log( response );
					}
				} );
			}
		}
	);
});
