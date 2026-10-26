<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * Functions class
 * Contains helper functions for data sanitization, content filtering, and post arrangement.
 * 
 * @package APB
 */
class Functions{	

	/**
	 * Recursively sanitizes an array of data.
	 *
	 * @param array $array The array to sanitize.
	 * @return array|false The sanitized array, or false if input is not an array.
	 */
	public static function sanitize_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = self::sanitize_array( $value );
			} elseif ( 'true' === $value ) {
				$array[ $key ] = true;
			} elseif ( 'false' === $value ) {
				$array[ $key ] = false;
			} elseif ( is_numeric( $value ) ) {
				$array[ $key ] = $value + 0; // preserves int/float type
			} else {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		return $array;
	}

	/**
	 * Filters an array to include only numeric values (IDs).
	 *
	 * @param array $array The array to filter.
	 * @return array The filtered array containing only numeric IDs.
	 */
	public static function filterNaN( $array ) {
		return array_filter( $array, function( $id ) {
			return $id && is_numeric( $id );
		});
	}

	/**
	 * Calculates the word count of a string after stripping HTML tags.
	 *
	 * @param string $content The content to count words in.
	 * @return int The word count.
	 */
	public static function wordCount( $content ) {
		return $content ? count( preg_split( 
			'/[\s]+/',
			preg_replace( '/(<([^>]+)>)/i', '', $content )
		) ) : 0;
	}

	/**
	 * Filters content to allow only specific HTML tags and returns plain text.
	 *
	 * @param string $rawContent The raw HTML content.
	 * @return string The filtered content.
	 */
	public static function applyContentFilter( $rawContent ){
		// remove script and style tag
		// $rawContent = preg_replace( '/<script\b[^>]*>(.*?)<\/script>|<style\b[^>]*>(.*?)<\/style>/is', '', $rawContent );

		$textAllowedHTML = [ 'a' => [ 'href' => [], 'title' => [] ], 'br' => [], 'em' => [], 'strong' => [] ];
		$innerAllowedHTML = array_merge( [ 'span' => [ $textAllowedHTML ] ], $textAllowedHTML );
		$allowedHTML = array_merge( [ 'p' => [ $innerAllowedHTML ] ], $innerAllowedHTML );
		$content = wp_kses( $rawContent, $allowedHTML );
		$plainText = trim( wp_strip_all_tags( $content ?? '' ) );

		return $plainText; // Custom filter is available in the premium version.
	}

	/**
	 * Arranges raw WP_Post objects into a structured array for frontend/editor use.
	 *
	 * @param array		$posts			Array of WP_Post objects.
	 * @param string	$postType		The post type slug.
	 * @param string	$fImgSize		Featured image size.
	 * @param string	$excerptFrom	Where to pull the excerpt from ('excerpt', 'content').
	 * @param int		$excerptLength	Length of the excerpt.
	 * @return array	The arranged posts data.
	 */
	public static function arrangedPosts ( $posts, $postType, $fImgSize = 'full', $excerptFrom = 'excerpt', $excerptLength = 25 ) {
		$arranged = [];

		$excerptLength = (int)$excerptLength;

		$taxOfPostType = array_diff( get_object_taxonomies( $postType ), array( 'post_format', 'category' ) );

		// Check if seo plugins are active
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$isYoastActive = is_plugin_active( 'wordpress-seo/wp-seo.php' );
		$isRankMathActive = is_plugin_active( 'seo-by-rank-math/rank-math.php' );
		$isAIOSEActive = is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) || is_plugin_active( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' );

		foreach( $posts as $post ){
			$id = $post->ID;
			$content = preg_replace( '/(<([^>]+)>)/i', '', $post->post_content );
			$contentWords = self::wordCount( $content );

			$thumbnail = [
				'url' => get_the_post_thumbnail_url( $post, $fImgSize ),
				'alt' => get_post_meta( get_post_thumbnail_id( $id ), '_wp_attachment_image_alt', true )
			];

			$taxonomies = [];
			foreach ( $taxOfPostType as $key => $slug ) {
				$terms = wp_get_post_terms( $id, $slug );

				$links = '';
				foreach( $terms as $index => $t ){
					$link = get_term_link( $t->slug, $slug );
					$terms[$index]->link = $link;

					$links .= sprintf( "<a href='%s' rel='%s'>%s</a>", esc_url( $link ), esc_attr( $slug ), esc_html( $t->name ) );
				};
				$taxonomies[$slug] = $links;
			}

			// Build excerpt based on source
			if ( 'content' === $excerptFrom ) {
				$contentOrExcerptArr = [ 'content' => $excerptLength > -1 ? wp_trim_words( self::applyContentFilter( $post->post_content ), $excerptLength + 5, '' ) : self::applyContentFilter( $post->post_content ) ];
			} else {
				$contentOrExcerptArr = [ 'excerpt' => self::applyContentFilter( $post->post_excerpt ) ];
			}

			$arranged[] = array_merge( [
				'id' => $id,
				'link' => get_permalink( $post ),
				'name' => $post->post_name,
				'thumbnail' => $thumbnail,
				'title' => esc_html( $post->post_title ),
				'postMeta' => [], // use get_post_meta( $post->ID ) for the post meta fields
				'author' => [
					'name' => get_the_author_meta( 'display_name', $post->post_author ),
					'link' => get_author_posts_url( $post->post_author )
				],
				'rawDate' => $post->post_date,
				'date' => get_the_date( 'M j, Y', $id ),
				'dateGMT' => $post->post_date_gmt,
				'modifiedDate' => $post->post_modified,
				'modifiedDateGMT' => $post->post_modified_gmt,
				'commentCount' => $post->comment_count,
				'commentStatus' => $post->comment_status,
				'views' => get_post_meta( $id, 'apb_post_views_count', true ) ?: 0,
				'categories' => [
					'coma' => get_the_category_list( ', ', '', $id ),
					'space' => get_the_category_list( ' ', '', $id )
				],
				'taxonomies' => $taxonomies,
				'readTime' => [
					'min' => floor( $contentWords / 200 ),
					'sec' => floor( $contentWords % 200 / ( 200 / 60 ) )
				],
				'status' => $post->post_status
			], $contentOrExcerptArr );
		}

		return $arranged;
	}

	/**
	 * Strips everything but a safe, static subset of SVG from a string.
	 * No <script>, no SMIL (<animate>, <set>), no event handler attributes,
	 * no <use>/<image>/<foreignObject> references.
	 *
	 * @param mixed $svg Raw SVG markup from a block attribute.
	 * @return string Sanitized SVG markup, or '' if the input is not a string.
	 */
	public static function sanitizeSvg( $svg ) {
		if ( ! is_string( $svg ) || '' === trim( $svg ) ) {
			return '';
		}

		$common = [
			'id' => true, 'class' => true, 'style' => true, 'transform' => true, 'opacity' => true,
			'fill' => true, 'fill-opacity' => true, 'fill-rule' => true,
			'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true,
			'stroke-opacity' => true, 'stroke-dasharray' => true, 'stroke-dashoffset' => true,
			'd' => true, 'x' => true, 'y' => true, 'cx' => true, 'cy' => true, 'r' => true, 'rx' => true, 'ry' => true,
			'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'points' => true,
			'width' => true, 'height' => true, 'clip-path' => true, 'clip-rule' => true, 'mask' => true,
		];

		// wp_kses lower-cases attribute names, so viewBox must be listed as viewbox.
		$allowed = [
			'svg'            => $common + [ 'xmlns' => true, 'viewbox' => true, 'preserveaspectratio' => true, 'aria-hidden' => true, 'role' => true, 'focusable' => true, 'version' => true ],
			'g'              => $common,
			'path'           => $common,
			'rect'           => $common,
			'circle'         => $common,
			'ellipse'        => $common,
			'line'           => $common,
			'polyline'       => $common,
			'polygon'        => $common,
			'defs'           => [],
			'clippath'       => [ 'id' => true, 'clippathunits' => true ],
			'lineargradient' => [ 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true, 'gradienttransform' => true ],
			'radialgradient' => [ 'id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientunits' => true, 'gradienttransform' => true ],
			'stop'           => [ 'offset' => true, 'stop-color' => true, 'stop-opacity' => true, 'style' => true ],
			'title'          => [],
			'desc'           => [],
		];

		return wp_kses( $svg, $allowed );
	}

	/**
	 * Sanitizes an icon attribute that may hold inline SVG or an image URL.
	 *
	 * @param mixed $icon Raw attribute value.
	 * @return string Safe SVG markup, a URL with no dangerous protocol, or ''.
	 */
	public static function sanitizeIcon( $icon ) {
		if ( ! is_string( $icon ) || '' === $icon ) {
			return '';
		}

		if ( 0 === strpos( ltrim( $icon ), '<svg' ) ) {
			return self::sanitizeSvg( $icon );
		}

		// Anything else is an image URL. Entity-encoded markup (what kses leaves a
		// Contributor) is not a URL, so it is dropped rather than echoed back.
		if ( false !== strpos( html_entity_decode( $icon, ENT_QUOTES | ENT_HTML5 ), '<' ) ) {
			return '';
		}

		return wp_kses_bad_protocol( $icon, wp_allowed_protocols() );
	}
}