<?php
defined( 'ABSPATH' ) || exit;

/**
 * Popup/banner de promociones de envío.
 * Configuración en WooCommerce > Ajustes > Envío > Promoción de Envío.
 */
class WCEV_Popup {

    public static function init() {
        add_filter( 'woocommerce_get_sections_shipping',  [ __CLASS__, 'add_section' ] );
        add_filter( 'woocommerce_get_settings_shipping',  [ __CLASS__, 'get_settings' ], 10, 2 );
        add_action( 'wp_footer', [ __CLASS__, 'render' ] );
    }

    /* ── Sección en ajustes de envío ──────────────────────────────────────── */
    public static function add_section( $sections ) {
        $sections['wcev_promo'] = __( 'Promoción de Envío', 'wc-envios-venezuela' );
        return $sections;
    }

    public static function get_settings( $settings, $current_section ) {
        if ( 'wcev_promo' !== $current_section ) {
            return $settings;
        }

        return [
            [
                'title' => __( 'Banner / Popup de Promoción', 'wc-envios-venezuela' ),
                'type'  => 'title',
                'desc'  => __( 'Muestra un popup emergente con mensajes como "envíos gratis esta semana". Puedes limitar las fechas de vigencia.', 'wc-envios-venezuela' ),
                'id'    => 'wcev_popup_section',
            ],
            [
                'title'   => __( 'Activar popup', 'wc-envios-venezuela' ),
                'type'    => 'checkbox',
                'id'      => 'wcev_popup_enabled',
                'default' => 'no',
                'desc'    => __( 'Mostrar el popup de promoción en la tienda.', 'wc-envios-venezuela' ),
            ],
            [
                'title'   => __( 'Mensaje', 'wc-envios-venezuela' ),
                'type'    => 'textarea',
                'id'      => 'wcev_popup_message',
                'css'     => 'width:100%;min-height:80px;',
                'default' => '🚚 ¡Esta semana envíos GRATIS! Aprovecha tu pedido.',
            ],
            [
                'title'    => __( 'Subtítulo', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_subtitle',
                'default'  => 'Válido solo por tiempo limitado',
                'desc_tip' => true,
                'desc'     => __( 'Texto secundario debajo del mensaje.', 'wc-envios-venezuela' ),
            ],
            [
                'title'    => __( 'Válido desde (YYYY-MM-DD)', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_date_from',
                'class'    => 'wcev-datepicker',
                'default'  => '',
                'desc_tip' => true,
                'desc'     => __( 'Deja vacío para que sea activo de inmediato.', 'wc-envios-venezuela' ),
            ],
            [
                'title'    => __( 'Válido hasta (YYYY-MM-DD)', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_date_to',
                'class'    => 'wcev-datepicker',
                'default'  => '',
                'desc_tip' => true,
                'desc'     => __( 'Deja vacío para que sea indefinido.', 'wc-envios-venezuela' ),
            ],
            [
                'title'   => __( 'Posición', 'wc-envios-venezuela' ),
                'type'    => 'select',
                'id'      => 'wcev_popup_position',
                'default' => 'center',
                'options' => [
                    'center'       => __( 'Centro de pantalla (modal)', 'wc-envios-venezuela' ),
                    'bottom-left'  => __( 'Esquina inferior izquierda', 'wc-envios-venezuela' ),
                    'bottom-right' => __( 'Esquina inferior derecha', 'wc-envios-venezuela' ),
                    'top-bar'      => __( 'Barra superior fija', 'wc-envios-venezuela' ),
                ],
            ],
            [
                'title'   => __( 'Color de fondo', 'wc-envios-venezuela' ),
                'type'    => 'color',
                'id'      => 'wcev_popup_bg_color',
                'default' => '#1a1a2e',
            ],
            [
                'title'   => __( 'Color de texto', 'wc-envios-venezuela' ),
                'type'    => 'color',
                'id'      => 'wcev_popup_text_color',
                'default' => '#ffffff',
            ],
            [
                'title'   => __( 'Color de acento / botón', 'wc-envios-venezuela' ),
                'type'    => 'color',
                'id'      => 'wcev_popup_accent_color',
                'default' => '#e94560',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'wcev_popup_section',
            ],
        ];
    }

    /* ── ¿Debe mostrarse hoy? ─────────────────────────────────────────────── */
    private static function is_active() {
        if ( 'yes' !== get_option( 'wcev_popup_enabled', 'no' ) ) {
            return false;
        }
        // Respetar cookie de "ya lo cerré hoy"
        if ( ! empty( $_COOKIE['wcev_popup_dismissed'] ) ) {
            return false;
        }
        $today     = current_time( 'Y-m-d' );
        $date_from = get_option( 'wcev_popup_date_from', '' );
        $date_to   = get_option( 'wcev_popup_date_to', '' );

        if ( $date_from && $today < $date_from ) {
            return false;
        }
        if ( $date_to && $today > $date_to ) {
            return false;
        }
        return true;
    }

    /* ── Renderizar HTML ──────────────────────────────────────────────────── */
    public static function render() {
        if ( ! self::is_active() ) {
            return;
        }
        $message      = get_option( 'wcev_popup_message',      '' );
        if ( ! $message ) {
            return;
        }
        $subtitle     = get_option( 'wcev_popup_subtitle',     '' );
        $bg_color     = get_option( 'wcev_popup_bg_color',     '#1a1a2e' );
        $text_color   = get_option( 'wcev_popup_text_color',   '#ffffff' );
        $accent_color = get_option( 'wcev_popup_accent_color', '#e94560' );
        $position     = get_option( 'wcev_popup_position',     'center' );
        $date_to      = get_option( 'wcev_popup_date_to',      '' );

        $styles = sprintf(
            '--wcev-bg:%s;--wcev-text:%s;--wcev-accent:%s;',
            esc_attr( $bg_color ),
            esc_attr( $text_color ),
            esc_attr( $accent_color )
        );
        ?>
        <div id="wcev-popup"
             class="wcev-popup-overlay wcev-pos-<?php echo esc_attr( $position ); ?>"
             style="<?php echo esc_attr( $styles ); ?>"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e( 'Promoción de envío', 'wc-envios-venezuela' ); ?>">

            <div class="wcev-popup-box">

                <button type="button" class="wcev-popup-close" aria-label="<?php esc_attr_e( 'Cerrar', 'wc-envios-venezuela' ); ?>">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                        <path d="M14 4L4 14M4 4l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>

                <div class="wcev-popup-icon" aria-hidden="true">🚚</div>

                <p class="wcev-popup-message"><?php echo wp_kses_post( nl2br( $message ) ); ?></p>

                <?php if ( $subtitle ) : ?>
                    <p class="wcev-popup-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>

                <?php if ( $date_to ) : ?>
                    <div class="wcev-countdown" data-date="<?php echo esc_attr( $date_to ); ?>">
                        <span class="wcev-countdown-label"><?php esc_html_e( 'Termina en:', 'wc-envios-venezuela' ); ?></span>
                        <span class="wcev-countdown-timer">--:--:--</span>
                    </div>
                <?php endif; ?>

                <button type="button" class="wcev-popup-cta wcev-popup-close">
                    <?php esc_html_e( '¡Ir a comprar!', 'wc-envios-venezuela' ); ?>
                </button>

            </div>
        </div>
        <?php
    }
}
