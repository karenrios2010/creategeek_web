<?php
defined( 'ABSPATH' ) || exit;

/**
 * Clase base para todos los métodos de envío WCEV.
 */
abstract class WCEV_Shipping_Base extends WC_Shipping_Method {

    public function __construct( $instance_id = 0 ) {
        $this->instance_id        = absint( $instance_id );
        $this->supports           = [ 'shipping-zones', 'instance-settings', 'instance-settings-modal' ];
        $this->method_description = $this->get_carrier_description();

        $this->init_form_fields();
        $this->init_settings();

        $this->title      = $this->get_option( 'title', $this->method_title );
        $this->enabled    = $this->get_option( 'enabled', 'yes' );
        $this->tax_status = 'none';
    }

    abstract protected function get_carrier_description();

    /* ── Campos de configuración de instancia ─────────────────────────────── */
    public function init_form_fields() {
        $this->instance_form_fields = [
            'enabled' => [
                'title'   => __( 'Activo', 'wc-envios-venezuela' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar este método de envío', 'wc-envios-venezuela' ),
                'default' => 'yes',
            ],
            'title' => [
                'title'    => __( 'Nombre visible', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'desc_tip' => __( 'Nombre que verá el cliente en el checkout.', 'wc-envios-venezuela' ),
                'default'  => $this->method_title,
            ],
            'logo' => [
                'title'    => __( 'Logo de la empresa', 'wc-envios-venezuela' ),
                'type'     => 'logo_upload',
                'desc_tip' => __( 'Sube o selecciona el logo del courier (PNG/SVG recomendado).', 'wc-envios-venezuela' ),
                'default'  => '',
            ],
            'cost' => [
                'title'    => __( 'Costo de envío', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'desc_tip' => __( 'Escribe el precio o deja en 0 si acuerdas el cobro por separado.', 'wc-envios-venezuela' ),
                'default'  => '0',
            ],
            'cobro_destino' => [
                'title'   => __( 'Cobro a destino', 'wc-envios-venezuela' ),
                'type'    => 'checkbox',
                'label'   => __( 'El cliente paga al recibir (cobro a destino)', 'wc-envios-venezuela' ),
                'default' => 'no',
            ],
            'cobro_destino_label' => [
                'title'    => __( 'Texto cobro a destino', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'desc_tip' => __( 'Texto que verá el cliente cuando cobro a destino esté activo.', 'wc-envios-venezuela' ),
                'default'  => __( 'Paga al recibir tu pedido', 'wc-envios-venezuela' ),
            ],
            'delivery_time' => [
                'title'    => __( 'Tiempo de entrega estimado', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'desc_tip' => __( 'Ejemplo: 2-3 días hábiles', 'wc-envios-venezuela' ),
                'default'  => '',
            ],
        ];
    }

    /* ── Campo personalizado: logo_upload ─────────────────────────────────── */
    public function generate_logo_upload_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $value     = $this->get_option( $key, '' );
        $data      = wp_parse_args( $data, [ 'title' => '', 'desc_tip' => false ] );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>">
                    <?php echo esc_html( $data['title'] ); ?>
                    <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore ?>
                </label>
            </th>
            <td class="forminp">
                <div class="wcev-logo-field">
                    <img
                        class="wcev-logo-preview"
                        src="<?php echo esc_url( $value ); ?>"
                        style="max-height:60px;max-width:160px;<?php echo $value ? 'display:block;' : 'display:none;'; ?>margin-bottom:8px;border:1px solid #ddd;border-radius:4px;padding:3px;">
                    <input
                        type="hidden"
                        id="<?php echo esc_attr( $field_key ); ?>"
                        name="<?php echo esc_attr( $field_key ); ?>"
                        value="<?php echo esc_url( $value ); ?>">
                    <button type="button" class="button wcev-upload-logo" data-target="<?php echo esc_attr( $field_key ); ?>">
                        <?php esc_html_e( 'Subir / Seleccionar logo', 'wc-envios-venezuela' ); ?>
                    </button>
                    <button type="button" class="button wcev-remove-logo" data-target="<?php echo esc_attr( $field_key ); ?>" style="<?php echo $value ? '' : 'display:none;'; ?>margin-left:4px;">
                        <?php esc_html_e( 'Quitar logo', 'wc-envios-venezuela' ); ?>
                    </button>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function validate_logo_upload_field( $key, $value ) {
        return esc_url_raw( trim( $value ) );
    }

    /* ── Calcular envío ───────────────────────────────────────────────────── */
    public function calculate_shipping( $package = [] ) {
        $cost          = (float) $this->get_option( 'cost', '0' );
        $cobro_destino = 'yes' === $this->get_option( 'cobro_destino', 'no' );
        $cd_label      = $this->get_option( 'cobro_destino_label', __( 'Paga al recibir tu pedido', 'wc-envios-venezuela' ) );
        $delivery_time = $this->get_option( 'delivery_time', '' );

        $label = $this->title;
        if ( $delivery_time ) {
            $label .= ' (' . $delivery_time . ')';
        }
        if ( $cobro_destino ) {
            $label .= ' — ' . $cd_label;
        }

        $this->add_rate( [
            'id'    => $this->get_rate_id(),
            'label' => $label,
            'cost'  => $cost,
        ] );
    }

    /* ── Logo en checkout clásico ─────────────────────────────────────────── */
    public static function inject_logo_classic( $label, $method ) {
        // method_id:instance_id — ej: wcev_mrw:3
        $rate_id   = $method->get_id();
        $rate_data = wcev_collect_rate_data();

        if ( empty( $rate_data[ $rate_id ]['logo'] ) ) {
            return $label;
        }

        $img = '<img src="' . esc_url( $rate_data[ $rate_id ]['logo'] ) . '" alt="" class="wcev-method-logo" loading="lazy">';
        return $img . '<span class="wcev-method-label">' . wp_kses_post( $label ) . '</span>';
    }
}

add_filter( 'woocommerce_cart_shipping_method_full_label', [ 'WCEV_Shipping_Base', 'inject_logo_classic' ], 10, 2 );
