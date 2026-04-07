<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

require_once APB_DIR_PATH . 'includes/Functions.php';

class Posts{
	public function __construct(){
		add_filter( 'apb_excerpt_filter', function( $plainText, $htmlContent ){
			return $htmlContent;
		}, 10, 3 );
	}

	static function query( $attributes ){
		extract( $attributes );
		$taxonomyRelation = $taxonomyRelation ?? 'AND';

		$selectedCategories = $selectedCategories ?? [];
		$selectedTags = $selectedTags ?? [];
		$selectedTaxonomies = $selectedTaxonomies ?? [];

		$termsQuery = ['relation' => $taxonomyRelation];

		if ( 'post' === $postType && count( $selectedCategories ) ) {
			$termsQuery[] = [
				'taxonomy'	=> 'category',
				'field'		=> 'term_id',
				'terms'		=> $selectedCategories,
			];
		}

		if ( 'post' === $postType && count( $selectedTags ) ) {
			$termsQuery[] = [
				'taxonomy'	=> 'post_tag',
				'field'		=> 'term_id',
				'terms'		=> $selectedTags,
			];
		}

		foreach ( $selectedTaxonomies as $taxonomy => $terms ){
			if( count( $terms ) ){
				$termsQuery[] = [
					'taxonomy'	=> $taxonomy,
					'field'		=> 'term_id',
					'terms'		=> $terms,
				];
			}
		}

		$postsInclude = Functions::filterNaN( $postsInclude ?? [] );
		$post__in = !empty( $postsInclude ) ? [ 'post__in' => $postsInclude ] : [];
		$post__not_in = Functions::filterNaN( $postsExclude ?? [] );
		$postsAuthors = Functions::filterNaN( $postsAuthors ?? [] );
		$author__in = !empty( $postsAuthors ) ? [ 'author__in' => $postsAuthors ] : [];

		$currentPostId = get_the_ID() ?: ( $currentPostId ?? 0 );
		if ( $isExcludeCurrent && $currentPostId ) {
			$post__not_in[] = $currentPostId;
		}

		if ( !empty( $isExcludeSticky ) ) {
			$stickyPosts = get_option( 'sticky_posts' );
			if ( !empty( $stickyPosts ) ) {
				$post__not_in = array_merge( $post__not_in, $stickyPosts );
			}
		}

		$query = array_merge( [
			'post_type'			=> $postType,
			'posts_per_page'	=> $isPostsPerPageAll ? -1 : (int) $postsPerPage,
			'orderby'			=> $postsOrderBy,
			'order'				=> $postsOrder,
			's'					=> $postsSearch ?? '',
			'tax_query'			=> count( $termsQuery ) > 1 ? $termsQuery : [],
			'offset'			=> $isPostsPerPageAll ? 0 : (int) $postsOffset,
			'post__not_in'		=> array_unique( $post__not_in ),
			'has_password'		=> false,
			'post_status'		=> 'publish'
		], $post__in, $author__in );

		if( apbIsPremium() ) {
			if ( ! empty( $queryPreset ) ) {
				switch ( $queryPreset ) {
					case 'popular':
						$query['meta_key']	= 'apb_post_views_count';
						$query['orderby']		= 'meta_value_num';
						$query['order']			= 'DESC';
						break;
					case 'popular-1-day':
						$query['meta_key']		= 'apb_post_views_count';
						$query['orderby']		= 'meta_value_num';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 day ago' ] ];
						break;
					case 'popular-7-days':
						$query['meta_key']		= 'apb_post_views_count';
						$query['orderby']		= 'meta_value_num';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 week ago' ] ];
						break;
					case 'popular-30-days':
						$query['meta_key']		= 'apb_post_views_count';
						$query['orderby']		= 'meta_value_num';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 month ago' ] ];
						break;
					case 'random':
						$query['orderby']		= 'rand';
						break;
					case 'random-7-days':
						$query['orderby']		= 'rand';
						$query['date_query']	= [ [ 'after' => '1 week ago' ] ];
						break;
					case 'random-30-days':
						$query['orderby']		= 'rand';
						$query['date_query']	= [ [ 'after' => '1 month ago' ] ];
						break;
					case 'latest-published':
						$query['orderby']		= 'date';
						$query['order']			= 'DESC';
						break;
					case 'latest-modified':
						$query['orderby']		= 'modified';
						$query['order']			= 'DESC';
						break;
					case 'oldest-published':
						$query['orderby']		= 'date';
						$query['order']			= 'ASC';
						break;
					case 'oldest-modified':
						$query['orderby']		= 'modified';
						$query['order']			= 'ASC';
						break;
					case 'alphabet-asc':
						$query['orderby']		= 'title';
						$query['order']			= 'ASC';
						break;
					case 'alphabet-desc':
						$query['orderby']		= 'title';
						$query['order']			= 'DESC';
						break;
					case 'sticky-posts':
						$query['post__in']		= get_option( 'sticky_posts' );
						$query['ignore_sticky_posts'] = 1;
						break;
					case 'most-comment':
						$query['orderby']		= 'comment_count';
						$query['order']			= 'DESC';
						break;
					case 'most-comment-1-day':
						$query['orderby']		= 'comment_count';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 day ago' ] ];
						break;
					case 'most-comment-7-days':
						$query['orderby']		= 'comment_count';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 week ago' ] ];
						break;
					case 'most-comment-30-days':
						$query['orderby']		= 'comment_count';
						$query['order']			= 'DESC';
						$query['date_query']	= [ [ 'after' => '1 month ago' ] ];
						break;
					case 'related-category':
						if ( $currentPostId ) {
							$query['post__not_in'][] = $currentPostId;
							$categories = wp_get_post_terms( $currentPostId, 'category', [ 'fields' => 'ids' ] );
							if ( ! empty( $categories ) ) {
								$rel_query = [
									'taxonomy'	=> 'category',
									'field'		=> 'term_id',
									'terms'		=> $categories,
								];
								$query['tax_query'] = empty( $query['tax_query'] ) ? [ $rel_query ] : array_merge( $query['tax_query'], [ $rel_query ] );
							} else {
								$query['post__in'] = [0]; // Force no results if no categories found
							}
						}
						break;
					case 'related-tag':
						if ( $currentPostId ) {
							$query['post__not_in'][] = $currentPostId;
							$tags = wp_get_post_terms( $currentPostId, 'post_tag', [ 'fields' => 'ids' ] );
							if ( ! empty( $tags ) ) {
								$rel_query = [
									'taxonomy'	=> 'post_tag',
									'field'		=> 'term_id',
									'terms'		=> $tags,
								];
								$query['tax_query'] = empty( $query['tax_query'] ) ? [ $rel_query ] : array_merge( $query['tax_query'], [ $rel_query ] );
							} else {
								$query['post__in'] = [0];
							}
						}
						break;
					case 'related-category-tag':
						if ( $currentPostId ) {
							$query['post__not_in'][] = $currentPostId;
							$tags = wp_get_post_terms( $currentPostId, 'post_tag', [ 'fields' => 'ids' ] );
							$categories = wp_get_post_terms( $currentPostId, 'category', [ 'fields' => 'ids' ] );
							$tax_query = [ 'relation' => 'OR' ];
							if ( ! empty( $tags ) ) {
								$tax_query[] = [ 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $tags ];
							}
							if ( ! empty( $categories ) ) {
								$tax_query[] = [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $categories ];
							}
							if ( count( $tax_query ) > 1 ) {
								$query['tax_query'] = empty( $query['tax_query'] ) ? [ $tax_query ] : array_merge( $query['tax_query'], [ $tax_query ] );
							} else {
								$query['post__in'] = [0];
							}
						}
						break;
					case 'related-posts':
						if ( $currentPostId ) {
							$query['post__not_in'][] = $currentPostId;
							$tax_query = [ 'relation' => 'OR' ];

							$post_type	= get_post_type( $currentPostId );
							$taxonomies	= get_object_taxonomies( $post_type );

							if ( ! empty( $taxonomies ) ) {
								foreach ( $taxonomies as $taxonomy ) {
									$terms = wp_get_post_terms( $currentPostId, $taxonomy, [ 'fields' => 'ids' ] );
									if ( ! empty( $terms ) ) {
										$tax_query[] = [
											'taxonomy'	=> $taxonomy,
											'field'		=> 'term_id',
											'terms'		=> $terms,
										];
									}
								}
							}

							if ( count( $tax_query ) > 1 ) {
								$query['tax_query'] = empty( $query['tax_query'] ) ? [ $tax_query ] : array_merge( $query['tax_query'], [ $tax_query ] );
							} else {
								$query['post__in'] = [0];
							}
						}
						break;
				}
			}

			$query = apply_filters( 'apb_query', $query );
		}

		return $query;
	}

	static function getPosts( $attributes, $pageNumber = 1 ){
		extract( $attributes );

		$attributes['isPostsPerPageAll'] = 'true' == $isPostsPerPageAll || 1 == $isPostsPerPageAll;
		$attributes['isExcludeCurrent'] = $isExcludeCurrent == 'true' || 1 == $isExcludeCurrent;

		// Ensure numeric values to avoid PHP type errors and handle "all" mode
		$postsPerPage	= isset( $postsPerPage ) ? (int) $postsPerPage : 0;
		$pageNumber		= (int) $pageNumber;
		$postsOffset	= isset( $postsOffset ) ? (int) $postsOffset : 0;

		$offset = ( $postsPerPage * max( 0, $pageNumber - 1 ) ) + $postsOffset;

		$newArgs = wp_parse_args( [ 'offset' => $offset ], self::query( $attributes ) );
		$posts = Functions::arrangedPosts(
			get_posts( $newArgs ),
			$postType,
			$fImgSize,
			$metaDateFormat,
			$isExcerptFromContent || 'true' === $isExcerptFromContent,
			$excerptLength
		);

		return $posts;
	}

	static function skeletonArticle( $prefix ){
		$articleEl = "<article class='". $prefix ."Post'>
			<span class='". $prefix ."LoadingItem ". $prefix ."Thumb'></span>
			
			<div class='". $prefix ."Text'>
				<div class='". $prefix ."Title'>
					<span class='". $prefix ."LoadingItem'></span>
					<span class='". $prefix ."LoadingItem'></span>
				</div>
				<div class='". $prefix ."Meta'>
					<span class='". $prefix ."LoadingItem'></span>
				</div>
				<div class='". $prefix ."Excerpt'>
					<span class='". $prefix ."LoadingItem'></span>
					<span class='". $prefix ."LoadingItem'></span>
					<span class='". $prefix ."LoadingItem'></span>
					<span class='". $prefix ."LoadingItem'></span>
				</div>
				<div class='". $prefix ."ReadMore'>
					<span class='". $prefix ."LoadingItem'></span>
				</div>
			</div>
		</article>";

		ob_start();
			echo wp_kses( $articleEl, [ 'article' => [ 'class' => [] ], 'div' => [ 'class' => [] ], 'span' => [ 'class' => [] ] ] );
		return ob_get_clean();
	}

	static function loadingPlaceholder( $attributes, $prefix ){
		extract( $attributes );
		$posts = self::getPosts( $attributes );

		$colD = $columns['desktop'];
		$colT = $columns['tablet'];
		$colM = $columns['mobile'];
		$gridClass = $prefix ."GridPosts columns-$colD columns-tablet-$colT columns-mobile-$colM";

		$placeholderId = wp_unique_id( $prefix .'LoadingPlaceholder-' );

		$sliderStyles = "#$placeholderId .$prefix" . "SliderSkeleton article{
			min-height: $sliderHeight;
		}";

		ob_start(); ?>
			<div class='<?php echo esc_attr( $prefix ); ?>LoadingPlaceholder' id='<?php echo esc_attr( $placeholderId ); ?>'>
				<?php switch ( $layout ) {
					case 'grid1': ?>
						<div class='<?php echo esc_attr( $prefix ); ?>Grid1Posts'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
							} ?>
						</div>
					<?php break;
					case 'magazine1': ?>
						<div class='<?php echo esc_attr( $prefix ); ?>Magazine1Posts'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
							} ?>
						</div>
					<?php break;
					case 'magazine2': ?>
						<div class='<?php echo esc_attr( $prefix ); ?>Magazine2Posts'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
							} ?>
						</div>
					<?php break;
					case 'slider': ?>
						<style>
							<?php echo esc_html( $sliderStyles ); ?>
						</style>
						<div class='<?php echo esc_attr( $prefix ); ?>SliderSkeleton'>
							<div class='swiper-wrapper'>
								<?php foreach ( range( 1, 2 ) as $item ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
								} ?>
							</div>
							<?php echo $sliderIsPage ? "<div class='swiper-pagination'></div>" : ''; ?>
							<?php echo $sliderIsPrevNext ? "<div class='swiper-button-prev'></div><div class='swiper-button-next'></div>" : ''; ?>
						</div>
					<?php break;
					case 'ticker': ?>
						<div class='<?php echo esc_attr( $prefix ); ?>TickerPosts'>
							<?php foreach ( range( 1, $tickerVisible ) as $item ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
							} ?>
						</div>
					<?php break;
					case 'newsTicker': 
						$label = ! empty( $newsTicker['label'] ) ? $newsTicker['label'] : 'Trending Now';
						$theme = ! empty( $newsTicker['theme'] ) ? $newsTicker['theme'] : 'theme1';
					?>
						<div class='<?php echo esc_attr( $prefix ); ?>NewsTicker <?php echo esc_attr( $theme ); ?> newsTickerSkeleton'>
							<div class='newsTickerLabel'>
								<span><?php echo esc_html( $label ); ?></span>
							</div>
							<div class='newsTickerPostsWrapper'>
								<span class='<?php echo esc_attr( $prefix ); ?>LoadingItem newsTickerSkeletonBar'></span>
							</div>
						</div>
					<?php break;
					default: ?>
						<div class='<?php echo esc_attr( $gridClass ); ?>'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
							} ?>
						</div>
					<?php break;
				} ?>
			</div>
		<?php return ob_get_clean();
	}
}