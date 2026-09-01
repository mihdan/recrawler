<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\ActionScheduler;
use PHPUnit\Framework\TestCase;

class ActionSchedulerTest extends TestCase {

	public function test_group_constant() {
		$this->assertSame('recrawler', ActionScheduler::GROUP);
	}

	public function test_async_returns_zero_when_not_initialized() {
		$result = ActionScheduler::async('test_action');
		$this->assertSame(0, $result);
	}

	public function test_async_returns_false_when_function_missing() {
		$this->assertSame(0, ActionScheduler::async('test_action'));
	}

	public function test_single_returns_zero_when_not_initialized() {
		$result = ActionScheduler::single(time(), 'test_action');
		$this->assertSame(0, $result);
	}

	public function test_recurring_returns_zero_when_not_initialized() {
		$result = ActionScheduler::recurring(time(), 3600, 'test_action');
		$this->assertSame(0, $result);
	}

	public function test_cron_returns_zero_when_not_initialized() {
		$result = ActionScheduler::cron(time(), 'hourly', 'test_action');
		$this->assertSame(0, $result);
	}

	public function test_cancel_does_nothing_when_not_initialized() {
		ActionScheduler::cancel('test_action');
		$this->assertTrue(true);
	}

	public function test_cancel_pending_does_nothing_when_not_initialized() {
		ActionScheduler::cancel_pending('test_action');
		$this->assertTrue(true);
	}

	public function test_is_scheduled_returns_false_when_not_initialized() {
		$result = ActionScheduler::is_scheduled('test_action');
		$this->assertFalse($result);
	}

	public function test_next_returns_false_when_not_initialized() {
		$result = ActionScheduler::next('test_action');
		$this->assertFalse($result);
	}

	public function test_custom_group_parameter() {
		$result = ActionScheduler::async('test_action', [], 'custom_group');
		$this->assertSame(0, $result);
	}
}
