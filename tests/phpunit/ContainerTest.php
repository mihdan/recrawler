<?php

namespace Mihdan\ReCrawler\Tests;

use Mihdan\ReCrawler\BlockAiCrawlerEnum;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

	public function test_container_auto_registers_itself() {
		$container = new \Mihdan\ReCrawler\Container();

		$this->assertSame( $container, $container->get( \Mihdan\ReCrawler\Container::class ) );
	}

	public function test_set_and_get_service() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( 'test_service', 'value' );

		$this->assertTrue( $container->has( 'test_service' ) );
		$this->assertSame( 'value', $container->get( 'test_service' ) );
	}

	public function test_set_closure_factory() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( 'factory', function ( $c ) {
			return 'created';
		} );

		$this->assertSame( 'created', $container->get( 'factory' ) );
	}

	public function test_set_class_string_resolved() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( 'simple', \stdClass::class );

		$result = $container->get( 'simple' );
		$this->assertInstanceOf( \stdClass::class, $result );
	}

	public function test_get_returns_same_instance() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( 'test', 'value' );

		$a = $container->get( 'test' );
		$b = $container->get( 'test' );

		$this->assertSame( $a, $b );
	}

	public function test_make_returns_new_instance() {
		$container = new \Mihdan\ReCrawler\Container();

		$a = $container->make( \stdClass::class );
		$b = $container->make( \stdClass::class );

		$this->assertNotSame( $a, $b );
	}

	public function test_make_throws_for_nonexistent_class() {
		$container = new \Mihdan\ReCrawler\Container();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'could not be resolved because class not exist' );

		$container->make( 'NonexistentClass' );
	}

	public function test_get_throws_for_unknown_service() {
		$container = new \Mihdan\ReCrawler\Container();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'not found in the Container' );

		$container->get( 'unknown_service' );
	}

	public function test_set_overrides_previous_value() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( 'key', 'first' );
		$container->set( 'key', 'second' );

		$this->assertSame( 'second', $container->get( 'key' ) );
	}

	public function test_has_returns_false_for_unknown() {
		$container = new \Mihdan\ReCrawler\Container();

		$this->assertFalse( $container->has( 'nope' ) );
	}

	public function test_make_resolves_constructor_dependencies() {
		$container = new \Mihdan\ReCrawler\Container();
		$container->set( \stdClass::class, new \stdClass() );

		$obj = $container->make( ContainerTest_DependentClass::class );

		$this->assertInstanceOf( ContainerTest_DependentClass::class, $obj );
	}

	public function test_resolve_optional_parameters() {
		$container = new \Mihdan\ReCrawler\Container();

		$obj = $container->make( ContainerTest_OptionalParam::class );

		$this->assertSame( 'default', $obj->value );
	}
}

class ContainerTest_DependentClass {
	public function __construct( \stdClass $dep ) {}
}

class ContainerTest_OptionalParam {
	public $value;

	public function __construct( string $value = 'default' ) {
		$this->value = $value;
	}
}
