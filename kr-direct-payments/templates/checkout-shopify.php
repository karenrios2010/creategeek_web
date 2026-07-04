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
		<span class="krsc-header-spacer" aria-hidden="true"></span>
		<a class="krsc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php
			if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
				the_custom_logo();
			} else {
				echo '<span class="krsc-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</a>
		<span class="krsc-header-actions">
			<?php if ( ! is_user_logged_in() && function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="krsc-signin" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Iniciar sesion', 'kr-direct-payments' ); ?></a>
			<?php endif; ?>
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="krsc-bag" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Volver al carrito', 'kr-direct-payments' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1.2 12.2a1.8 1.8 0 0 1-1.8 1.6H9a1.8 1.8 0 0 1-1.8-1.6Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
				</a>
			<?php endif; ?>
		</span>
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
