<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

$prefix = 'apbAdvancedPosts';
$id = wp_unique_id( "$prefix-" );

$layout = $attributes['layout'] ?? '';
$align = $attributes['align'] ?? 'wide';

if( 'ticker' === $layout ){
	wp_enqueue_script( 'easyTicker' );
}

$skeletonAllowedTags = [
	'style'		=> [],
	'div'		=> [ 'class' => [], 'id' => [], 'role' => [], 'aria-busy' => [] ],
	'article'	=> [ 'class' => [], 'aria-hidden' => [] ],
	'span'		=> [ 'class' => [] ]
];
?>
<div
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is properly escaped ?>
	<?php echo get_block_wrapper_attributes( [ 'class' => "align$align" ] ); ?>
	id='<?php echo esc_attr( $id ); ?>'
	data-nonce='<?php echo esc_attr( wp_json_encode( wp_create_nonce( 'wp_rest' ) ) ); ?>'
	data-attributes='<?php echo esc_attr( wp_json_encode( array_merge( $attributes ?? [], [ 'currentPostId' => get_the_ID() ] ) ) ); ?>'
>
	<?php echo wp_kses( APB\Posts::loadingPlaceholder( $attributes ?? [], 'apb' ), $skeletonAllowedTags ); ?>
</div>