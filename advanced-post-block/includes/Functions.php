<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

class Functions{	
	static function sanitize_array($array){
		if( !is_array( $array ) ) {
			return false;
		}

		foreach( $array as $key => $value ) {
			if( strpos( $key, 'secret_key' ) !== false && strlen( $value ) == 32 ) {
				$value = sanitize_text_field( str_replace( '<', '&lt;', $value ) ); 
				$value = sanitize_text_field( $value );
				$array[$key] = str_replace( ['&lt;', '&gt;', '&amp;'], [ '<', '>', '&'], $value );
			}else {
				if( is_array( $value ) ) {
					$array[$key] = self::sanitize_array( $value );
				}else {
					$array[$key] =$value == 'true' ? true : ( $value == 'false' ? false : sanitize_text_field( $value ) );
				}
			}
		}
		return $array;
	}

	static function filterNaN( $array ) {
		return array_filter( $array, function( $id ) {
			return $id && is_numeric( $id );
		});
	}

	static function wordCount( $content ) {
		return $content ? count( preg_split( 
			'/[\s]+/',
			preg_replace( '/(<([^>]+)>)/i', '', $content )
		) ) : 0;
	}

	static function applyContentFilter( $rawContent ){
		// remove script and style tag
		// $rawContent = preg_replace( '/<script\b[^>]*>(.*?)<\/script>|<style\b[^>]*>(.*?)<\/style>/is', '', $rawContent );

		$textAllowedHTML = [ 'a' => [ 'href' => [], 'title' => [] ], 'br' => [], 'em' => [], 'strong' => [] ];
		$innerAllowedHTML = array_merge( [ 'span' => [ $textAllowedHTML ] ], $textAllowedHTML );
		$allowedHTML = array_merge( [ 'p' => [ $innerAllowedHTML ] ], $innerAllowedHTML );
		$content = wp_kses( $rawContent, $allowedHTML );
		$plainText = trim( wp_strip_all_tags( $content ?? '' ) );

		return apbIsPremium() ? apply_filters( 'apb_excerpt_filter', $plainText, $content ) : $plainText;
	}

	static function arrangedPosts ( $posts, $postType, $fImgSize = 'full', $metaDateFormat = 'M j, Y', $isExcerptFromContent = false, $excerptLength = 25 ) {
		$arranged = [];

		$excerptLength = (int)$excerptLength;

		$taxOfPostType = array_diff( get_object_taxonomies( $postType ), array( 'post_format', 'category' ) );

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

					$links .= "<a href='$link' rel='$slug'>$t->name</a>";
				};
				$taxonomies[$slug] = $links;
			}

			$contentOrExcerptArr = $isExcerptFromContent ? [ 'content' => $excerptLength > -1 ? wp_trim_words( self::applyContentFilter( $post->post_content ), $excerptLength, '' ) : self::applyContentFilter( $post->post_content ) ] : [ 'excerpt' => self::applyContentFilter( $post->post_excerpt ) ];

			$arranged[] = array_merge( [
				'id' => $id,
				'link' => get_permalink( $post ),
				'name' => $post->post_name,
				'thumbnail' => $thumbnail,
				'title' => $post->post_title,
				'author' => [
					'name' => get_the_author_meta( 'display_name', $post->post_author ),
					'link' => get_author_posts_url( $post->post_author )
				],
				'date' => $post->post_date,
				'date' => get_the_date( $metaDateFormat, $id ),
				'dateGMT' => $post->post_date_gmt,
				'modifiedDate' => $post->post_modified,
				'modifiedDateGMT' => $post->post_modified_gmt,
				'commentCount' => $post->comment_count,
				'commentStatus' => $post->comment_status,
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
}