<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Providers\BlockAiCrawler;
use Mihdan\ReCrawler\Enums\BlockAiCrawlerEnum;
use Mihdan\ReCrawler\Views\WPOSA;
use PHPUnit\Framework\TestCase;

class BlockAiCrawlerTest extends TestCase {

	private $wposa;

	public function setUp(): void {
		parent::setUp();
		_reset_wp_mocks();

		$this->wposa = $this->createMock(WPOSA::class);
	}

	public function test_setup_hooks_adds_init_and_do_robots() {
		$this->wposa->method('get_option')->willReturnMap([
			['bots', 'ai', [], ['CHATGPT_USER' => 'on']],
		]);

		$blocker = new BlockAiCrawler($this->wposa);
		_expect_wp_mock('add_action', 2);

				$blocker->setup_hooks();
		_assert_wp_mock('add_action');
	}

	public function test_maybe_block_does_nothing_when_no_bots() {
		$this->wposa->method('get_option')->willReturn([]);

		$blocker = new BlockAiCrawler($this->wposa);
		$blocker->maybe_block();
		$this->assertTrue(true);
	}

	public function test_maybe_block_blocks_matching_bot() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; ChatGPT-User)';

		$this->wposa->method('get_option')->willReturnMap([
			['bots', 'ai', [], ['CHATGPT_USER' => 'on']],
		]);

		$status_header_code = null;
		$nocache_called = false;
		_set_wp_override('status_header', function ($code) use (&$status_header_code) {
			$status_header_code = $code;
		});
		_set_wp_override('nocache_headers', function () use (&$nocache_called) {
			$nocache_called = true;
		});

		$blocker = new class($this->wposa) extends BlockAiCrawler {
			protected function block(): void {
				\status_header(403);
				\nocache_headers();
				throw new \RuntimeException('exit() was called');
			}
		};

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('exit() was called');

		try {
			$blocker->maybe_block();
		} catch (\RuntimeException $e) {
			$this->assertSame(403, $status_header_code);
			$this->assertTrue($nocache_called);
			throw $e;
		}
	}

	public function test_maybe_block_skips_non_matching_bot() {
		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot';

		$this->wposa->method('get_option')->willReturnMap([
			['bots', 'ai', [], ['CHATGPT_USER' => 'on']],
		]);

		$blocker = new BlockAiCrawler($this->wposa);

		$called = false;
		_set_wp_override('status_header', function () use (&$called) {
			$called = true;
		});

		$blocker->maybe_block();
		$this->assertFalse($called);
	}

	public function test_maybe_block_skips_empty_user_agent() {
		$_SERVER['HTTP_USER_AGENT'] = '';

		$this->wposa->method('get_option')->willReturnMap([
			['bots', 'ai', [], ['CHATGPT_USER' => 'on']],
		]);

		$blocker = new BlockAiCrawler($this->wposa);

		$called = false;
		_set_wp_override('status_header', function () use (&$called) {
			$called = true;
		});

		$blocker->maybe_block();
		$this->assertFalse($called);
	}

	public function test_add_robots_rules_outputs_disallow() {
		$this->wposa->method('get_option')->willReturnMap([
			['bots', 'ai', [], ['CHATGPT_USER' => 'on']],
		]);

		$blocker = new BlockAiCrawler($this->wposa);

		ob_start();
		$blocker->add_robots_rules();
		$output = ob_get_clean();

		$this->assertStringContainsString('User-agent: ChatGPT-User', $output);
		$this->assertStringContainsString('Disallow: /', $output);
	}

	public function test_add_robots_rules_does_nothing_when_no_bots() {
		$this->wposa->method('get_option')->willReturn([]);

		$blocker = new BlockAiCrawler($this->wposa);

		ob_start();
		$blocker->add_robots_rules();
		$output = ob_get_clean();

		$this->assertEmpty($output);
	}
}
