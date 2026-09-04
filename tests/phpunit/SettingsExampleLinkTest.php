<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\HelpTab;
use Mihdan\ReCrawler\Views\Settings;
use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class SettingsExampleLinkTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	/**
	 * Get the `desc` of the `index_now.api_key` field as Settings registers it.
	 */
	private function get_api_key_desc(): string {
		$wposa    = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$settings = new Settings( $wposa, new HelpTab() );
		$settings->setup_vars();
		$settings->setup_fields();

		$property = new ReflectionProperty( WPOSA::class, 'fields_array' );
		$property->setAccessible( true );
		$fields = $property->getValue( $wposa );

		foreach ( $fields['recrawler_index_now'] as $field ) {
			if ( 'api_key' === $field['id'] ) {
				return (string) $field['desc'];
			}
		}

		$this->fail( 'Field index_now.api_key was not registered.' );
	}

	public function test_example_link_uses_stylesheet_class() {
		$this->assertStringContainsString( 'class="recrawler-example-link"', $this->get_api_key_desc() );
	}

	public function test_example_link_has_no_inline_style() {
		$desc = $this->get_api_key_desc();

		$this->assertStringNotContainsString( 'style=', $desc );
		$this->assertStringNotContainsString( '#2271b1', $desc );
	}
}
