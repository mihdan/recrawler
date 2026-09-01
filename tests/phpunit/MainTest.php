<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Main;
use Mihdan\ReCrawler\Logger\Logger;
use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase {

	private $container;
	private $logger;
	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->container = new \Mihdan\ReCrawler\Container();
		$this->logger    = $this->createMock(Logger::class);
		$this->wposa     = $this->createMock(WPOSA::class);

		$this->container->set(Logger::class, $this->logger);
		$this->container->set(WPOSA::class, $this->wposa);
	}

	public function test_add_last_update_column() {
		$main = new Main($this->container);
		$columns = $main->add_last_update_column(['title' => 'Title']);
		$this->assertArrayHasKey('recrawler', $columns);
		$this->assertStringContainsString('ReCrawler', $columns['recrawler']);
	}

	public function test_add_last_update_column_content_returns_for_wrong_column() {
		$main = new Main($this->container);

		ob_start();
		$main->add_last_update_column_content('wrong_column', 1);
		$output = ob_get_clean();

		$this->assertEmpty($output);
	}

	public function test_add_last_update_column_content_returns_for_zero_meta() {
		$main = new Main($this->container);

		ob_start();
		$main->add_last_update_column_content('recrawler', 1);
		$output = ob_get_clean();

		$this->assertEmpty($output);
	}

	public function test_add_last_update_column_content_shows_date() {
		$main = new Main($this->container);

		_set_wp_override('get_post_meta', function () { return 1700000000; });

		ob_start();
		$main->add_last_update_column_content('recrawler', 1);
		$output = ob_get_clean();

		$this->assertNotEmpty($output);
	}

	public function test_add_sorting_by_last_update_column() {
		$main = new Main($this->container);
		$columns = $main->add_sorting_by_last_update_column([]);
		$this->assertArrayHasKey('recrawler', $columns);
	}

	public function test_set_screen_option() {
		$main = new Main($this->container);
		$this->assertSame(20, $main->set_screen_option('', '', 20));
		$this->assertSame(50, $main->set_screen_option('', '', '50'));
	}

	public function test_add_settings_link_adds_link_for_this_plugin() {
		$main = new Main($this->container);

		$actions = $main->add_settings_link(['deactivate' => 'Deactivate'], 'recrawler/recrawler.php');
		$this->assertCount(2, $actions);
	}

	public function test_add_settings_link_does_not_add_for_other_plugins() {
		$main = new Main($this->container);

		$actions = $main->add_settings_link(['deactivate' => 'Deactivate'], 'other-plugin/other-plugin.php');
		$this->assertCount(1, $actions);
	}

	public function test_constants_accessible() {
		$this->assertSame('0.3.2', RECRAWLER_VERSION);
		$this->assertSame('recrawler', RECRAWLER_SLUG);
		$this->assertSame('recrawler', RECRAWLER_PREFIX);
		$this->assertSame('ReCrawler', RECRAWLER_NAME);
		$this->assertSame('recrawler/recrawler.php', RECRAWLER_BASENAME);
	}
}
