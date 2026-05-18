<?php
defined( 'ABSPATH' ) || exit;

/**
 * Lee los datos de cada instancia WCEV directamente de wp_options,
 * sin depender de que las clases de envío estén completamente inicializadas.
 * Indexado por "method_id:instance_id" para que el JS haga el match.
 */
function wcev_collect_rate_data() {
    global $wpdb;

    $data = [];

    // Leer todas las instancias wcev_ registradas en zonas de envío
    $rows = $wpdb->get_results(
        "SELECT method_id, instance_id
         FROM {$wpdb->prefix}woocommerce_shipping_zone_methods
         WHERE method_id LIKE 'wcev_%'",
        ARRAY_A
    );

    if ( empty( $rows ) ) {
        return $data;
    }

    foreach ( $rows as $row ) {
        $method_id   = $row['method_id'];
        $instance_id = (int) $row['instance_id'];
        $rate_key    = $method_id . ':' . $instance_id;

        // La clave de opción WooCommerce para instance settings
        $option_key = 'woocommerce_' . $method_id . '_' . $instance_id . '_settings';
        $settings   = get_option( $option_key, [] );

        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $data[ $rate_key ] = [
            'logo'                => isset( $settings['logo'] ) ? esc_url_raw( $settings['logo'] ) : '',
            'cobro_destino'       => isset( $settings['cobro_destino'] ) ? $settings['cobro_destino'] : 'no',
            'cobro_destino_label' => ! empty( $settings['cobro_destino_label'] )
                                        ? $settings['cobro_destino_label']
                                        : 'Cobro a destino',
        ];
    }

    return $data;
}
