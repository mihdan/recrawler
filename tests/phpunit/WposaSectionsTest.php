<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaSectionsTest extends TestCase {

	private function make_wposa(): WPOSA {
		return new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_section_is_registered_on_its_tab_page() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_section( [ 'id' => 'logs', 'tab' => 'general', 'title' => 'Logs' ] );
		$wposa->admin_init();

		$sections = array_column( $GLOBALS['__wp_settings_sections'], 'page', 'id' );

		$this->assertArrayHasKey( 'recrawler_logs', $sections );
		$this->assertSame( 'recrawler_general', $sections['recrawler_logs'] );
	}

	public function test_field_renders_in_subsection_but_saves_to_tab_option() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_section( [ 'id' => 'logs', 'tab' => 'general', 'title' => 'Logs' ] );
		$wposa->add_field( 'general', [ 'id' => 'enable', 'type' => 'switcher', 'section' => 'logs' ] );
		$wposa->admin_init();

		$field = $GLOBALS['__wp_settings_fields'][0];

		$this->assertSame( 'recrawler_general', $field['page'] );
		$this->assertSame( 'recrawler_logs', $field['section'] );
		$this->assertSame( 'recrawler_general', $field['args']['section'] );
	}

	public function test_field_without_section_keeps_old_behaviour() {
		$wposa = $this->make_wposa();
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_field( 'general', [ 'id' => 'ping_delay', 'type' => 'number' ] );
		$wposa->admin_init();

		$field = $GLOBALS['__wp_settings_fields'][0];

		$this->assertSame( 'recrawler_general', $field['page'] );
		$this->assertSame( 'recrawler_general', $field['section'] );
	}

	public function test_get_sections_by_tab_filters_by_owner() {
		$wposa = $this->make_wposa();
		$wposa->add_section( [ 'id' => 'logs', 'tab' => 'general', 'title' => 'Logs' ] );
		$wposa->add_section( [ 'id' => 'quota', 'tab' => 'yandex_webmaster', 'title' => 'Quota' ] );

		$sections = $wposa->get_sections_by_tab( 'recrawler_general' );

		$this->assertCount( 1, $sections );
		$this->assertSame( 'recrawler_logs', $sections[0]['id'] );
	}
}
