<?php
/**
 * Main Class file for `WP_OSA`
 *
 * Main class that deals with all other classes.
 *
 * @since   1.0.0
 * @package WPOSA
 */

namespace Mihdan\ReCrawler\Views;

use Mihdan\ReCrawler\Utils;

/**
 * WP_OSA.
 *
 * WP Settings API Class.
 *
 * @since 1.0.0
 */

class WPOSA {

	/**
	 * Allowed HTML tags and attributes for wp_kses().
	 */
	private const ALLOWED_HTML = [
		'strong'   => [],
		'b'        => [],
		'i'        => [],
		'code'     => [],
		'ul'       => [
			'class' => true,
		],
		'ol'       => [],
		'li'       => [],
		'br'       => [
			'class' => true,
		],
		'fields'   => [],
		'label'    => [
			'for' => true,
		],
		'select'   => [
			'class' => true,
			'name'  => true,
			'id'    => true,
		],
		'option'   => [
			'value'    => true,
			'selected' => true,
		],
		'div'      => [
			'id'     => true,
			'style'  => true,
			'class'  => true,
			'data-w' => true,
		],
		'a'        => [
			'id'      => true,
			'class'   => true,
			'href'    => true,
			'style'   => true,
			'title'   => true,
			'onclick' => true,
			'target'  => true,
		],
		'img'      => [
			'src'    => true,
			'width'  => true,
			'height' => true,
		],
		'p'        => [
			'class' => true,
		],
		'h1'       => [
			'class' => true,
		],
		'h2'       => [
			'class' => true,
		],
		'nav'      => [
			'class'      => true,
			'aria-label' => true,
		],
		'span'     => [
			'class' => true,
			'style' => true,
		],
		'table'    => [
			'class' => true,
		],
		'tbody'    => [
			'class' => true,
		],
		'tr'       => [
			'class' => true,
		],
		'th'       => [
			'class' => true,
		],
		'td'       => [
			'class' => true,
		],
		'textarea' => [
			'name'  => true,
			'class' => true,
			'id'    => true,
			'rows'  => true,
			'cols'  => true,
		],
		'input'    => [
			'id'          => true,
			'class'       => true,
			'type'        => true,
			'name'        => true,
			'value'       => true,
			'placeholder' => true,
			'checked'     => true,
			'readonly'    => true,
			'disabled'    => true,
		],
		'script' => [
			'src'   => true,
			'async' => true,
		]
	];

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $plugin_version;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin prefix.
	 *
	 * @var string
	 */
	private $plugin_prefix;

	/**
	 * Sections array.
	 *
	 * @var   array
	 * @since 1.0.0
	 */
	private $sections_array = array();

	/**
	 * Fields array.
	 *
	 * @var   array
	 * @since 1.0.0
	 */
	private $fields_array = array();

	/**
	 * Sidebar card array.
	 *
	 * @var array $sidebar_cards
	 */
	private $sidebar_cards = [];

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $plugin_version Pluign version.
	 * @param string $plugin_slug Plugin slug.
	 *
	 * @since  1.0.0
	 */
	public function __construct( string $plugin_name = 'WPOSA', string $plugin_version = '0.1', string $plugin_slug = 'WPOSA', string $plugin_prefix = 'WPOSA' ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->plugin_slug    = $plugin_slug;
		$this->plugin_prefix  = $plugin_prefix;
	}

	public function get_prefix(): string {
		return $this->plugin_prefix;
	}

	public function setup_hooks() {
		// Enqueue the admin scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

		// Hook it up.
		add_action( 'admin_init', [ $this, 'admin_init' ] );

		// Menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		// Ajax.
		add_action( 'wp_ajax_' . Utils::get_plugin_prefix() . '_reset_form', [ $this, 'reset_form' ] );

		// Hide all admin notices.
		add_action( 'in_admin_header', [ $this, 'hide_admin_notices' ] );
	}

	/**
	 * Hide all admin notices for plugin pages.
	 *
	 * @return void
	 */
	public function hide_admin_notices(): void {
		$current_screen = get_current_screen();

		if ( ! in_array( $current_screen->id, [ 'toplevel_page_recrawler', 'recrawler_page_recrawler-log' ] ) ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Admin Scripts.
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts() {
		global $wp_version;

		// Color Picker.
		wp_enqueue_script(
			'iris',
			admin_url( 'js/iris.min.js' ),
			array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
			$wp_version,
			true
		);

		wp_enqueue_script(
			Utils::get_plugin_prefix() . '-admin',
			Utils::get_plugin_asset_url( 'js/admin.js' ),
			[ 'jquery', 'iris' ],
			filemtime( Utils::get_plugin_asset_path( 'js/admin.js' ) ),
			[
				'in_footer' => true,
			]
		);

		$settings = [
			'redirect_url' => esc_url( admin_url( 'admin.php?page=' . Utils::get_plugin_slug() ) ),
		];

		wp_add_inline_script(
			Utils::get_plugin_prefix() . '-admin',
			'var recrawlerSettings = ' . json_encode( $settings ),
			'before'
		);

		wp_localize_script(
			Utils::get_plugin_prefix() . '-admin',
			'recrawlerLocalize',
			[
				'are_you_sure' => esc_js( __( 'Are you sure?', 'recrawler' ) )
			]
		);

		wp_enqueue_style(
			Utils::get_plugin_prefix() . '-admin',
			Utils::get_plugin_asset_url( 'css/admin.css' ),
			[],
			filemtime( Utils::get_plugin_asset_path( 'css/admin.css' ) )
		);
	}


	/**
	 * Set Sections.
	 *
	 * @param array $sections
	 * @since 1.0.0
	 */
	public function set_sections( $sections ) {
		// Bail if not array.
		if ( ! is_array( $sections ) ) {
			return false;
		}

		// Assign to the sections array.
		$this->sections_array = $sections;

		return $this;
	}


	/**
	 * Add a single section.
	 *
	 * @param array $section
	 * @since 1.0.0
	 */
	public function add_section( $section ) {
		// Bail if not array.
		if ( ! is_array( $section ) ) {
			return false;
		}

		$section['id'] = $this->get_prefix() . '_' . $section['id'];

		// Assign the section to sections array.
		$this->sections_array[] = $section;

		return $this;
	}


	/**
	 * Set Fields.
	 *
	 * @since 1.0.0
	 */
	public function set_fields( $fields ) {
		// Bail if not array.
		if ( ! is_array( $fields ) ) {
			return false;
		}

		// Assign the fields.
		$this->fields_array = $fields;

		return $this;
	}



	/**
	 * Add a single field.
	 *
	 * @since 1.0.0
	 */
	public function add_field( $section, $field_array ) {
		// Set the defaults
		$defaults = array(
			'id'   => '',
			'name' => '',
			'desc' => '',
			'type' => 'text',
		);

		// Combine the defaults with user's arguments.
		$arg = wp_parse_args( $field_array, $defaults );

		// Each field is an array named against its section.
		$this->fields_array[ $this->get_prefix() . '_' . $section ][] = $arg;

		return $this;
	}

	/**
	 * Add sidebar cards.
	 *
	 * @param array $card
	 *
	 * @return $this
	 */
	public function add_sidebar_card( array $card ): WPOSA {
		$this->sidebar_cards[] = $card;

		return $this;
	}

	public function get_sidebar_cards() {
		return $this->sidebar_cards;
	}

	public function get_sidebar_cards_total() {
		return count( $this->get_sidebar_cards() );
	}

	private function convert_array_to_attributes( array $args ): string {
		$result = [];

		if ( count( $args ) ) {
			foreach ( $args as $attr_key => $attr_value ) {
				if ( $attr_value === true || $attr_value === false ) {
					if ( $attr_value === true ) {
						$result[] = esc_attr( $attr_key );
					}
				} else {
					$result[] = sprintf(
						'%s="%s"',
						esc_attr( $attr_key ),
						esc_attr( $attr_value )
					);
				}
			}
		}

		return implode( ' ', $result );
	}

	/**
	 * Initialize API.
	 *
	 * Initializes and registers the settings sections and fields.
	 * Usually this should be called at `admin_init` hook.
	 *
	 * @since  1.0.0
	 */
	function admin_init() {
		/**
		 * Register the sections.
		 *
		 * Sections array is like this:
		 *
		 * $sections_array = array (
		 *   $section_array,
		 *   $section_array,
		 *   $section_array,
		 * );
		 *
		 * Section array is like this:
		 *
		 * $section_array = array (
		 *   'id'    => 'section_id',
		 *   'title' => 'Section Title'
		 * );
		 *
		 * @since 1.0.0
		 */
		foreach ( $this->sections_array as $section ) {
			if ( get_option( $section['id'] ) === false ) {
				// Add a new field as section ID.
				add_option( $section['id'], '', '', false );
			}

			// Deals with sections description.
			if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
				// Build HTML.
				$section['desc'] = '<div class="inside wposa-section-description">' . wp_kses( $section['desc'], self::ALLOWED_HTML ) . '</div>';

				// Create the callback for description.
				$callback = function() use ( $section ) {
					echo wp_kses( str_replace( '"', '\"', $section['desc'] ), self::ALLOWED_HTML );
				};

			} elseif ( isset( $section['callback'] ) ) {
				$callback = $section['callback'];
			} else {
				$callback = null;
			}

			/**
			 * Add a new section to a settings page.
			 *
			 * @param string $id
			 * @param string $title
			 * @param callable $callback
			 * @param string $page | Page is same as section ID.
			 * @since 1.0.0
			 */
			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		} // foreach ended.

		/**
		 * Register settings fields.
		 *
		 * Fields array is like this:
		 *
		 * $fields_array = array (
		 *   $section => $field_array,
		 *   $section => $field_array,
		 *   $section => $field_array,
		 * );
		 *
		 *
		 * Field array is like this:
		 *
		 * $field_array = array (
		 *   'id'   => 'id',
		 *   'name' => 'Name',
		 *   'type' => 'text',
		 * );
		 *
		 * @since 1.0.0
		 */
		foreach ( $this->fields_array as $section => $field_array ) {
			foreach ( $field_array as $field ) {
				// ID.
				$id = isset( $field['id'] ) ? $field['id'] : false;

				// Type.
				$type = isset( $field['type'] ) ? $field['type'] : 'text';

				// Name.
				$name = isset( $field['name'] ) ? $field['name'] : 'No Name Added';

				// Label for.
				$label_for = "{$section}[{$field['id']}]";

				// Description.
				$description = isset( $field['desc'] ) ? $field['desc'] : '';

				// Size.
				$size = isset( $field['size'] ) ? $field['size'] : null;

				// Options.
				$options = isset( $field['options'] ) ? $field['options'] : '';

				// Standard default value.
				$default = isset( $field['default'] ) ? $field['default'] : '';

				// Standard default placeholder.
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

				// Readonly attribute.
				$readonly = $field['readonly'] ?? false;

				// Sanitize Callback.
				$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : '';

				$help_tab = $field['help_tab'] ?? '';
				$class    = $field['class'] ?? "wposa-form-table__row wposa-form-table__row_type_{$type} wposa-form-table__row_{$section}_{$id}";

				$args = array(
					'id'                => $id,
					'type'              => $type,
					'name'              => $name,
					'label_for'         => $label_for,
					'desc'              => $description,
					'section'           => $section,
					'size'              => $size,
					'options'           => $options,
					'std'               => $default,
					'placeholder'       => $placeholder,
					'sanitize_callback' => $sanitize_callback,
					'attributes'        => [
						'readonly' => $readonly,
					],
					'class'             => $class,
				);

				if ( ! empty( $field['button_class'] ) ) {
					$args['button_class'] = $field['button_class'];
				}

				if ( $help_tab ) {
					$name .= $this->show_help_tab_toggle( $help_tab );
				}

				/**
				 * Add a new field to a section of a settings page.
				 *
				 * @param string   $id
				 * @param string   $title
				 * @param callable $callback
				 * @param string   $page
				 * @param string   $section = 'default'
				 * @param array    $args = array()
				 * @since 1.0.0
				 */
				// @param string 	$id
				$field_id = $section . '[' . $field['id'] . ']';

				add_settings_field(
					$field_id,
					$name,
					array( $this, 'callback_' . $type ),
					$section,
					$section,
					$args
				);
			} // foreach ended.
		} // foreach ended.

		// Creates our settings in the fields table.
		foreach ( $this->sections_array as $section ) {
			/**
			 * Registers a setting and its sanitization callback.
			 *
			 * @param string $field_group   | A settings group name.
			 * @param string $field_name    | The name of an option to sanitize and save.
			 * @param callable  $sanitize_callback = ''
			 * @since 1.0.0
			 */
			register_setting( $section['id'], $section['id'], array( $this, 'sanitize_fields' ) );
		} // foreach ended.

	} // admin_init() ended.


	/**
	 * Sanitize callback for Settings API fields.
	 *
	 * @since 1.0.0
	 */
	public function sanitize_fields( $fields ) {
		foreach ( $fields as $field_slug => $field_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $field_slug );

			// If callback is set, call it.
			if ( $sanitize_callback ) {
				$fields[ $field_slug ] = call_user_func( $sanitize_callback, $field_value );
				continue;
			}
		}

		return $fields;
	}


	/**
	 * Get sanitization callback for given option slug
	 *
	 * @param string $slug option slug.
	 * @return mixed string | bool false
	 * @since  1.0.0
	 */
	function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}

		// Iterate over registered fields and see if we can find proper callback.
		foreach ( $this->fields_array as $section => $field_array ) {
			foreach ( $field_array as $field ) {
				if ( $field['name'] != $slug ) {
					continue;
				}

				// Return the callback name.
				return isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : false;
			}
		}

		return false;
	}


	/**
	 * Get field description for display
	 *
	 * @param array $args settings field args
	 */
	public function get_field_description( $args ) {
		if ( ! empty( $args['desc'] ) ) {
			$desc = sprintf(
				'<p class="description">%s</p>',
				is_callable( $args['desc'] )
					? call_user_func( $args['desc'] )
					: $args['desc']
			);
		} else {
			$desc = '';
		}

		return wp_kses( $desc, self::ALLOWED_HTML );
	}


	/**
	 * Displays a title field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_title( $args ) {
		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		if ( '' !== $args['name'] ) {
			$name = $args['name'];
		} else {
		};
		$type = isset( $args['type'] ) ? $args['type'] : 'title';

		echo esc_html( $value );
	}


	/**
	 * Displays a text field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_text( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$type  = isset( $args['type'] ) ? $args['type'] : 'text';

		$attributes = $this->convert_array_to_attributes( $args['attributes'] );

		$html  = sprintf(
			'<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s" placeholder="%6$s" %7$s/>',
			esc_attr( $type ),
			esc_attr( $size ),
			esc_attr( $args['section'] ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $args['placeholder'] ),
			$attributes
		);

		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}


	/**
	 * Displays a url field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_url( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays a number field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_number( $args ) {
		$this->callback_text( $args );
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_checkbox( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$html  = '<fieldset>';
		$html .= sprintf( '<label for="wposa-%1$s[%2$s]">', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="checkbox" class="checkbox" id="wposa-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
		$html .= sprintf( '%1$s</label>', $args['desc'] );
		$html .= '</fieldset>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a iOS switch checkbox for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_switch( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$html  = '<fieldset>';
		$html .= sprintf( '<label for="wposa-%1$s[%2$s]">', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="checkbox" class="wposa-field wposa-field--switch" id="wposa-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
		$html .= sprintf( '%1$s</label>', $args['desc'] );
		$html .= '</fieldset>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_multicheck( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '<fieldset>';
		$html .= sprintf(
			'<input type="hidden" name="%s[%s][]" value="" />',
			$args['section'], $args['id']
		);
		foreach ( $args['options'] as $key => $label ) {
			$checked = isset( $value[ $key ] ) ? $value[ $key ] : '0';
			$html   .= sprintf( '<label for="wposa-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html   .= sprintf( '<input type="checkbox" class="checkbox" id="wposa-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			$html   .= sprintf( '%1$s</label><br>', $label );
		}
		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_radio( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '<fieldset>';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<label for="wposa-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
			$html .= sprintf( '<input type="radio" class="radio" id="wposa-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
			$html .= sprintf( '%1$s</label><br>', $label );
		}
		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_select( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}
		$html .= '</select>';
		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_textarea( $args ) {

		$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value );
		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array $args settings field args.
	 * @return string
	 */
	function callback_html( $args ) {
		echo wp_kses( $this->get_field_description( $args ), self::ALLOWED_HTML );
	}

	/**
	 * Displays a file upload field for a settings field
	 *
	 * @param array $args settings field args.
	 */
	function callback_file( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section'] . '[' . $args['id'] . ']';
		$label = $args['options']['button_label'] ?? __('Choose File', 'recrawler');

		$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays an image upload field with a preview
	 *
	 * @param array $args settings field args.
	 */
	function callback_image( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id    = $args['section'] . '[' . $args['id'] . ']';
		$label = $args['options']['button_label'] ?? __('Choose Image', 'recrawler');

		$html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
		$html .= $this->get_field_description( $args );
		$html .= '<p class="wpsa-image-preview"><img src=""/></p>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a password field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_password( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Displays a color picker field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_color( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'], $args['placeholder'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html  = sprintf( '<input type="text" class="%1$s-text color-picker" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" placeholder="%6$s" />', $size, $args['section'], $args['id'], $value, $args['std'], $args['placeholder'] );
		$html .= $this->get_field_description( $args );

		echo wp_kses( $html, self::ALLOWED_HTML );
	}


	/**
	 * Displays a separator field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_separator( $args ) {
		?>
		<div class="wposa-field wposa-field--separator"></div>
		<?php
	}

	/**
	 * Displays a Button field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_button( $args ) {
		$value = $args['placeholder'] ?? __( 'Submit' );
		$class = $args['button_class'] ?? 'button-secondary';
		$id    = $args['id'] ?? time();
		?>
		<input
			type="button"
			id="<?php echo esc_attr( $id ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="button <?php echo esc_attr( $class ); ?>"
		/>
		<?php echo wp_kses( $this->get_field_description( $args ), self::ALLOWED_HTML ); ?>
		<?php
	}

	/**
	 * Displays a Button field for a settings field
	 *
	 * @param array $args settings field args
	 */
	function callback_hidden( $args ) {
		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		?>
		<input
			type="hidden"
			name="<?php echo esc_attr( $args['section'] ); ?>[<?php echo esc_attr( $args['id'] ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<?php
	}

	/**
	 * Get the value of a settings field
	 *
	 * @param string $option  settings field name.
	 * @param string $section the section name this field belongs to.
	 * @param mixed  $default default text if it's not found.
	 * @return mixed
	 */
	public function get_option( string $option, string $section, $default = '' ) {
		$section = str_replace( $this->get_prefix() . '_', '', $section );
		$options = get_option( $this->get_prefix() . '_' . $section );

		if ( isset( $options[ $option ] ) ) {
			return apply_filters( 'wposa/get_option', $options[ $option ], $option, $section, $default );
		}

		return apply_filters( 'wposa/get_option', $default, $option, $section, $default );
	}

	public function set_option( string $option, $value, string $section ): bool {
		$name = $this->get_prefix() . '_' . $section;

		// Get option.
		$options = get_option( $name );

		if ( ! $options ) {
			return false;
		}

		// Update option.
		$options[ $option ] = $value;

		return update_option( $name, $options );
	}

	/**
	 * Add submenu page to the Settings main menu.
	 *
	 * @param string $page_title
	 * @param string $menu_title
	 * @param string $capability
	 * @param string $menu_slug
	 * @param callable $function = ''
	 * @author Ahmad Awais
	 * @since  [version]
	 */
	public function admin_menu() {
		add_menu_page(
			$this->plugin_name,
			$this->plugin_name,
			'manage_options',
			$this->plugin_slug,
			array( $this, 'plugin_page' ),
			'dashicons-rest-api'
		);
	}

	public function plugin_page() {
		?>
		<div class="wposa">
			<div class="wposa-header">
				<div class="wposa-header--left">
					<img class="wposa-logo" title="ReCrawler" src="<?php echo esc_url( Utils::get_plugin_asset_url( 'images/icons/logo.svg' ) ); ?>" width="80" alt="" />
				</div>
				<div class="wposa-header--center">
					<div class="wposa-heading"><?php echo esc_html( $this->plugin_name ); ?></div>
					<div class="wposa-version"><?php esc_html_e( 'Version' ); ?>: <?php echo esc_html( $this->plugin_version )?></div>
				</div>
				<div class="wposa-header--right">
					<p><?php esc_html_e( 'ReCrawler is a small WordPress Plugin for quickly notifying search engines whenever their website content is created, updated, or deleted.', 'recrawler' ); ?></p>
				</div>
			</div>
			<?php $this->show_navigation(); ?>
			<div class="wposa__grid">
				<div class="wposa__column">
					<?php $this->show_forms(); ?>
				</div>
				<?php if ( $this->get_sidebar_cards_total() ) : ?>
					<div class="wposa__column wposa__sidebar">
						<?php foreach ( $this->get_sidebar_cards() as $card ) : ?>
							<div class="card wposa-card wposa-card--<?php echo esc_attr( $this->get_prefix() )?>_<?php echo esc_attr( $card['id'] )?>">
								<?php if ( ! empty( $card['title'] ) ) : ?>
									<h2 class="title wposa__title wposa__title--h2 wposa-card__title"><?php echo esc_html( $card['title'] )?></h2>
								<?php endif; ?>
								<div class="wposa-card__content">
									<?php echo wp_kses( $card['desc'], self::ALLOWED_HTML ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Show navigations as tab
	 *
	 * Shows all the settings section labels as tab
	 */
	function show_navigation() {
		$html = sprintf(
			'<nav class="nav-tab-wrapper" aria-label="%s">',
			esc_html__( 'Secondary Navigation', 'recrawler' )
		);

		foreach ( $this->sections_array as $tab ) {
			if ( isset( $tab['disabled'] ) && $tab['disabled'] === true ) {
				if ( isset( $tab['badge'] ) ) {
					$html .= sprintf( '<span class="nav-tab wposa-nav-tab wposa-nav-tab--disabled" id="%1$s-tab">%2$s <span class="wposa-badge">%3$s</span></span>', $tab['id'], $tab['title'], $tab['badge'] );
				} else {
					$html .= sprintf( '<span class="nav-tab wposa-nav-tab wposa-nav-tab--disabled" id="%1$s-tab">%2$s</span>', $tab['id'], $tab['title'] );
				}
			} else {
				$html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
			}
		}

		$html .= '</nav>';

		echo wp_kses( $html, self::ALLOWED_HTML );
	}

	/**
	 * Show the section settings forms
	 *
	 * This function displays every sections in a different form
	 */
	function show_forms() {
		$default = array(
			'label_submit' => null,
			'submit_type'  => 'primary',
			'wrap'         => false,
			'attributes'   => null,
			'reset_button' => true,
		);
		?>
		<div class="metabox-holder">
			<?php foreach ( $this->sections_array as $form ) : ?>
				<?php
				$form = wp_parse_args( $form, $default );
				?>
				<div id="<?php echo esc_attr( $form['id'] ); ?>" class="wposa__group" >
					<form class="wposa__form" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php
						do_action( 'wsa_form_top_' . $form['id'], $form );
						settings_fields( $form['id'] );
						do_settings_sections( $form['id'] );
						do_action( 'wsa_form_bottom_' . $form['id'], $form );
						?>
						<div class="wposa-footer">
							<div class="wposa-footer__column wposa-footer__column--left">
								<?php submit_button( $form['label_submit'], $form['submit_type'], 'submit_' . $form['id'], $form['wrap'], $form['attributes'] ); ?>
							</div>
							<div class="wposa-footer__column wposa-footer__column--right">
								<?php if ( $form['reset_button'] ) : ?>
									<input type="button"
										   class="button button-danger button-link"
										   data-section="<?php echo esc_attr( $form['id'] ); ?>"
										   id="<?php echo esc_attr( $form['id'] ); ?>_reset_form"
										   value="<?php echo esc_attr( __( 'Reset Form', 'recrawler' ) ); ?>"
									/>
								<?php endif; ?>
							</div>
						</div>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Show help tab toggle.
	 *
	 * @param string $tab_id   Tab identified.
	 * @param string $tab_icon Tab icon.
	 */
	private function show_help_tab_toggle( $tab_id, $tab_icon = '?' ) {
		ob_start();
		?>
		<a title="<?php echo esc_attr__( 'Click to show Help tab', 'recrawler' ); ?>" class="wpsa-help-tab-toggle" data-tab="<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $tab_icon ); ?></a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Reset settings for given section.
	 *
	 * @return void
	 * @link https://wpmag.ru/2015/nonces-wordpress-security/
	 */
	public function reset_form(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				__( 'You have no rights to do this', 'recrawler' )
			);
		}

		$nonce   = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		$section = sanitize_text_field( wp_unslash( $_POST['section'] ?? '' ) );

		if ( ! $section ) {
			wp_send_json_error(
				__( 'Invalid section name', 'recrawler' )
			);
		}

		if ( ! $nonce ) {
			wp_send_json_error(
				__( 'Invalid nonce', 'recrawler' )
			);
		}

		if ( ! wp_verify_nonce( $nonce, $section . '-options' ) ) {
			wp_send_json_error(
				__( 'Invalid nonce', 'recrawler' )
			);
		}

		delete_option( $section );

		wp_send_json_success( 'ok' );
	}
}
