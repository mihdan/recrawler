<?php

namespace The_SEO_Framework\Meta;

if ( ! class_exists( 'The_SEO_Framework\Meta\Robots' ) ) {
	class Robots {
		public static function get_generated_meta( $args = null, $get = null, $options = 0 ) {
			$meta = \_get_seo_stub( 'tsf_meta' );
			if ( 'throw' === $meta ) {
				throw new \RuntimeException( 'TSF exploded' );
			}
			return null === $meta ? [] : $meta;
		}
	}
}
