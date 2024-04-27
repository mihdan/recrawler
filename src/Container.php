<?php // phpcs:ignoreFile
/**
 * Simple PHP DIC - DI Container in one file.
 * Supports autowiring and allows you to easily use it in your simple PHP applications and
 * especially convenient for WordPress plugins and themes.
 *
 * Author: Andrei Pisarevskii
 * Author Email: renakdup@gmail.com
 * Author Site: https://wp-yoda.com/en/
 *
 * Version: 0.2.5
 * Source Code: https://github.com/renakdup/simple-php-dic
 *
 * Licence: MIT License
 */

declare( strict_types=1 );

namespace Mihdan\ReCrawler;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

use function array_key_exists;
use function class_exists;
use function is_string;

######## PSR11 2.0 interfaces #########
# If you want to support PSR11, then remove 3 interfaces below
# (ContainerInterface, ContainerExceptionInterface, NotFoundExceptionInterface)
# and import PSR11 interfaces in this file:
# -----
# use Psr\Container\ContainerExceptionInterface;
# use Psr\Container\ContainerInterface;
# use Psr\Container\NotFoundExceptionInterface;
###############################
interface ContainerInterface {
	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 *
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 */
	public function get( string $id );

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool;
}

/**
 * Base interface representing a generic exception in a container.
 */
interface ContainerExceptionInterface extends \Throwable {}

/**
 * No entry was found in the container.
 */
interface NotFoundExceptionInterface extends ContainerExceptionInterface {}
######## PSR11 interfaces - END #########


###############################
#     Simple DIC code
###############################
class Container implements ContainerInterface {
	/**
	 * @var mixed[]
	 */
	protected array $services = [];

	/**
	 * @var mixed[]
	 */
	protected array $resolved = [];

	/**
	 * @var ReflectionClass[]
	 */
	protected array $reflection_cache = [];

	public function __construct() {
		// Auto-register the container
		$this->resolved = [
			self::class               => $this,
			ContainerInterface::class => $this,
		];
	}

	/**
	 * Set service to the container. Allows to set configurable services
	 * using factory "function () {}" as passed service.
	 *
	 * @param mixed $service
	 */
	public function set( string $id, $service ): void {
		$this->services[ $id ] = $service;
		unset( $this->resolved[ $id ] );
		unset( $this->reflection_cache[ $id ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( string $id ) {
		if ( isset( $this->resolved[ $id ] ) || array_key_exists( $id, $this->resolved ) ) {
			return $this->resolved[ $id ];
		}

		$service = $this->resolve( $id );

		$this->resolved[ $id ] = $service;

		return $service;
	}

	/**
	 * @inheritdoc
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->services );
	}

	/**
	 * Resolves service by its name. It returns a new instance of service every time, but the constructor's
	 * dependencies will not instantiate every time. If dependencies were resolved before
	 * then they will be passed as resolved dependencies.
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function make( string $id ): object {
		if ( ! class_exists( $id ) ) {
			$message = "Service `{$id}` could not be resolved because class not exist.";
			throw new ContainerException( $message );
		}

		return $this->resolve_object( $id );
	}

	/**
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function resolve( string $id ) {
		if ( $this->has( $id ) ) {
			$service = $this->services[ $id ];

			if ( $service instanceof Closure ) {
				return $service( $this );
			} elseif ( is_string( $service ) && class_exists( $service ) ) {
				return $this->resolve_object( $service );
			}

			return $service;
		}

		if ( class_exists( $id ) ) {
			return $this->resolve_object( $id );
		}

		throw new ContainerNotFoundException( "Service `{$id}` not found in the Container." );
	}

	/**
	 * @param class-string $service
	 *
	 * @return object
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function resolve_object( string $service ): object {
		try {
			$reflected_class = $this->reflection_cache[ $service ] ?? new ReflectionClass( $service );

			$constructor = $reflected_class->getConstructor();

			if ( ! $constructor ) {
				return new $service();
			}

			$params = $constructor->getParameters();

			if ( ! $params ) {
				return new $service();
			}

			$resolved_params = $this->resolve_parameters( $params );

		} catch ( ReflectionException $e ) {
			throw new ContainerException(
				"Service `{$service}` could not be resolved due the reflection issue: `{$e->getMessage()}`"
			);
		}

		return new $service( ...$resolved_params );
	}

	/**
	 * @param ReflectionParameter[] $params
	 *
	 * @return mixed[]
	 * @throws ContainerExceptionInterface
	 * @throws ReflectionException
	 */
	protected function resolve_parameters( array $params ): array {
		$resolved_params = [];
		foreach ( $params as $param ) {
			$resolved_params[] = $this->resolve_parameter( $param );
		}

		return $resolved_params;
	}

	/**
	 * @param ReflectionParameter $param
	 *
	 * @return mixed|object
	 * @throws ContainerExceptionInterface
	 * @throws ReflectionException
	 */
	protected function resolve_parameter( ReflectionParameter $param ) {
		if ( $param_class = $param->getClass() ) {
			return $this->get( $param_class->getName() );
		}

		if ( $param->isOptional() ) {
			return $param->getDefaultValue();
		}

		// @phpstan-ignore-next-line - Cannot call method getName() on ReflectionClass|null.
		$message = "Parameter `{$param->getName()}` of `{$param->getDeclaringClass()->getName()}` can't be resolved.";
		throw new ContainerException( $message );
	}

	protected function get_stack_trace(): string {
		$stackTraceArray  = debug_backtrace();
		$stackTraceString = '';

		foreach ( $stackTraceArray as $item ) {
			$file     = $item['file'] ?? '[internal function]';
			$line     = $item['line'] ?? '';
			$function = $item['function'] ?? ''; // @phpstan-ignore-line - 'function' on array always exists and is not nullable.
			$class    = $item['class'] ?? '';
			$type     = $item['type'] ?? '';

			$stackTraceString .= "{$file}({$line}): ";
			if ( ! empty( $class ) ) {
				$stackTraceString .= "{$class}{$type}";
			}
			$stackTraceString .= "{$function}()\n";
		}

		return $stackTraceString;
	}
}

class ContainerNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface {}

class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface {}
