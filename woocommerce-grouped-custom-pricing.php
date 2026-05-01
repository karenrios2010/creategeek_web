<?php
/**
 * Plugin Name: WooCommerce Grouped Custom Pricing
 * Plugin URI:  https://creategeekagency.com
 * Description: Permite asignar un precio personalizado a cada producto hijo dentro de un producto agrupado de WooCommerce.
 * Version:     1.0.0
 * Author:      Create Geek Agency
 * Text Domain: wc-grouped-custom-pricing
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta key template: _gcprice_{child_product_id}
 * Se guarda en el producto agrupado (padre).
 */
define( 'WGCP_META_PREFIX', '_gcprice_' );

// ─────────────────────────────────────────────
// ADMIN: Pestaña de precios del producto agrupado
// ─────────────────────────────────────────────

/**
 * Muestra los campos de precio personalizado en la pestaña "Grouped" del editor de producto.
 * Se engancha tarde para que WooCommerce ya haya renderizado los productos hijos.
 */
add_action( 'woocommerce_product_options_grouped_product_options', 'wgcp_render_custom_price_fields' );
function wgcp_render_custom_price_fields() {
    global $post;

    $product = wc_get_product( $post->ID );
    if ( ! $product || ! $product->is_type( 'grouped' ) ) {
        return;
    }

    $children = $product->get_children();
    if ( empty( $children ) ) {
        return;
    }

    echo '<div class="wgcp-custom-prices options_group">';
    echo '<h4 style="padding:10px 12px;margin:0;background:#f8f8f8;border-top:1px solid #eee;">'
        . esc_html__( 'Precios personalizados por hijo', 'wc-grouped-custom-pricing' )
        . '</h4>';
    echo '<p class="description" style="padding:4px 12px 8px;">'
        . esc_html__( 'Deja vacío para usar el precio regular del producto hijo.', 'wc-grouped-custom-pricing' )
        . '</p>';

    foreach ( $children as $child_id ) {
        $child       = wc_get_product( $child_id );
        if ( ! $child ) {
            continue;
        }

        $meta_key    = WGCP_META_PREFIX . $child_id;
        $saved_price = get_post_meta( $post->ID, $meta_key, true );

        woocommerce_wp_text_input( [
            'id'          => 'wgcp_price_' . $child_id,
            'name'        => 'wgcp_prices[' . $child_id . ']',
            'label'       => sprintf(
                /* translators: %1$s product name, %2$s product ID */
                esc_html__( 'Precio para "%1$s" (ID %2$s)', 'wc-grouped-custom-pricing' ),
                $child->get_name(),
                $child_id
            ),
            'placeholder' => $child->get_price() ?: '0.00',
            'value'       => $saved_price,
            'type'        => 'number',
            'custom_attributes' => [
                'step' => 'any',
                'min'  => '0',
            ],
            'desc_tip'    => true,
            'description' => sprintf(
                esc_html__( 'Precio actual del producto: %s', 'wc-grouped-custom-pricing' ),
                wc_price( $child->get_price() )
            ),
        ] );
    }

    echo '</div>';
}

/**
 * Guarda los precios personalizados al guardar el producto agrupado.
 */
add_action( 'woocommerce_process_product_meta_grouped', 'wgcp_save_custom_prices' );
function wgcp_save_custom_prices( $post_id ) {
    if ( ! isset( $_POST['wgcp_prices'] ) || ! is_array( $_POST['wgcp_prices'] ) ) {
        return;
    }

    // Obtener los hijos actuales para validar los IDs recibidos.
    $product  = wc_get_product( $post_id );
    $children = $product ? $product->get_children() : [];

    foreach ( $_POST['wgcp_prices'] as $child_id => $price ) {
        $child_id = absint( $child_id );

        if ( ! in_array( $child_id, $children, true ) ) {
            continue; // Seguridad: ignorar IDs que no son hijos reales.
        }

        $meta_key = WGCP_META_PREFIX . $child_id;

        if ( '' === $price ) {
            delete_post_meta( $post_id, $meta_key );
        } else {
            $sanitized = wc_format_decimal( sanitize_text_field( $price ) );
            update_post_meta( $post_id, $meta_key, $sanitized );
        }
    }
}

// ─────────────────────────────────────────────
// FRONTEND: Mostrar precio personalizado en la tabla del producto agrupado
// ─────────────────────────────────────────────

/**
 * Filtra el precio que WooCommerce muestra para cada hijo dentro de la
 * tabla del producto agrupado. Sólo aplica cuando se está viendo ese padre.
 */
add_filter( 'woocommerce_grouped_product_list_column_price', 'wgcp_display_custom_price_in_table', 10, 2 );
function wgcp_display_custom_price_in_table( $price_html, $child_product ) {
    $grouped_id = wgcp_get_current_grouped_id();
    if ( ! $grouped_id ) {
        return $price_html;
    }

    $custom_price = get_post_meta( $grouped_id, WGCP_META_PREFIX . $child_product->get_id(), true );
    if ( '' === $custom_price || false === $custom_price ) {
        return $price_html;
    }

    return '<span class="wgcp-custom-price">' . wc_price( $custom_price ) . '</span>';
}

/**
 * Detecta el ID del producto agrupado que se está visualizando actualmente.
 */
function wgcp_get_current_grouped_id() {
    if ( is_product() ) {
        $product = wc_get_product( get_the_ID() );
        if ( $product && $product->is_type( 'grouped' ) ) {
            return $product->get_id();
        }
    }
    return 0;
}

// ─────────────────────────────────────────────
// CARRITO: Aplicar precio personalizado al añadir al carrito
// ─────────────────────────────────────────────

/**
 * Cuando se añade al carrito un hijo desde un producto agrupado,
 * guardamos el precio personalizado como datos del ítem.
 */
add_filter( 'woocommerce_add_cart_item_data', 'wgcp_inject_custom_price_into_cart_item', 10, 3 );
function wgcp_inject_custom_price_into_cart_item( $cart_item_data, $product_id, $variation_id ) {
    // El referer nos indica desde qué página se añadió al carrito.
    $referer_id = wgcp_get_referer_grouped_product_id();
    if ( ! $referer_id ) {
        return $cart_item_data;
    }

    $meta_key     = WGCP_META_PREFIX . $product_id;
    $custom_price = get_post_meta( $referer_id, $meta_key, true );

    if ( '' !== $custom_price && false !== $custom_price ) {
        $cart_item_data['wgcp_custom_price']  = wc_format_decimal( $custom_price );
        $cart_item_data['wgcp_grouped_source'] = $referer_id;
        $cart_item_data['unique_key']          = md5( microtime() . rand() ); // evita fusión de ítems.
    }

    return $cart_item_data;
}

/**
 * Aplica el precio personalizado al ítem del carrito después de ser cargado.
 */
add_action( 'woocommerce_before_calculate_totals', 'wgcp_apply_custom_price_in_cart', 20 );
function wgcp_apply_custom_price_in_cart( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['wgcp_custom_price'] ) ) {
            $cart_item['data']->set_price( $cart_item['wgcp_custom_price'] );
        }
    }
}

/**
 * Muestra el precio personalizado en la vista del carrito/checkout como metadato visible.
 */
add_filter( 'woocommerce_get_item_data', 'wgcp_display_custom_price_label_in_cart', 10, 2 );
function wgcp_display_custom_price_label_in_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['wgcp_grouped_source'] ) ) {
        $grouped = wc_get_product( $cart_item['wgcp_grouped_source'] );
        if ( $grouped ) {
            $item_data[] = [
                'key'   => esc_html__( 'Pack agrupado', 'wc-grouped-custom-pricing' ),
                'value' => esc_html( $grouped->get_name() ),
            ];
        }
    }
    return $item_data;
}

/**
 * Persiste el precio personalizado en los metadatos del pedido para que quede registrado.
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'wgcp_save_custom_price_in_order_meta', 10, 4 );
function wgcp_save_custom_price_in_order_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['wgcp_custom_price'] ) ) {
        $item->add_meta_data(
            esc_html__( 'Precio agrupado personalizado', 'wc-grouped-custom-pricing' ),
            wc_price( $values['wgcp_custom_price'] ),
            true
        );
    }
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

/**
 * Intenta obtener el ID del producto agrupado desde el HTTP Referer.
 */
function wgcp_get_referer_grouped_product_id() {
    $referer = wp_get_referer();
    if ( ! $referer ) {
        return 0;
    }

    $post_id = url_to_postid( $referer );
    if ( ! $post_id ) {
        return 0;
    }

    $product = wc_get_product( $post_id );
    if ( $product && $product->is_type( 'grouped' ) ) {
        return $product->get_id();
    }

    return 0;
}

// ─────────────────────────────────────────────
// ESTILOS ADMIN MÍNIMOS
// ─────────────────────────────────────────────

add_action( 'admin_head', 'wgcp_admin_inline_styles' );
function wgcp_admin_inline_styles() {
    $screen = get_current_screen();
    if ( ! $screen || 'product' !== $screen->id ) {
        return;
    }
    echo '<style>
        .wgcp-custom-prices { border-top: 1px solid #eee; margin-top: 12px; }
        .wgcp-custom-prices .form-field { padding: 6px 12px; }
        .wgcp-custom-price { font-weight: 600; color: #2271b1; }
    </style>';
}
