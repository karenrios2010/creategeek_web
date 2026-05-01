<?php
/**
 * WooCommerce – Precio personalizado en productos agrupados
 * Pega este código en Code Snippets o en functions.php de tu tema hijo.
 */

defined( 'ABSPATH' ) || exit;

define( 'WGCP_META_PREFIX', '_gcprice_' );

// ── ADMIN: campos de precio por hijo en el editor de producto agrupado ──────

add_action( 'woocommerce_product_options_grouped_product_options', function () {
    global $post;
    $product = wc_get_product( $post->ID );
    if ( ! $product || ! $product->is_type( 'grouped' ) ) return;

    $children = $product->get_children();
    if ( empty( $children ) ) return;

    echo '<div class="options_group" style="border-top:1px solid #eee;">';
    echo '<h4 style="padding:10px 12px;margin:0;background:#f8f8f8;">'
        . esc_html__( 'Precios personalizados por producto hijo', 'woocommerce' ) . '</h4>';
    echo '<p class="description" style="padding:4px 12px 8px;">'
        . esc_html__( 'Deja vacío para usar el precio regular del hijo.', 'woocommerce' ) . '</p>';

    foreach ( $children as $child_id ) {
        $child = wc_get_product( $child_id );
        if ( ! $child ) continue;

        woocommerce_wp_text_input( [
            'id'          => 'wgcp_price_' . $child_id,
            'name'        => 'wgcp_prices[' . $child_id . ']',
            'label'       => sprintf( '"%s" (ID %s)', $child->get_name(), $child_id ),
            'placeholder' => $child->get_price() ?: '0.00',
            'value'       => get_post_meta( $post->ID, WGCP_META_PREFIX . $child_id, true ),
            'type'        => 'number',
            'custom_attributes' => [ 'step' => 'any', 'min' => '0' ],
            'desc_tip'    => true,
            'description' => 'Precio actual: ' . wc_price( $child->get_price() ),
        ] );
    }

    echo '</div>';
} );

// ── ADMIN: guardar precios al publicar/actualizar el producto ────────────────

add_action( 'woocommerce_process_product_meta_grouped', function ( $post_id ) {
    if ( empty( $_POST['wgcp_prices'] ) || ! is_array( $_POST['wgcp_prices'] ) ) return;

    $children = wc_get_product( $post_id )->get_children();

    foreach ( $_POST['wgcp_prices'] as $child_id => $price ) {
        $child_id = absint( $child_id );
        if ( ! in_array( $child_id, $children, true ) ) continue; // seguridad

        $meta_key = WGCP_META_PREFIX . $child_id;
        '' === $price
            ? delete_post_meta( $post_id, $meta_key )
            : update_post_meta( $post_id, $meta_key, wc_format_decimal( sanitize_text_field( $price ) ) );
    }
} );

// ── FRONTEND: mostrar precio personalizado en la tabla del agrupado ──────────

add_filter( 'woocommerce_grouped_product_list_column_price', function ( $price_html, $child ) {
    if ( ! is_product() ) return $price_html;
    $parent = wc_get_product( get_the_ID() );
    if ( ! $parent || ! $parent->is_type( 'grouped' ) ) return $price_html;

    $custom = get_post_meta( $parent->get_id(), WGCP_META_PREFIX . $child->get_id(), true );
    return ( '' !== $custom && false !== $custom )
        ? '<span style="font-weight:600;color:#2271b1;">' . wc_price( $custom ) . '</span>'
        : $price_html;
}, 10, 2 );

// ── CARRITO: inyectar precio personalizado al añadir desde el agrupado ───────

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
    $referer_id = url_to_postid( wp_get_referer() );
    if ( ! $referer_id ) return $data;

    $parent = wc_get_product( $referer_id );
    if ( ! $parent || ! $parent->is_type( 'grouped' ) ) return $data;

    $custom = get_post_meta( $referer_id, WGCP_META_PREFIX . $product_id, true );
    if ( '' !== $custom && false !== $custom ) {
        $data['wgcp_price']  = wc_format_decimal( $custom );
        $data['wgcp_parent'] = $referer_id;
        $data['unique_key']  = md5( microtime() . rand() ); // evita fusión de ítems
    }
    return $data;
}, 10, 2 );

// ── CARRITO: aplicar el precio antes de calcular totales ────────────────────

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    foreach ( $cart->get_cart() as $item ) {
        if ( isset( $item['wgcp_price'] ) ) {
            $item['data']->set_price( $item['wgcp_price'] );
        }
    }
}, 20 );

// ── PEDIDO: guardar el precio personalizado en los metadatos del pedido ──────

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
    if ( isset( $values['wgcp_price'] ) ) {
        $item->add_meta_data( 'Precio agrupado personalizado', wc_price( $values['wgcp_price'] ), true );
    }
}, 10, 4 );
