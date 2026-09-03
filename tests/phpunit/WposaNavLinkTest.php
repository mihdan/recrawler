<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaNavLinkTest extends TestCase {

	private function make_wposa(): WPOSA {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_nav_link(
			[
				'page'  => 'recrawler-log',
				'title' => 'Log',
			]
		);

		return $wposa;
	}

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
		unset( $_GET['tab'], $_GET['page'] );
	}

	public function tearDown(): void {
		unset( $_GET['tab'], $_GET['page'] );
		parent::tearDown();
	}

	public function test_nav_link_is_rendered_after_tabs() {
		$_GET['page'] = 'recrawler';

		ob_start();
		$this->make_wposa()->show_navigation();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'page=recrawler-log', $html );
		$this->assertGreaterThan(
			strpos( $html, 'tab=general' ),
			strpos( $html, 'page=recrawler-log' ),
			'Nav link must come after the tabs'
		);
	}

	public function test_nav_link_is_active_on_its_own_page() {
		$_GET['page'] = 'recrawler-log';

		ob_start();
		$this->make_wposa()->show_navigation();
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/page=recrawler-log"[^>]*class="nav-tab nav-tab-active"/', $html );
	}

	public function test_no_tab_is_active_outside_the_settings_page() {
		$_GET['page'] = 'recrawler-log';

		ob_start();
		$this->make_wposa()->show_navigation();
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'tab=general" class="nav-tab nav-tab-active"', $html );
	}

	public function test_first_tab_is_active_on_the_settings_page() {
		$_GET['page'] = 'recrawler';

		ob_start();
		$this->make_wposa()->show_navigation();
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/tab=general"[^>]*class="nav-tab nav-tab-active"/', $html );
	}
}
