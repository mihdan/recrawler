jQuery( document ).ready( function( $ ) {
	const ACTIVE_TAB = 'recrawler_active_tab';

	const
		$show_settings_toggler = $('.show-settings'),
		$help = $('.wpsa-help-tab-toggle'),
		wp = window.wp;

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

	// Switches option sections
	$( '.wposa__group' ).hide();

	let active_tab = '';

	if ( 'undefined' != typeof localStorage ) {
		active_tab = localStorage.getItem( ACTIVE_TAB );
	}

	if ( '' !== active_tab && $( active_tab ).length ) {
		$( active_tab ).fadeIn();
	} else {
		$( '.wposa__group:first' ).fadeIn();
	}

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

	if ( '' !== active_tab && $( active_tab + '-tab' ).length ) {
		$( active_tab + '-tab' ).addClass( 'nav-tab-active' );
	} else {
		$( '.wposa .nav-tab-wrapper a:first' ).addClass( 'nav-tab-active' );
	}

	$( '.wposa .nav-tab-wrapper a' ).click( function( evt ) {
		$( '.wposa .nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
		$( this )
			.addClass( 'nav-tab-active' )
			.blur();
		var clicked_group = $( this ).attr( 'href' );
		if ( 'undefined' != typeof localStorage ) {
			localStorage.setItem( ACTIVE_TAB, $( this ).attr( 'href' ) );
		}
		$( '.wposa__group' ).hide();
		$( clicked_group ).fadeIn();
		evt.preventDefault();
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
						document.location.reload();
					} else {
						console.log( response );
					}
				} );
			}
		}
	);
});
