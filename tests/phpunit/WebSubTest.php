<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Providers\WebSub\WebSub;
use Mihdan\ReCrawler\Views\WPOSA;
use Mihdan\ReCrawler\Logger\Logger;
use PHPUnit\Framework\TestCase;

class WebSubTest extends TestCase {

	private $logger;
	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->logger = $this->createMock(Logger::class);
		$this->wposa  = $this->createMock(WPOSA::class);
	}

	public function test_get_slug() {
		$webSub = new WebSub($this->logger, $this->wposa);
		$this->assertSame('websub', $webSub->get_slug());
	}

	public function test_default_hubs_constant() {
		$this->assertNotEmpty(WebSub::DEFAULT_HUBS);
		$this->assertContains('https://pubsubhubbub.appspot.com/', WebSub::DEFAULT_HUBS);
	}

	public function test_is_enabled_returns_true() {
		$this->wposa->method('get_option')
			->with('enable', 'websub', 'off')
			->willReturn('on');

		$webSub = new WebSub($this->logger, $this->wposa);
		$this->assertTrue($webSub->is_enabled());
	}

	public function test_is_enabled_returns_false() {
		$this->wposa->method('get_option')
			->with('enable', 'websub', 'off')
			->willReturn('off');

		$webSub = new WebSub($this->logger, $this->wposa);
		$this->assertFalse($webSub->is_enabled());
	}

	public function test_get_hubs_returns_default_when_empty() {
		$this->wposa->method('get_option')
			->with('hubs', 'websub', '')
			->willReturn('');

		$webSub = new WebSub($this->logger, $this->wposa);
		$hubs = $webSub->get_hubs();

		$this->assertSame(WebSub::DEFAULT_HUBS, $hubs);
	}

	public function test_get_hubs_returns_configured_hubs() {
		$this->wposa->method('get_option')
			->with('hubs', 'websub', '')
			->willReturn("https://custom-hub.com/\nhttps://another-hub.com/");

		$webSub = new WebSub($this->logger, $this->wposa);
		$hubs = $webSub->get_hubs();

		$this->assertSame([
			'https://custom-hub.com/',
			'https://another-hub.com/',
		], $hubs);
	}

	public function test_setup_hooks_does_nothing_when_disabled() {
		$this->wposa->method('get_option')
			->with('enable', 'websub', 'off')
			->willReturn('off');

		$webSub = new WebSub($this->logger, $this->wposa);
		_expect_wp_mock('add_action', 0);
		_expect_wp_mock('add_filter', 0);

		$webSub->setup_hooks();
	}

	protected function tearDown(): void {
		parent::tearDown();
		_assert_wp_mock('add_action');
		_assert_wp_mock('add_filter');
	}

	public function test_setup_hooks_registers_actions_when_enabled() {
		$this->wposa->method('get_option')->willReturnMap([
			['enable', 'websub', 'off', 'on'],
			['hubs', 'websub', '', ''],
			['post_types', 'general', [], ['post']],
		]);

		$webSub = new WebSub($this->logger, $this->wposa);
		_expect_wp_mock('add_action', 9);
		_expect_wp_mock('add_filter', 2);

		$webSub->setup_hooks();
	}

	public function test_add_atom_namespace() {
		$webSub = new WebSub($this->logger, $this->wposa);

		$result = $webSub->add_atom_namespace('xmlns:dc="http://purl.org/dc/elements/1.1/" ');

		$this->assertStringContainsString('xmlns:atom="http://www.w3.org/2005/Atom"', $result);
		$this->assertStringContainsString('xmlns:dc=', $result);
	}

	public function test_add_html_head_links_outputs() {
		$this->wposa->method('get_option')
			->with('hubs', 'websub', '')
			->willReturn('');

		$webSub = new WebSub($this->logger, $this->wposa);

		ob_start();
		$webSub->add_html_head_links();
		$output = ob_get_clean();

		$this->assertStringContainsString('rel="hub"', $output);
		$this->assertStringContainsString('pubsubhubbub.appspot.com', $output);
	}

	public function test_async_publish_handler_does_not_crash() {
		$webSub = new WebSub($this->logger, $this->wposa);
		$webSub->async_publish_handler(1, ['https://hub1.com/', 'https://hub2.com/'], ['https://feed.com/rss2']);
		$this->assertTrue(true);
	}
}
