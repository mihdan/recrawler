<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaNavigationTest extends TestCase {

	private function make_wposa(): WPOSA {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'logs', 'title' => 'Logs' ] );

		return $wposa;
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		unset( $_GET['tab'] );
		$_GET['page'] = 'recrawler';
	}

	public function tearDown(): void {
		unset( $_GET['tab'], $_GET['page'] );
		parent::tearDown();
	}

	public function test_defaults_to_first_tab() {
		$this->assertSame( 'recrawler_general', $this->make_wposa()->get_current_tab() );
	}

	public function test_reads_tab_from_query() {
		$_GET['tab'] = 'logs';
		$this->assertSame( 'recrawler_logs', $this->make_wposa()->get_current_tab() );
	}

	public function test_unknown_tab_falls_back_to_first() {
		$_GET['tab'] = 'nonexistent';
		$this->assertSame( 'recrawler_general', $this->make_wposa()->get_current_tab() );
	}

	public function test_navigation_renders_links_with_active_tab() {
		$_GET['tab'] = 'logs';

		ob_start();
		$this->make_wposa()->show_navigation();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'page=recrawler&tab=general', $html );
		$this->assertStringContainsString( 'page=recrawler&tab=logs', $html );
		$this->assertStringNotContainsString( 'href="#', $html );
		$this->assertMatchesRegularExpression( '/tab=logs"[^>]*class="nav-tab nav-tab-active"/', $html );
	}

	public function test_disabled_tab_is_not_a_link() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI', 'disabled' => true ] );

		ob_start();
		$wposa->show_navigation();
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'tab=ai', $html );
		$this->assertStringContainsString( 'wposa-nav-tab--disabled', $html );
	}
}
