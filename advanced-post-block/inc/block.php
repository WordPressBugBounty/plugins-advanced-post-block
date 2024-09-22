<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class APBBlock extends APBPlugin{
    function __construct(){
		add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
		add_action( 'wp_loaded', [$this, 'onLoaded'] );
	}

    function enqueueBlockAssets(){
		wp_register_script( 'easyTicker', APB_DIR_URL . 'assets/js/easy-ticker.min.js', [ 'jquery' ], '3.2.1', true );
		wp_set_script_translations( 'easyTicker', 'advanced-post-block', APB_DIR_PATH . 'languages' );
	}

    function onLoaded(){
		wp_register_style( 'ap-block-posts-style', APB_DIR_URL . 'dist/style.css', [ 'dashicons' ], APB_VERSION );

		wp_register_style( 'ap-block-posts-editor-style', APB_DIR_URL . 'dist/editor.css', [ 'ap-block-posts-style' ], APB_VERSION );

		register_block_type( __DIR__, [
			'editor_style'		=> 'ap-block-posts-editor-style',
			'render_callback'	=> [$this, 'render']
		] ); // Register Block

		wp_set_script_translations( 'ap-block-posts-editor-script', 'advanced-post-block', APB_DIR_PATH . 'languages' );

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); // Disable emoji load as image
	} // Register

	function render( $attributes ) {
		extract( $attributes );

		wp_enqueue_style( 'ap-block-posts-style' );
		if( 'ticker' === $layout ){
			wp_enqueue_script( 'easyTicker' );
		}
		wp_enqueue_script( 'ap-block-posts-script', APB_DIR_URL . 'dist/script.js', [ 'wp-api', 'wp-util', 'react', 'react-dom' ], APB_VERSION, true );
		wp_set_script_translations( 'ap-block-posts-script', 'advanced-post-block', APB_DIR_PATH . 'languages' );

		$className = $className ?? '';
		$extraClass = apbIsPremium() ? 'premium' : 'free';
		$blockClassName = "wp-block-ap-block-posts $extraClass $className align$align";

		$allPosts = get_posts( array_merge( $this->query( $attributes ), [ 'posts_per_page' => -1 ] ) );

		$skeletonAllowedTags = [
			'style'		=> [],
			'div'		=> [ 'class' => [], 'id' => [] ],
			'article'	=> [ 'class' => [] ],
			'span'		=> [ 'class' => [] ],
		];

		$id = wp_unique_id( 'apbAdvancedPosts-' );

		ob_start(); ?>
		<div
			class='<?php echo esc_attr( $blockClassName ); ?>'
			id='<?php echo esc_attr( $id ); ?>'
			data-nonce='<?php echo esc_attr( wp_json_encode( wp_create_nonce( 'wp_ajax' ) ) ); ?>'
			data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'
			data-extra='<?php echo esc_attr( wp_json_encode( [ 'totalPosts' => count( $allPosts ) ] ) ); ?>'
		>
			<pre id='firstPosts' style='display: none;'>
				<?php echo esc_html( wp_json_encode( $this->getPosts( $attributes ) ) ) ?>
			</pre>

			<?php echo wp_kses( $this->loadingPlaceholder( $attributes ), $skeletonAllowedTags ); ?>
		</div>
		<?php return ob_get_clean();
	} // Render

	function skeletonArticle(){
		ob_start(); ?>
			<article class='apbPost'>
				<span class='apbLoadingItem apbThumb'></span>
				
				<div class='apbText'>
					<div class='apbTitle'>
						<span class='apbLoadingItem'></span>
						<span class='apbLoadingItem'></span>
					</div>
					<div class='apbMeta'>
						<span class='apbLoadingItem'></span>
					</div>
					<div class='apbExcerpt'>
						<span class='apbLoadingItem'></span>
						<span class='apbLoadingItem'></span>
						<span class='apbLoadingItem'></span>
						<span class='apbLoadingItem'></span>
					</div>
					<div class='apbReadMore'>
						<span class='apbLoadingItem'></span>
					</div>
				</div>
			</article>
		<?php return ob_get_clean();
	}

	function loadingPlaceholder( $attributes ){
		extract( $attributes );
		$posts = $this->getPosts( $attributes );

		$colD = $columns['desktop'];
		$colT = $columns['tablet'];
		$colM = $columns['mobile'];
		$gridClass = "apbGridPosts columns-$colD columns-tablet-$colT columns-mobile-$colM";

		$placeholderId = wp_unique_id( 'apbLoadingPlaceholder-' );

		$sliderStyles = "#$placeholderId .apbSliderSkeleton article{
			min-height: $sliderHeight;
		}";

		ob_start(); ?>
			<div class='apbLoadingPlaceholder' id='<?php echo esc_attr( $placeholderId ); ?>'>
				<?php switch ( $layout ) {
					case 'grid1': ?>
						<div class='apbGrid1Posts'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								echo $this->skeletonArticle();
							} ?>
						</div>
					<?php break;
					case 'slider': ?>
						<style>
							<?php echo esc_html( $sliderStyles ); ?>
						</style>
						<div class='apbSliderSkeleton'>
							<div class='swiper-wrapper'>
								<?php foreach ( range( 1, 2 ) as $item ) {
									echo $this->skeletonArticle();
								} ?>
							</div>
							<?php echo $sliderIsPage ? "<div class='swiper-pagination'></div>" : ''; ?>
							<?php echo $sliderIsPrevNext ? "<div class='swiper-button-prev'></div><div class='swiper-button-next'></div>" : ''; ?>
						</div>
					<?php break;
					case 'ticker': ?>
						<div class='apbTickerPosts'>
							<?php foreach ( range( 1, $tickerVisible ) as $item ) {
								echo $this->skeletonArticle();
							} ?>
						</div>
					<?php break;
					default: ?>
						<div class='<?php echo esc_attr( $gridClass ); ?>'>
							<?php foreach ( range( 1, count( $posts ) ) as $item ) {
								echo $this->skeletonArticle();
							} ?>
						</div>
					<?php break;
				} ?>
			</div>
		<?php return ob_get_clean();
	}
}
new APBBlock;