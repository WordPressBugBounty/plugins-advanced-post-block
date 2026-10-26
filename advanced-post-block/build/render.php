<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

$prefix = 'apbAdvancedPosts';
$id = wp_unique_id( "$prefix-" );

// Icon attributes carry inline SVG. Clean them here so a Contributor can't
// store markup that runs when the frontend injects it. The JSON below is also
// hex-encoded so stored HTML entities can't decode back into tags.
if ( isset( $attributes['readMore'] ) && is_array( $attributes['readMore'] ) ) {
	$attributes['readMore']['icon'] = APB\Functions::sanitizeIcon( $attributes['readMore']['icon'] ?? '' );
}
if ( isset( $attributes['accordion'] ) && is_array( $attributes['accordion'] ) ) {
	$attributes['accordion']['indicatorIconUp'] = APB\Functions::sanitizeSvg( $attributes['accordion']['indicatorIconUp'] ?? '' );
	$attributes['accordion']['indicatorIconDown'] = APB\Functions::sanitizeSvg( $attributes['accordion']['indicatorIconDown'] ?? '' );
}

$layout = $attributes['layout'] ?? '';
$align = $attributes['align'] ?? 'wide';

if( 'ticker' === $layout ){
	wp_enqueue_script( 'easyTicker' );
}

$skeletonAllowedTags = [
	'style'		=> [],
	'div'		=> [ 'class' => [], 'id' => [], 'role' => [], 'style' => [], 'aria-busy' => [], 'aria-hidden' => [] ],
	'article'	=> [ 'class' => [], 'aria-hidden' => [] ],
	'span'		=> [ 'class' => [], 'style' => [] ]
];
?>
<div
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is properly escaped ?>
	<?php echo get_block_wrapper_attributes( [ 'class' => "align$align" ] ); ?>
	id='<?php echo esc_attr( $id ); ?>'
	data-nonce='<?php echo esc_attr( wp_json_encode( wp_create_nonce( 'wp_rest' ) ) ); ?>'
	data-attributes='<?php echo esc_attr( wp_json_encode( array_merge( $attributes ?? [], [ 'currentPostId' => get_the_ID() ] ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ); ?>'
>
	<?php echo wp_kses( APB\Posts::loadingPlaceholder( $attributes ?? [], 'apb' ), $skeletonAllowedTags ); ?>
</div>