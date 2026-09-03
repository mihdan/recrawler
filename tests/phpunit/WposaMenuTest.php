<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class WposaMenuTest extends TestCase {

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();
	}

	public function test_every_tab_gets_submenu_item() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI' ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertContains( 'recrawler&tab=general', $slugs );
		$this->assertContains( 'recrawler&tab=ai', $slugs );
	}

	public function test_disabled_tab_has_no_submenu_item() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI', 'disabled' => true ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertNotContains( 'recrawler&tab=ai', $slugs );
	}

	public function test_tab_can_opt_out_of_menu() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'plugins', 'title' => 'Plugins', 'show_in_menu' => false ] );
		$wposa->admin_menu();

		$slugs = array_column( $GLOBALS['__wp_submenus'], 'menu_slug' );

		$this->assertContains( 'recrawler&tab=general', $slugs );
		$this->assertNotContains( 'recrawler&tab=plugins', $slugs );
	}

	public function test_highlights_submenu_of_current_tab() {
		$_GET['tab'] = 'ai';

		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );
		$wposa->add_tab( [ 'id' => 'ai', 'title' => 'AI' ] );

		$this->assertSame( 'recrawler&tab=ai', $wposa->highlight_current_submenu( 'recrawler' ) );

		unset( $_GET['tab'] );
	}

	public function test_leaves_other_screens_alone() {
		$wposa = new WPOSA( 'ReCrawler', '1.0.0', 'recrawler', 'recrawler' );
		$wposa->add_tab( [ 'id' => 'general', 'title' => 'General' ] );

		_set_wp_override( 'get_current_screen', function () {
			return (object) [ 'id' => 'edit-post' ];
		} );

		$this->assertSame( 'edit.php', $wposa->highlight_current_submenu( 'edit.php' ) );
	}
}
