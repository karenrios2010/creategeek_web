<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestiona el popup de promociones (ej: "envíos gratis esta semana").
 * Configuración desde WooCommerce > Ajustes > Envío > Promoción de Envío.
 */
class WCEV_Popup {

    private static string $option_key = 'wcev_popup_settings';

    public static function init(): void {
        // Pestaña de ajustes dentro de WooCommerce > Envío
        add_filter( 'woocommerce_get_sections_shipping', [ __CLASS__, 'add_section' ] );
        add_filter( 'woocommerce_get_settings_shipping', [ __CLASS__, 'get_settings' ], 10, 2 );
        add_action( 'woocommerce_settings_save_shipping', [ __CLASS__, 'save_settings' ] );

        // Mostrar popup en frontend si corresponde
        add_action( 'wp_footer', [ __CLASS__, 'render_popup' ] );
    }

    /* ─── Pestaña en ajustes de envío ───────────────────────────────────── */
    public static function add_section( array $sections ): array {
        $sections['wcev_promo'] = __( 'Promoción de Envío', 'wc-envios-venezuela' );
        return $sections;
    }

    public static function get_settings( array $settings, string $current_section ): array {
        if ( 'wcev_promo' !== $current_section ) {
            return $settings;
        }

        return [
            [
                'title' => __( 'Banner / Popup de Promoción de Envío', 'wc-envios-venezuela' ),
                'type'  => 'title',
                'desc'  => __( 'Muestra un popup emergente a tus clientes con mensajes de promociones de envío (ej: envíos gratis esta semana). El popup respeta las fechas configuradas y puede cerrarse una vez por día.', 'wc-envios-venezuela' ),
                'id'    => 'wcev_popup_section',
            ],
            [
                'title'   => __( 'Activar popup', 'wc-envios-venezuela' ),
                'type'    => 'checkbox',
                'id'      => 'wcev_popup_enabled',
                'default' => 'no',
                'desc'    => __( 'Habilitar el popup de promoción de envío en la tienda.', 'wc-envios-venezuela' ),
            ],
            [
                'title'       => __( 'Mensaje principal', 'wc-envios-venezuela' ),
                'type'        => 'textarea',
                'id'          => 'wcev_popup_message',
                'css'         => 'width:100%;min-height:80px;',
                'default'     => __( '🚚 ¡Esta semana tenemos envíos GRATIS! Aprovecha tu pedido ahora.', 'wc-envios-venezuela' ),
                'desc'        => __( 'Puedes usar emojis y texto plano. Este es el mensaje que verá el cliente.', 'wc-envios-venezuela' ),
                'desc_tip'    => false,
            ],
            [
                'title'    => __( 'Subtítulo / CTA', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_subtitle',
                'default'  => __( 'Válido solo por tiempo limitado', 'wc-envios-venezuela' ),
                'desc'     => __( 'Texto secundario o llamada a la acción.', 'wc-envios-venezuela' ),
                'desc_tip' => true,
            ],
            [
                'title'    => __( 'Fecha de inicio', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_date_from',
                'class'    => 'wcev-datepicker',
                'default'  => '',
                'desc'     => __( 'El popup comenzará a mostrarse desde esta fecha (YYYY-MM-DD). Deja vacío para que sea inmediato.', 'wc-envios-venezuela' ),
                'desc_tip' => true,
            ],
            [
                'title'    => __( 'Fecha de fin', 'wc-envios-venezuela' ),
                'type'     => 'text',
                'id'       => 'wcev_popup_date_to',
                'class'    => 'wcev-datepicker',
                'default'  => '',
                'desc'     => __( 'El popup dejará de mostrarse después de esta fecha (YYYY-MM-DD). Deja vacío para que sea indefinido.', 'wc-envios-venezuela' ),
                'desc_tip' => true,
            ],
            [
                'title'    => __( 'Color de fondo', 'wc-envios-venezuela' ),
                'type'     => 'color',
                'id'       => 'wcev_popup_bg_color',
                'default'  => '#1a1a2e',
            ],
            [
                'title'    => __( 'Color de texto', 'wc-envios-venezuela' ),
                'type'     => 'color',
                'id'       => 'wcev_popup_text_color',
                'default'  => '#ffffff',
            ],
            [
                'title'    => __( 'Color de acento / botón', 'wc-envios-venezuela' ),
                'type'     => 'color',
                'id'       => 'wcev_popup_accent_color',
                'default'  => '#e94560',
            ],
            [
                'title'    => __( 'Posición del popup', 'wc-envios-venezuela' ),
                'type'     => 'select',
                'id'       => 'wcev_popup_position',
                'default'  => 'center',
                'options'  => [
                    'center'        => __( 'Centro de pantalla (modal)', 'wc-envios-venezuela' ),
                    'bottom-left'   => __( 'Esquina inferior izquierda', 'wc-envios-venezuela' ),
                    'bottom-right'  => __( 'Esquina inferior derecha', 'wc-envios-venezuela' ),
                    'top-bar'       => __( 'Barra superior (banner)', 'wc-envios-venezuela' ),
                ],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'wcev_popup_section',
            ],
        ];
    }

    public static function save_settings(): void {
        // WooCommerce guarda los campos automáticamente con woocommerce_update_options().
        // No se necesita lógica extra aquí, pero el hook es útil para limpieza de caché, etc.
    }

    /* ─── Verificar si el popup debe mostrarse ──────────────────────────── */
    private static function is_active(): bool {
        if ( 'yes' !== get_option( 'wcev_popup_enabled', 'no' ) ) {
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

    /* ─── Renderizar HTML del popup ─────────────────────────────────────── */
    public static function render_popup(): void {
        if ( ! self::is_active() ) {
            return;
        }

        $message      = get_option( 'wcev_popup_message',      '' );
        $subtitle     = get_option( 'wcev_popup_subtitle',     '' );
        $bg_color     = get_option( 'wcev_popup_bg_color',     '#1a1a2e' );
        $text_color   = get_option( 'wcev_popup_text_color',   '#ffffff' );
        $accent_color = get_option( 'wcev_popup_accent_color', '#e94560' );
        $position     = get_option( 'wcev_popup_position',     'center' );
        $date_to      = get_option( 'wcev_popup_date_to',      '' );

        if ( ! $message ) {
            return;
        }

        $countdown_html = '';
        if ( $date_to ) {
            $countdown_html = '<div class="wcev-countdown" data-date="' . esc_attr( $date_to ) . '">
                <span class="wcev-countdown-label">' . esc_html__( 'Termina en:', 'wc-envios-venezuela' ) . '</span>
                <span class="wcev-countdown-timer"></span>
            </div>';
        }

        $inline_styles = sprintf(
            '--wcev-bg:%s;--wcev-text:%s;--wcev-accent:%s;',
            esc_attr( $bg_color ),
            esc_attr( $text_color ),
            esc_attr( $accent_color )
        );
        ?>
        <div id="wcev-popup-overlay"
             class="wcev-popup-overlay wcev-pos-<?php echo esc_attr( $position ); ?>"
             style="<?php echo esc_attr( $inline_styles ); ?>"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e( 'Promoción de envío', 'wc-envios-venezuela' ); ?>">

            <div class="wcev-popup-box" role="document">

                <button class="wcev-popup-close" aria-label="<?php esc_attr_e( 'Cerrar', 'wc-envios-venezuela' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>

                <div class="wcev-popup-icon" aria-hidden="true">🚚</div>

                <div class="wcev-popup-content">
                    <p class="wcev-popup-message"><?php echo wp_kses_post( $message ); ?></p>
                    <?php if ( $subtitle ) : ?>
                        <p class="wcev-popup-subtitle"><?php echo esc_html( $subtitle ); ?></p>
                    <?php endif; ?>
                    <?php echo $countdown_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>

                <button class="wcev-popup-cta wcev-popup-close">
                    <?php esc_html_e( '¡Ir a comprar!', 'wc-envios-venezuela' ); ?>
                </button>

            </div>
        </div>
        <?php
    }
}
