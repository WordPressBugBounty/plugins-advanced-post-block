<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

require_once APB_DIR_PATH . 'includes/Functions.php';

/**
 * Posts class
 * Handles post querying, formatting, and rendering of placeholders.
 * 
 * @package APB
 */
class Posts{
	/**
	 * Constructor.
	 * Registers excerpt filters.
	 */
	public function __construct(){
		add_filter( 'apb_excerpt_filter', function( $plainText, $htmlContent ){
			return $htmlContent;
		}, 10, 2 );
	}

	/**
	 * Builds WP_Query arguments based on block attributes.
	 *
	 * @param array $queryAttr Block attributes.
	 * @return array WP_Query arguments.
	 */
	public static function arrangeQuery( $queryAttr ){
		$taxonomyRelation = $queryAttr['taxonomyRelation'] ?? 'AND';
		$selectedCategories = $queryAttr['selectedCategories'] ?? [];
		$postType = $queryAttr['postType'] ?? 'post';
		$isPostsPerPageAll = $queryAttr['isPostsPerPageAll'] ?? false;
		$postsPerPage = $queryAttr['postsPerPage'] ?? 12;
		$postsOrderBy = $queryAttr['postsOrderBy'] ?? 'date';
		$postsOrder = $queryAttr['postsOrder'] ?? 'desc';
		$currentPostId = $queryAttr['currentPostId'] ?? 0;

		if ( ! post_type_exists( $postType ) || ! is_post_type_viewable( $postType ) ) {
			$postType = 'post';
		}

		$termsQuery = ['relation' => $taxonomyRelation];

		if ( 'post' === $postType && count( $selectedCategories ) ) {
			$termsQuery[] = [
				'taxonomy'	=> 'category',
				'field'		=> 'term_id',
				'terms'		=> $selectedCategories,
			];
		}

		$postsAuthors = Functions::filterNaN( $queryAttr['postsAuthors'] ?? [] );
		$author__in = !empty( $postsAuthors ) ? [ 'author__in' => $postsAuthors ] : [];

		$query = array_merge( [
			'post_type'			=> $postType,
			'posts_per_page'	=> $isPostsPerPageAll ? -1 : (int) $postsPerPage,
			'orderby'			=> $postsOrderBy,
			'order'				=> $postsOrder,
			'tax_query'			=> count( $termsQuery ) > 1 ? $termsQuery : [],
			'has_password'		=> false,
			'post_status'		=> 'publish'
		], $author__in );

		// Query Presets are available in the premium version.

		return $query;
	}

	/**
	 * Fetches posts based on attributes and page number.
	 *
	 * @param array $queryAttr Block attributes.
	 * @param int $pageNumber Current page number.
	 * @return array Arranged posts data.
	 */
	public static function getPosts( $queryAttr, $pageNumber = 1 ){
		$isPostsPerPageAll = $queryAttr['isPostsPerPageAll'] ?? false;
		$postsPerPage = $queryAttr['postsPerPage'] ?? 12;
		$postType = $queryAttr['postType'] ?? 'post';
		$excerptLength = $queryAttr['excerptLength'] ?? 25;
		$isExcerptFromContent = $queryAttr['isExcerptFromContent'] ?? false;

		$queryAttr['isPostsPerPageAll'] = true === $isPostsPerPageAll || 'true' === $isPostsPerPageAll;

		// Ensure numeric values to avoid PHP type errors and handle "all" mode
		$postsPerPage	= (int) $postsPerPage;
		$pageNumber		= (int) $pageNumber;

		// Extract excerptFrom with fallback to legacy isExcerptFromContent
		$excerptFrom = $queryAttr['excerptFrom'] ?? ( isset( $queryAttr['excerpt']['from'] ) ? $queryAttr['excerpt']['from'] : 'excerpt' );
		$excerptFrom = ( 'content' === $excerptFrom || $isExcerptFromContent ) ? 'content' : 'excerpt';

		$offset = ( $postsPerPage * max( 0, $pageNumber - 1 ) );

		$newArgs = wp_parse_args( [ 'offset' => $offset ], self::arrangeQuery( $queryAttr ) );
		$posts = Functions::arrangedPosts(
			get_posts( $newArgs ),
			$postType,
			$excerptFrom,
			$excerptLength ?? 25
		);

		return $posts;
	}

	/**
	 * Generates a skeleton article HTML for loading state.
	 *
	 * @param string $prefix CSS class prefix.
	 * @return string Skeleton article HTML.
	 */
	public static function skeletonArticle( $prefix ){
		$articleEl = "<article class='". $prefix ."Post' aria-hidden='true'>
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

		return wp_kses( $articleEl, [ 'article' => [ 'class' => [], 'aria-hidden' => [] ], 'div' => [ 'class' => [] ], 'span' => [ 'class' => [] ] ] );
	}

	/**
	 * Generates a loading placeholder HTML based on layout and attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @param string $prefix CSS class prefix.
	 * @return string Loading placeholder HTML.
	 */
	public static function loadingPlaceholder( $attributes, $prefix ){
		$countQuery = new \WP_Query( Posts::arrangeQuery( $attributes ?? [] ) );
		$postCount = $countQuery->found_posts;

		$layout = $attributes['layout'] ?? '';
		$columns = $attributes['columns'] ?? [ 'desktop' => 3, 'tablet' => 2, 'mobile' => 1 ];
		$sliderHeight = $attributes['sliderHeight'] ?? '350px';
		$sliderIsPage = $attributes['sliderIsPage'] ?? true;
		$sliderIsPrevNext = $attributes['sliderIsPrevNext'] ?? true;
		$tickerVisible = $attributes['tickerVisible'] ?? 3;

		$colD = $columns['desktop'];
		$colT = $columns['tablet'];
		$colM = $columns['mobile'];
		$gridClass = $prefix ."GridPosts columns-$colD columns-tablet-$colT columns-mobile-$colM";

		$placeholderId = wp_unique_id( $prefix .'LoadingPlaceholder-' );

		$sliderStyles = "#$placeholderId .$prefix" . "SliderSkeleton article{
			min-height: $sliderHeight;
		}";

		$loadingLabel = esc_html__( 'Loading posts…', 'advanced-post-block' );

		ob_start(); ?>
			<div class='<?php echo esc_attr( $prefix ); ?>LoadingPlaceholder' id='<?php echo esc_attr( $placeholderId ); ?>' role='status' aria-busy='true'>
				<span class='screen-reader-text'><?php echo esc_html( $loadingLabel ); ?></span>
				<?php switch ( $layout ) {
					case 'slider': ?>
						<style>
							<?php echo wp_strip_all_tags( $sliderStyles ); ?>
						</style>
						<div class='<?php echo esc_attr( $prefix ); ?>SliderSkeleton'>
							<div class='swiper-wrapper'>
								<?php foreach ( range( 1, 2 ) as $item ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self::skeletonArticle is properly escaped
								echo self::skeletonArticle( $prefix );
								} ?>
							</div>
							<?php if ( $sliderIsPage ) : ?>
								<div class='swiper-pagination'></div>
							<?php endif; ?>
							<?php if ( $sliderIsPrevNext ) : ?>
								<div class='swiper-button-prev'></div>
								<div class='swiper-button-next'></div>
							<?php endif; ?>
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
						$label = __('Trending Now', 'advanced-post-block');
						$theme = 'theme1';
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
							<?php foreach ( range( 1, $postCount ) as $item ) {
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