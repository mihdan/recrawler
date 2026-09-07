<?php

namespace RankMath;

if ( ! class_exists( 'RankMath\Post' ) ) {
	class Post {
		public static function get_meta( $key, $post_id = 0, $default_value = '' ) {
			$robots = \_get_seo_stub( 'rankmath_robots' );
			if ( 'throw' === $robots ) {
				throw new \RuntimeException( 'Rank Math exploded' );
			}
			return null === $robots ? $default_value : $robots;
		}
	}
}
