<?php
$prefix = 'apbAdvancedPosts';
$id = wp_unique_id( "$prefix-" );
$planClass = apbIsPremium() ? 'premium' : 'free';

extract( $attributes );

if( 'ticker' === $layout ){
	wp_enqueue_script( 'easyTicker' );
}

$allPosts = get_posts( array_merge( APB\Posts::query( $attributes ), [ 'posts_per_page' => -1 ] ) );

$skeletonAllowedTags = [
	'style'		=> [],
	'div'		=> [ 'class' => [], 'id' => [] ],
	'article'	=> [ 'class' => [] ],
	'span'		=> [ 'class' => [] ],
];
?>
<div
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is properly escaped ?>
	<?php echo get_block_wrapper_attributes( [ 'class' => "$planClass align$align" ] ); ?>
	id='<?php echo esc_attr( $id ); ?>'
	data-nonce='<?php echo esc_attr( wp_json_encode( wp_create_nonce( 'wp_rest' ) ) ); ?>'
	data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'
	data-extra='<?php echo esc_attr( wp_json_encode( [ 'totalPosts' => count( $allPosts ) ] ) ); ?>'
	data-pipecheck='<?php echo esc_attr( apbIsPremium() ); ?>'
>
	<pre id='firstPosts'>
		<?php echo esc_html( wp_json_encode( APB\Posts::getPosts( $attributes ) ) ) ?>
	</pre>

	<?php echo wp_kses( APB\Posts::loadingPlaceholder( $attributes, 'apb' ), $skeletonAllowedTags ); ?>
</div>