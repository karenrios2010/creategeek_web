<?php
/**
 * Full-screen Shopify-style checkout template.
 *
 * Renders the native [woocommerce_checkout] shortcode inside a clean shell
 * (no theme header/footer), so the theme/Elementor layout never interferes
 * on this screen.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'krsc-body' ); ?>>
<?php wp_body_open(); ?>
<div class="krsc-page">

	<header class="krsc-header">
		<a class="krsc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php
			if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
				the_custom_logo();
			} else {
				echo '<span class="krsc-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</a>
	</header>

	<main class="krsc-main">
		<?php echo do_shortcode( '[woocommerce_checkout]' ); ?>
	</main>

	<footer class="krsc-footer">
		<?php
		$krsc_privacy = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
		if ( $krsc_privacy ) {
			echo '<a href="' . esc_url( $krsc_privacy ) . '">' . esc_html__( 'Politica de privacidad', 'kr-direct-payments' ) . '</a>';
		}
		?>
	</footer>

</div>
<?php wp_footer(); ?>
</body>
</html>
