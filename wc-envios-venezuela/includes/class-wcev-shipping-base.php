<?php
defined( 'ABSPATH' ) || exit;

/**
 * Clase base compartida por MRW, Zoom y Tealca.
 * Gestiona: título, precio, cobro a destino, logo y estado activo.
 */
abstract class WCEV_Shipping_Base extends WC_Shipping_Method {

    /** Nombre corto del carrier (mrw | zoom | tealca) */
    protected string $carrier_key;

    public function __construct( $instance_id = 0 ) {
        $this->instance_id           = absint( $instance_id );
        $this->supports              = [ 'shipping-zones', 'instance-settings', 'instance-settings-modal' ];
        $this->method_description    = $this->get_carrier_description();
        $this->init();
    }

    protected function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option( 'title', $this->method_title );
        $this->enabled            = $this->get_option( 'enabled', 'yes' );
        $this->tax_status         = 'none';

        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            [ $this, 'process_admin_options' ]
        );
    }

    abstract protected function get_carrier_description(): string;

    /* ─── Campos de configuración por instancia ─────────────────────────── */
    public function init_form_fields(): void {
        $this->instance_form_fields = [
            'enabled' => [
                'title'   => __( 'Activo', 'wc-envios-venezuela' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar este método de envío', 'wc-envios-venezuela' ),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __( 'Nombre visible', 'wc-envios-venezuela' ),
                'type'        => 'text',
                'description' => __( 'Nombre que verá el cliente en el checkout.', 'wc-envios-venezuela' ),
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ],
            'logo' => [
                'title'       => __( 'Logo de la empresa', 'wc-envios-venezuela' ),
                'type'        => 'logo_upload',   // tipo personalizado
                'description' => __( 'Sube o selecciona el logo del courier (PNG/SVG recomendado).', 'wc-envios-venezuela' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'cost' => [
                'title'       => __( 'Costo de envío (Bs)', 'wc-envios-venezuela' ),
                'type'        => 'price',
                'description' => __( 'Deja en 0 si el precio lo acuerdas por separado.', 'wc-envios-venezuela' ),
                'default'     => '0',
                'desc_tip'    => true,
            ],
            'cobro_destino' => [
                'title'       => __( 'Cobro a destino', 'wc-envios-venezuela' ),
                'type'        => 'checkbox',
                'label'       => __( 'Permitir cobro a destino (el cliente paga al recibir)', 'wc-envios-venezuela' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'cobro_destino_label' => [
                'title'       => __( 'Texto cobro a destino', 'wc-envios-venezuela' ),
                'type'        => 'text',
                'description' => __( 'Texto adicional que verá el cliente cuando cobro a destino esté activo.', 'wc-envios-venezuela' ),
                'default'     => __( 'Paga al recibir tu pedido', 'wc-envios-venezuela' ),
                'desc_tip'    => true,
            ],
            'delivery_time' => [
                'title'       => __( 'Tiempo estimado de entrega', 'wc-envios-venezuela' ),
                'type'        => 'text',
                'description' => __( 'Ejemplo: 2-3 días hábiles', 'wc-envios-venezuela' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
        ];
    }

    /* ─── Tipo de campo personalizado: logo_upload ───────────────────────── */
    public function generate_logo_upload_html( string $key, array $data ): string {
        $field_key = $this->get_field_key( $key );
        $value     = $this->get_option( $key, '' );
        $defaults  = [
            'title'       => '',
            'description' => '',
            'desc_tip'    => false,
        ];
        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>">
                    <?php echo wp_kses_post( $data['title'] ); ?>
                    <?php echo $this->get_tooltip_html( $data ); // phpcs:ignore ?>
                </label>
            </th>
            <td class="forminp">
                <div class="wcev-logo-field">
                    <?php if ( $value ) : ?>
                        <img src="<?php echo esc_url( $value ); ?>" class="wcev-logo-preview" style="max-height:60px;max-width:160px;display:block;margin-bottom:8px;">
                    <?php else : ?>
                        <img src="" class="wcev-logo-preview" style="max-height:60px;max-width:160px;display:none;margin-bottom:8px;">
                    <?php endif; ?>
                    <input type="hidden"
                           id="<?php echo esc_attr( $field_key ); ?>"
                           name="<?php echo esc_attr( $field_key ); ?>"
                           value="<?php echo esc_url( $value ); ?>">
                    <button type="button" class="button wcev-upload-logo" data-target="<?php echo esc_attr( $field_key ); ?>">
                        <?php esc_html_e( 'Subir / Seleccionar logo', 'wc-envios-venezuela' ); ?>
                    </button>
                    <?php if ( $value ) : ?>
                        <button type="button" class="button wcev-remove-logo" data-target="<?php echo esc_attr( $field_key ); ?>" style="margin-left:4px;">
                            <?php esc_html_e( 'Eliminar', 'wc-envios-venezuela' ); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button wcev-remove-logo" data-target="<?php echo esc_attr( $field_key ); ?>" style="margin-left:4px;display:none;">
                            <?php esc_html_e( 'Eliminar', 'wc-envios-venezuela' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="description"><?php echo wp_kses_post( $data['description'] ); ?></p>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function validate_logo_upload_field( string $key, $value ): string {
        return esc_url_raw( $value );
    }

    /* ─── Cálculo del envío ─────────────────────────────────────────────── */
    public function calculate_shipping( $package = [] ): void {
        $cost           = (float) $this->get_option( 'cost', 0 );
        $cobro_destino  = 'yes' === $this->get_option( 'cobro_destino', 'no' );
        $cd_label       = $this->get_option( 'cobro_destino_label', __( 'Paga al recibir tu pedido', 'wc-envios-venezuela' ) );
        $delivery_time  = $this->get_option( 'delivery_time', '' );
        $logo           = $this->get_option( 'logo', '' );

        $label = $this->title;
        if ( $delivery_time ) {
            $label .= ' (' . $delivery_time . ')';
        }
        if ( $cobro_destino ) {
            $label .= ' — ' . $cd_label;
        }

        $meta_data = [];
        if ( $logo ) {
            $meta_data['logo'] = $logo;
        }
        if ( $cobro_destino ) {
            $meta_data['cobro_destino'] = 'yes';
        }

        $this->add_rate( [
            'id'        => $this->get_rate_id(),
            'label'     => $label,
            'cost'      => $cost,
            'meta_data' => $meta_data,
        ] );
    }

    /* ─── Logo en la etiqueta del checkout ──────────────────────────────── */
    public static function filter_label( string $label, WC_Shipping_Rate $method ): string {
        $meta = $method->get_meta_data();
        if ( empty( $meta['logo'] ) ) {
            return $label;
        }
        $img = '<img src="' . esc_url( $meta['logo'] ) . '" alt="" class="wcev-method-logo" aria-hidden="true">';
        return $img . '<span class="wcev-method-label">' . esc_html( $label ) . '</span>';
    }
}

// Filtro para mostrar logo en checkout
add_filter( 'woocommerce_cart_shipping_method_full_label', [ 'WCEV_Shipping_Base', 'filter_label' ], 10, 2 );
