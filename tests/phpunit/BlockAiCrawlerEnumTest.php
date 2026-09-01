<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\Enums\BlockAiCrawlerEnum;
use PHPUnit\Framework\TestCase;

class BlockAiCrawlerEnumTest extends TestCase {

	public function test_enum_has_all_expected_cases() {
		$cases = BlockAiCrawlerEnum::cases();

		$this->assertGreaterThanOrEqual( 50, count( $cases ) );
	}

	public function test_chatgpt_case_exists() {
		$case = BlockAiCrawlerEnum::CHATGPT_USER;

		$this->assertSame( 'ChatGPT-User', $case->value );
	}

	public function test_bing_ai_case_exists() {
		$case = BlockAiCrawlerEnum::BING_AI;

		$this->assertSame( 'BingAI', $case->value );
	}

	public function test_openai_case_exists() {
		$case = BlockAiCrawlerEnum::OPENAI;

		$this->assertSame( 'OpenAI', $case->value );
	}

	public function test_anthropic_case_exists() {
		$case = BlockAiCrawlerEnum::ANTHROPIC_AI;

		$this->assertSame( 'AnthropicAI', $case->value );
	}

	public function test_to_array_returns_correct_structure() {
		$result = BlockAiCrawlerEnum::toArray();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'CHATGPT_USER', $result );
		$this->assertSame( 'ChatGPT-User', $result['CHATGPT_USER'] );
	}

	public function test_to_array_has_all_cases() {
		$result = BlockAiCrawlerEnum::toArray();
		$cases = BlockAiCrawlerEnum::cases();

		$this->assertCount( count( $cases ), $result );
	}

	public function test_all_values_are_strings() {
		foreach ( BlockAiCrawlerEnum::cases() as $case ) {
			$this->assertIsString( $case->value, $case->name . ' value should be a string' );
		}
	}

	public function test_from_returns_correct_case() {
		$case = BlockAiCrawlerEnum::from( 'ChatGPT-User' );

		$this->assertSame( BlockAiCrawlerEnum::CHATGPT_USER, $case );
	}

	public function test_try_from_returns_correct_case() {
		$case = BlockAiCrawlerEnum::tryFrom( 'Grammarly' );

		$this->assertSame( BlockAiCrawlerEnum::GRAMMARLY, $case );
	}

	public function test_try_from_returns_null_for_unknown() {
		$case = BlockAiCrawlerEnum::tryFrom( 'UnknownBot' );

		$this->assertNull( $case );
	}
}
