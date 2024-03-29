<?php
/**
 * Main class.
 *
 * @package Mihdan\ReCrawler
 */

namespace Mihdan\ReCrawler;

interface SearchEngineInterface {
	public function get_slug(): string;
	public function get_name(): string;
	public function setup_hooks();
	public function is_enabled(): bool;
}
