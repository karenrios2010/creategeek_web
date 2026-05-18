<?php
defined( 'ABSPATH' ) || exit;

/**
 * Recopila datos de todas las instancias de métodos WCEV en zonas de envío.
 * Resultado indexado por "method_id:instance_id" para que el JS haga el match.
 */
function wcev_collect_rate_data() {
    $data = [];

    try {
        $all_zones   = WC_Shipping_Zones::get_zones();
        $all_zones[] = [ 'zone_id' => 0 ]; // Zona "Resto del mundo"

        foreach ( $all_zones as $zone_data ) {
            $zone = new WC_Shipping_Zone( $zone_data['zone_id'] );

            foreach ( $zone->get_shipping_methods( false ) as $method ) {
                if ( strpos( $method->id, 'wcev_' ) !== 0 ) {
                    continue;
                }
                $key        = $method->id . ':' . $method->instance_id;
                $data[ $key ] = [
                    'logo'                => esc_url_raw( $method->get_option( 'logo', '' ) ),
                    'cobro_destino'       => $method->get_option( 'cobro_destino', 'no' ),
                    'cobro_destino_label' => $method->get_option( 'cobro_destino_label', 'Cobro a destino' ),
                ];
            }
        }
    } catch ( Exception $e ) {
        // Silenciar errores de inicialización temprana
    }

    return $data;
}
