<?php
/**
 * Abstract base gateway for KR Direct Payments.
 *
 * Each manual payment method (Zelle, Bank Transfer, Pago Movil, Binance)
 * extends this class and only declares:
 *   - its own admin "detail" fields (account data shown to the buyer)
 *   - get_payment_rows()         : label/value pairs shown to the buyer
 *   - get_verification_fields()  : the form the buyer fills after paying
 *   - get_brand_icon_html()      : the small icon shown at checkout
 *
 * Everything else (styling, copy buttons, QR, proof upload, AJAX submit,
 * admin display, discount, on-hold flow, Blocks support) is shared here.
 *
 * @package KR_Direct_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]
abstract class KR_DP_Gateway_Base extends WC_Payment_Gateway {

	/**
	 * Each child sets this in its constructor BEFORE calling parent::__construct().
	 *
	 * @var string
	 */
	protected $kr_default_accent = '#6d1ed4';

	/**
	 * Default fixed surcharge (USD) applied when the method is selected.
	 * '0' disables the fee section defaults. Children set this before
	 * calling parent::__construct() (e.g. '5' for Transferencia/Pago Movil).
	 *
	 * @var string
	 */
	protected $kr_default_fee = '0';

	/**
	 * Whether the Bs (BCV) conversion is shown by default ('yes'/'no').
	 *
	 * @var string
	 */
	protected $kr_default_show_bs = 'no';

	/**
	 * Boot the gateway. Children must set $this->id, $this->method_title,
	 * $this->method_description and $this->kr_default_accent first.
	 */
	public function __construct() {
		$this->has_fields         = false;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled     = $this->get_option( 'enabled', 'no' );
		$this->title       = $this->get_option( 'title', $this->method_title );
		$this->description = $this->get_option( 'description', '' );
		$this->icon        = $this->get_brand_icon_url();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'maybe_apply_discount' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'maybe_apply_fee' ), 20 );
	}

	/* ====================================================================
	 * APPEARANCE
	 * ================================================================= */

	/**
	 * Read sanitized appearance settings.
	 *
	 * @return array
	 */
	public function get_appearance() {
		$a = $this->kr_default_accent;
		return array(
			'accent'      => kr_dp_sanitize_color( $this->get_option( 'color_accent', $a ), $a ),
			'accent_2'    => kr_dp_sanitize_color( $this->get_option( 'color_accent_2', $a ), $a ),
			'header_text' => kr_dp_sanitize_color( $this->get_option( 'color_header_text', '#ffffff' ), '#ffffff' ),
			'badge_bg'    => kr_dp_sanitize_color( $this->get_option( 'color_badge_bg', '#f6a609' ), '#f6a609' ),
			'badge_text'  => kr_dp_sanitize_color( $this->get_option( 'color_badge_text', '#ffffff' ), '#ffffff' ),
			'success'     => kr_dp_sanitize_color( $this->get_option( 'color_success', '#1aa06d' ), '#1aa06d' ),
			'border'      => kr_dp_sanitize_color( $this->get_option( 'color_border', 'rgba(127,127,127,0.3)' ), 'rgba(127,127,127,0.3)' ),
			'field_bg'    => kr_dp_sanitize_color( $this->get_option( 'color_field_bg', '#fff5f7' ), '#fff5f7' ),
			'btn_text'    => kr_dp_sanitize_color( $this->get_option( 'color_btn_text', '#ffffff' ), '#ffffff' ),
			'radius'      => kr_dp_clamp_int( $this->get_option( 'ui_radius', 12 ), 0, 60, 12 ),
			'font_title'  => kr_dp_clamp_int( $this->get_option( 'font_title', 22 ), 10, 64, 22 ),
			'font_amount' => kr_dp_clamp_int( $this->get_option( 'font_amount', 20 ), 10, 64, 20 ),
			'font_base'   => kr_dp_clamp_int( $this->get_option( 'font_base', 16 ), 11, 28, 16 ),
			'max_width'   => kr_dp_clamp_int( $this->get_option( 'ui_max_width', 620 ), 320, 900, 620 ),
		);
	}

	/**
	 * Build the CSS custom-properties block scoped to this gateway.
	 *
	 * @param string $scope Wrapper class selector.
	 * @return string
	 */
	protected function css_vars( $scope ) {
		$ap = $this->get_appearance();
		return sprintf(
			'%1$s{--krdp-accent:%2$s;--krdp-accent2:%3$s;--krdp-header-text:%4$s;--krdp-badge-bg:%5$s;--krdp-badge-text:%6$s;--krdp-success:%7$s;--krdp-border:%8$s;--krdp-field-bg:%9$s;--krdp-btn-text:%10$s;--krdp-radius:%11$dpx;--krdp-font-title:%12$dpx;--krdp-font-amount:%13$dpx;--krdp-font-base:%14$dpx;--krdp-max-width:%15$dpx;}',
			$scope,
			$ap['accent'],
			$ap['accent_2'],
			$ap['header_text'],
			$ap['badge_bg'],
			$ap['badge_text'],
			$ap['success'],
			$ap['border'],
			$ap['field_bg'],
			$ap['btn_text'],
			$ap['radius'],
			$ap['font_title'],
			$ap['font_amount'],
			$ap['font_base'],
			$ap['max_width']
		);
	}

	/* ====================================================================
	 * ADMIN FORM FIELDS
	 * ================================================================= */

	/**
	 * Build the admin settings fields. Concrete (overrides WC_Settings_API)
	 * because PHP forbids re-declaring an inherited method as abstract.
	 * Children may override but the default already covers every method.
	 */
	public function init_form_fields() {
		$this->form_fields = $this->common_form_fields();
	}

	/**
	 * Detail (account) fields shown to the buyer. Children return a WC
	 * settings-fields array. Keys here become $this->get_option( key ).
	 *
	 * @return array
	 */
	abstract protected function detail_form_fields();

	/**
	 * Shared admin fields: enable, title, description, icon, appearance,
	 * messages, discount, proof.
	 *
	 * @return array
	 */
	protected function common_form_fields() {
		$accent = $this->kr_default_accent;

		return array(

			'section_general'   => array(
				'title' => __( 'General', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'enabled'           => array(
				'title'   => __( 'Activar / Desactivar', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => sprintf( __( 'Activar %s', 'kr-direct-payments' ), $this->method_title ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Titulo', 'kr-direct-payments' ),
				'type'        => 'text',
				'description' => __( 'Nombre del metodo que ve el cliente en el checkout.', 'kr-direct-payments' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'description'       => array(
				'title'       => __( 'Descripcion corta', 'kr-direct-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Texto bajo el titulo en el checkout.', 'kr-direct-payments' ),
				'default'     => '',
			),

			'section_icon'      => array(
				'title' => __( 'Icono / logo', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'icon_style'        => array(
				'title'   => __( 'Estilo del icono', 'kr-direct-payments' ),
				'type'    => 'select',
				'default' => 'builtin',
				'options' => array(
					'builtin' => __( 'Icono integrado (se tiñe con el color de acento)', 'kr-direct-payments' ),
					'custom'  => __( 'Logo personalizado', 'kr-direct-payments' ),
					'none'    => __( 'Sin icono', 'kr-direct-payments' ),
				),
			),
			'logo_url'          => array(
				'title' => __( 'Logo personalizado', 'kr-direct-payments' ),
				'type'  => 'kr_logo',
			),
			'logo_height'       => array(
				'title'   => __( 'Alto del logo (px)', 'kr-direct-payments' ),
				'type'    => 'number',
				'default' => '24',
			),

			'section_detail'    => array(
				'title' => __( 'Datos de la cuenta', 'kr-direct-payments' ),
				'type'  => 'title',
			),
		)
		+ $this->detail_form_fields()
		+ array(

			'section_messages'  => array(
				'title' => __( 'Mensajes', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'instructions'      => array(
				'title'   => __( 'Instrucciones', 'kr-direct-payments' ),
				'type'    => 'textarea',
				'default' => __( 'Realiza el pago con los siguientes datos y luego registra tu comprobante.', 'kr-direct-payments' ),
			),
			'verify_title'      => array(
				'title'   => __( 'Titulo del formulario de verificacion', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => __( 'Completa estos datos para verificar tu pago', 'kr-direct-payments' ),
			),
			'support_contact'   => array(
				'title'   => __( 'Contacto de soporte', 'kr-direct-payments' ),
				'type'    => 'text',
				'default' => '',
			),

			'section_proof'     => array(
				'title' => __( 'Comprobante (captura)', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'enable_proof'      => array(
				'title'   => __( 'Permitir adjuntar comprobante', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Mostrar campo para subir la captura del pago', 'kr-direct-payments' ),
				'default' => 'yes',
			),
			'require_proof'     => array(
				'title'   => __( 'Comprobante obligatorio', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Exigir la captura para registrar el pago', 'kr-direct-payments' ),
				'default' => 'no',
			),

			'section_colors'    => array(
				'title' => __( 'Colores', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'color_accent'      => array( 'title' => __( 'Acento', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => $accent ),
			'color_accent_2'    => array( 'title' => __( 'Acento 2 (degradado)', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => $accent ),
			'color_header_text' => array( 'title' => __( 'Texto del encabezado', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#ffffff' ),
			'color_badge_bg'    => array( 'title' => __( 'Fondo de la insignia', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#f6a609' ),
			'color_badge_text'  => array( 'title' => __( 'Texto de la insignia', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#ffffff' ),
			'color_success'     => array( 'title' => __( 'Color de exito', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#1aa06d' ),
			'color_border'      => array( 'title' => __( 'Bordes', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => 'rgba(127,127,127,0.3)' ),
			'color_field_bg'    => array( 'title' => __( 'Fondo de campos', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#fff5f7' ),
			'color_btn_text'    => array( 'title' => __( 'Texto de botones', 'kr-direct-payments' ), 'type' => 'kr_color', 'default' => '#ffffff' ),

			'section_type'      => array(
				'title' => __( 'Tipografia y diseño', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'font_base'         => array( 'title' => __( 'Fuente base (px)', 'kr-direct-payments' ), 'type' => 'number', 'default' => '16' ),
			'font_title'        => array( 'title' => __( 'Fuente del titulo (px)', 'kr-direct-payments' ), 'type' => 'number', 'default' => '22' ),
			'font_amount'       => array( 'title' => __( 'Fuente del monto (px)', 'kr-direct-payments' ), 'type' => 'number', 'default' => '20' ),
			'ui_radius'         => array( 'title' => __( 'Radio de bordes (px)', 'kr-direct-payments' ), 'type' => 'number', 'default' => '12' ),
			'ui_max_width'      => array( 'title' => __( 'Ancho maximo (px)', 'kr-direct-payments' ), 'type' => 'number', 'default' => '620' ),

			'section_discount'  => array(
				'title' => __( 'Descuento', 'kr-direct-payments' ),
				'type'  => 'title',
			),
			'enable_discount'   => array(
				'title'   => __( 'Activar descuento', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Aplicar un descuento cuando se elige este metodo', 'kr-direct-payments' ),
				'default' => 'no',
			),
			'discount_percent'  => array(
				'title'   => __( 'Porcentaje de descuento', 'kr-direct-payments' ),
				'type'    => 'number',
				'default' => '0',
			),

			'section_fee'       => array(
				'title'       => __( 'Recargo por metodo de pago', 'kr-direct-payments' ),
				'type'        => 'title',
				'description' => __( 'Suma un costo fijo (en la moneda de la tienda) al total cuando el cliente elige este metodo.', 'kr-direct-payments' ),
			),
			'enable_fee'        => array(
				'title'   => __( 'Activar recargo fijo', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Aplicar un recargo fijo cuando se elige este metodo', 'kr-direct-payments' ),
				'default' => ( (float) $this->kr_default_fee > 0 ) ? 'yes' : 'no',
			),
			'fee_amount'        => array(
				'title'             => __( 'Monto del recargo', 'kr-direct-payments' ),
				'type'              => 'number',
				'description'       => __( 'Monto fijo en la moneda de la tienda (ej: 5).', 'kr-direct-payments' ),
				'default'           => $this->kr_default_fee,
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '0.01',
				),
			),
			'fee_label'         => array(
				'title'       => __( 'Etiqueta del recargo', 'kr-direct-payments' ),
				'type'        => 'text',
				'description' => __( 'Texto de la linea del recargo en el checkout. Vacio = "Comision {metodo}".', 'kr-direct-payments' ),
				'default'     => '',
				'desc_tip'    => true,
			),

			'section_bcv'       => array(
				'title'       => __( 'Conversion a Bolivares (tasa BCV)', 'kr-direct-payments' ),
				'type'        => 'title',
				'description' => __( 'Muestra el total a pagar en Bs. usando la tasa oficial publicada por el Banco Central de Venezuela (bcv.org.ve). La tasa se cachea 3 horas y se guarda en el pedido al momento de la compra.', 'kr-direct-payments' ),
			),
			'show_bs'           => array(
				'title'   => __( 'Mostrar total en Bs.', 'kr-direct-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Mostrar el calculo del monto a pagar en bolivares en el checkout, pedido y correos', 'kr-direct-payments' ),
				'default' => $this->kr_default_show_bs,
			),
			'bcv_source'        => array(
				'title'   => __( 'Tasa BCV a usar', 'kr-direct-payments' ),
				'type'    => 'select',
				'default' => 'euro',
				'options' => array(
					'euro'  => __( 'Euro (EUR) del dia', 'kr-direct-payments' ),
					'dolar' => __( 'Dolar (USD) del dia', 'kr-direct-payments' ),
				),
			),
		);
	}

	/* ====================================================================
	 * CUSTOM ADMIN FIELD TYPES (color + logo)
	 * ================================================================= */

	/**
	 * Render a color field (native swatch synced to a text input).
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_kr_color_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'   => '',
			'default' => '',
		);
		$data      = wp_parse_args( $data, $defaults );
		$value     = $this->get_option( $key, $data['default'] );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<input type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $value ); ?>" style="width:160px" class="kr-dp-color-text" />
				<input type="color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $value ) ? $value : '#6d1ed4' ); ?>" class="kr-dp-color-pick" style="vertical-align:middle;margin-left:6px;height:30px;width:42px;padding:0;border:1px solid #ccc;cursor:pointer" />
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate the color field.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Posted value.
	 * @return string
	 */
	public function validate_kr_color_field( $key, $value ) {
		return kr_dp_sanitize_color( $value, '' );
	}

	/**
	 * Render a Media-Library logo picker.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field data.
	 * @return string
	 */
	public function generate_kr_logo_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$value     = $this->get_option( $key, '' );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( isset( $data['title'] ) ? $data['title'] : '' ); ?></label>
			</th>
			<td class="forminp">
				<input type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( $value ); ?>" style="width:60%" class="kr-dp-logo-url" placeholder="https://..." />
				<button type="button" class="button kr-dp-logo-pick"><?php esc_html_e( 'Elegir imagen', 'kr-direct-payments' ); ?></button>
				<div style="margin-top:8px"><?php if ( $value ) : ?><img src="<?php echo esc_url( $value ); ?>" style="max-height:40px;background:#eee;padding:4px;border-radius:6px" class="kr-dp-logo-preview" /><?php endif; ?></div>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate the logo field.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Posted value.
	 * @return string
	 */
	public function validate_kr_logo_field( $key, $value ) {
		return esc_url_raw( trim( (string) $value ) );
	}

	/* ====================================================================
	 * ICONS
	 * ================================================================= */

	/**
	 * Inline SVG badge tinted with the accent color. Children may override.
	 *
	 * @param int $size Height in px.
	 * @return string
	 */
	public function get_brand_icon_html( $size = 24 ) {
		$ap     = $this->get_appearance();
		$accent = $ap['accent'];
		$svg    = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="%1$d" height="%1$d" style="vertical-align:middle"><rect width="48" height="48" rx="11" fill="%2$s"/><path d="M16 15h16v4l-9 10h9v4H15v-4l9-10h-8z" fill="#fff"/><rect x="22.4" y="10" width="3.2" height="28" rx="1.6" fill="#fff"/></svg>',
			(int) $size,
			esc_attr( $accent )
		);
		return $svg;
	}

	/**
	 * Icon HTML respecting the icon_style option.
	 *
	 * @param int $height Height in px.
	 * @return string
	 */
	protected function resolve_icon_html( $height ) {
		$style = $this->get_option( 'icon_style', 'builtin' );
		if ( 'none' === $style ) {
			return '';
		}
		if ( 'custom' === $style ) {
			$url = $this->get_option( 'logo_url', '' );
			if ( $url ) {
				return '<img src="' . esc_url( $url ) . '" alt="" style="height:' . (int) $height . 'px;width:auto;vertical-align:middle" />';
			}
		}
		return $this->get_brand_icon_html( $height );
	}

	/**
	 * Icon for the classic checkout (returns a data URI or logo URL).
	 *
	 * @return string
	 */
	public function get_brand_icon_url() {
		$style = $this->get_option( 'icon_style', 'builtin' );
		if ( 'none' === $style ) {
			return '';
		}
		if ( 'custom' === $style ) {
			$url = $this->get_option( 'logo_url', '' );
			if ( $url ) {
				return $url;
			}
		}
		return 'data:image/svg+xml;base64,' . base64_encode( $this->get_brand_icon_html( 24 ) );
	}

	/* ====================================================================
	 * PAYMENT DETAILS + VERIFICATION (children supply the specifics)
	 * ================================================================= */

	/**
	 * Rows of payment data shown to the buyer.
	 * Return an array of arrays: array( 'label' => '', 'value' => '', 'copy' => true|false ).
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	abstract public function get_payment_rows( $order );

	/**
	 * Verification form fields the buyer must fill after paying.
	 * Return an associative array keyed by field slug:
	 *   array( 'reference' => array( 'label'=>'', 'type'=>'text|date|tel|select|number',
	 *                                'required'=>bool, 'placeholder'=>'', 'options'=>array ) )
	 *
	 * @return array
	 */
	abstract public function get_verification_fields();

	/**
	 * Optional QR image URL shown to the buyer (e.g. Pago Movil / Binance).
	 *
	 * @return string
	 */
	public function get_qr_url() {
		return '';
	}

	/* ====================================================================
	 * FRONT-END: ORDER RECEIVED PAGE
	 * ================================================================= */

	/**
	 * Render the styled payment instructions + verification form.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$scope = '.kr-dp.kr-dp-' . esc_attr( $this->id );
		$ap    = $this->get_appearance();

		$rows         = array_merge( (array) $this->get_payment_rows( $order ), $this->get_bs_rows( $order ) );
		$fields       = (array) $this->get_verification_fields();
		$qr           = $this->get_qr_url();
		$instructions = wptexturize( wp_kses_post( $this->get_option( 'instructions', '' ) ) );
		$verify_title = $this->get_option( 'verify_title', __( 'Completa estos datos para verificar tu pago', 'kr-direct-payments' ) );
		$support      = $this->get_option( 'support_contact', '' );
		$enable_proof = 'yes' === $this->get_option( 'enable_proof', 'yes' );
		$require_proof = 'yes' === $this->get_option( 'require_proof', 'no' );

		$already = $order->get_meta( '_kr_dp_verified' );

		echo '<style>' . $this->css_vars( $scope ) . '</style>';
		echo '<div class="kr-dp kr-dp-' . esc_attr( $this->id ) . '" style="max-width:var(--krdp-max-width)">';

		// Header.
		echo '<div class="kr-dp-head">';
		echo '<span class="kr-dp-head-icon">' . wp_kses( $this->resolve_icon_html( 26 ), $this->kr_svg_allowed() ) . '</span>';
		echo '<span class="kr-dp-head-title">' . esc_html( $this->get_option( 'title', $this->method_title ) ) . '</span>';
		echo '</div>';

		if ( $instructions ) {
			echo '<p class="kr-dp-intro">' . wp_kses_post( $instructions ) . '</p>';
		}

		// Payment data rows.
		if ( $rows ) {
			echo '<div class="kr-dp-card">';
			foreach ( $rows as $row ) {
				$label = isset( $row['label'] ) ? $row['label'] : '';
				$value = isset( $row['value'] ) ? $row['value'] : '';
				if ( '' === $value ) {
					continue;
				}
				$copy = ! empty( $row['copy'] );
				echo '<div class="kr-dp-row">';
				echo '<span class="kr-dp-row-label">' . esc_html( $label ) . '</span>';
				echo '<span class="kr-dp-row-value">' . esc_html( $value );
				if ( $copy ) {
					echo ' <button type="button" class="kr-dp-copy" data-copy="' . esc_attr( $value ) . '">' . esc_html__( 'Copiar', 'kr-direct-payments' ) . '</button>';
				}
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}

		// QR.
		if ( $qr ) {
			echo '<div class="kr-dp-qr"><img src="' . esc_url( $qr ) . '" alt="QR" /></div>';
		}

		if ( $already ) {
			echo '<div class="kr-dp-done">' . esc_html__( 'Ya recibimos los datos de tu pago. Lo estamos verificando.', 'kr-direct-payments' ) . '</div>';
			if ( $support ) {
				echo '<p class="kr-dp-support">' . esc_html__( 'Soporte:', 'kr-direct-payments' ) . ' ' . esc_html( $support ) . '</p>';
			}
			echo '</div>';
			return;
		}

		// Verification form.
		echo '<h3 class="kr-dp-verify-title">' . esc_html( $verify_title ) . '</h3>';
		echo '<form class="kr-dp-form" data-order="' . esc_attr( $order_id ) . '" data-key="' . esc_attr( $order->get_order_key() ) . '" data-method="' . esc_attr( $this->id ) . '" enctype="multipart/form-data">';
		wp_nonce_field( 'kr_dp_submit', 'kr_dp_nonce' );

		foreach ( $fields as $fkey => $f ) {
			$this->render_verification_field( $fkey, $f );
		}

		if ( $enable_proof ) {
			echo '<div class="kr-dp-field">';
			echo '<label>' . esc_html__( 'Comprobante / captura del pago', 'kr-direct-payments' );
			if ( $require_proof ) {
				echo ' <span class="kr-dp-req">*</span>';
			}
			echo '</label>';
			echo '<input type="file" name="kr_dp_proof" accept="image/png,image/jpeg,image/webp,application/pdf" ' . ( $require_proof ? 'data-required="1"' : '' ) . ' />';
			echo '<small class="kr-dp-hint">' . esc_html__( 'JPG, PNG, WEBP o PDF. Maximo 5 MB.', 'kr-direct-payments' ) . '</small>';
			echo '</div>';
		}

		echo '<button type="submit" class="kr-dp-submit">' . esc_html__( 'Registrar mi pago', 'kr-direct-payments' ) . '</button>';
		echo '<div class="kr-dp-msg" role="status"></div>';
		echo '</form>';

		if ( $support ) {
			echo '<p class="kr-dp-support">' . esc_html__( 'Soporte:', 'kr-direct-payments' ) . ' ' . esc_html( $support ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render one verification field.
	 *
	 * @param string $key Field slug.
	 * @param array  $f   Field config.
	 */
	protected function render_verification_field( $key, $f ) {
		$label       = isset( $f['label'] ) ? $f['label'] : $key;
		$type        = isset( $f['type'] ) ? $f['type'] : 'text';
		$required    = ! empty( $f['required'] );
		$placeholder = isset( $f['placeholder'] ) ? $f['placeholder'] : '';
		$name        = 'kr_dp_f_' . $key;

		echo '<div class="kr-dp-field">';
		echo '<label for="' . esc_attr( $name ) . '">' . esc_html( $label );
		if ( $required ) {
			echo ' <span class="kr-dp-req">*</span>';
		}
		echo '</label>';

		if ( 'select' === $type ) {
			$options = isset( $f['options'] ) && is_array( $f['options'] ) ? $f['options'] : array();
			echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" ' . ( $required ? 'data-required="1"' : '' ) . '>';
			echo '<option value="">' . esc_html__( 'Seleccione', 'kr-direct-payments' ) . '</option>';
			foreach ( $options as $ov => $ol ) {
				echo '<option value="' . esc_attr( $ov ) . '">' . esc_html( $ol ) . '</option>';
			}
			echo '</select>';
		} else {
			$input_type = in_array( $type, array( 'date', 'tel', 'number', 'email' ), true ) ? $type : 'text';
			echo '<input type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" placeholder="' . esc_attr( $placeholder ) . '" ' . ( $required ? 'data-required="1"' : '' ) . ' />';
		}
		echo '</div>';
	}

	/**
	 * Allowed SVG tags for wp_kses when echoing the inline icon.
	 *
	 * @return array
	 */
	protected function kr_svg_allowed() {
		return array(
			'svg'  => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'style' => true, 'fill' => true ),
			'rect' => array( 'width' => true, 'height' => true, 'x' => true, 'y' => true, 'rx' => true, 'ry' => true, 'fill' => true ),
			'path' => array( 'd' => true, 'fill' => true ),
			'img'  => array( 'src' => true, 'alt' => true, 'style' => true, 'height' => true, 'width' => true ),
		);
	}

	/* ====================================================================
	 * EMAIL
	 * ================================================================= */

	/**
	 * Add payment instructions to the order email.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Admin email?
	 * @param bool     $plain_text    Plain text?
	 */
	public function email_instructions( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_a( $order, 'WC_Order' ) || $order->get_payment_method() !== $this->id ) {
			return;
		}
		if ( ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return;
		}
		$instructions = wp_strip_all_tags( $this->get_option( 'instructions', '' ) );
		if ( $instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $instructions ) ) );
		}
		$rows = array_merge( (array) $this->get_payment_rows( $order ), $this->get_bs_rows( $order ) );
		if ( $rows ) {
			echo '<ul>';
			foreach ( $rows as $row ) {
				if ( empty( $row['value'] ) ) {
					continue;
				}
				echo '<li><strong>' . esc_html( $row['label'] ) . ':</strong> ' . esc_html( $row['value'] ) . '</li>';
			}
			echo '</ul>';
		}
	}

	/* ====================================================================
	 * DISCOUNT + PROCESS
	 * ================================================================= */

	/**
	 * Apply an optional discount when this gateway is the chosen one.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public function maybe_apply_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( 'yes' !== $this->get_option( 'enable_discount', 'no' ) ) {
			return;
		}
		$chosen = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';
		if ( $chosen !== $this->id ) {
			return;
		}
		$percent = (float) $this->get_option( 'discount_percent', 0 );
		if ( $percent <= 0 ) {
			return;
		}
		$subtotal = (float) $cart->get_subtotal();
		$discount = round( $subtotal * ( $percent / 100 ), wc_get_price_decimals() );
		if ( $discount > 0 ) {
			$cart->add_fee( sprintf( __( 'Descuento %s (%s%%)', 'kr-direct-payments' ), $this->method_title, $percent ), -$discount );
		}
	}

	/**
	 * Add the fixed surcharge when this gateway is the chosen one.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public function maybe_apply_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( 'yes' !== $this->get_option( 'enable_fee', 'no' ) ) {
			return;
		}
		$chosen = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : '';
		if ( $chosen !== $this->id ) {
			return;
		}
		$amount = (float) $this->get_option( 'fee_amount', 0 );
		if ( $amount <= 0 ) {
			return;
		}
		$label = trim( (string) $this->get_option( 'fee_label', '' ) );
		if ( '' === $label ) {
			$label = sprintf( __( 'Comision %s', 'kr-direct-payments' ), $this->get_option( 'title', $this->method_title ) );
		}
		$cart->add_fee( $label, $amount, false );
	}

	/* ====================================================================
	 * BCV / BOLIVARES
	 * ================================================================= */

	/**
	 * Extra rows with the Bs conversion for an order. Uses the rate frozen
	 * on the order at purchase time; falls back to the current BCV rate.
	 *
	 * @param WC_Order $order Order.
	 * @return array Same shape as get_payment_rows().
	 */
	public function get_bs_rows( $order ) {
		if ( 'yes' !== $this->get_option( 'show_bs', 'no' ) ) {
			return array();
		}

		$source   = $this->get_option( 'bcv_source', 'euro' );
		$rate     = (float) $order->get_meta( '_kr_dp_bcv_rate' );
		$total_bs = (float) $order->get_meta( '_kr_dp_total_bs' );

		if ( $rate <= 0 ) {
			$rate     = KR_DP_BCV::get_rate( $source );
			$total_bs = $rate > 0 ? (float) $order->get_total() * $rate : 0;
		}
		if ( $rate <= 0 || $total_bs <= 0 ) {
			return array();
		}

		$src_label = ( 'dolar' === $source ) ? 'USD' : 'EUR';

		return array(
			array(
				'label' => sprintf( __( 'Tasa BCV (%s)', 'kr-direct-payments' ), $src_label ),
				'value' => kr_dp_format_bs( $rate ),
				'copy'  => false,
			),
			array(
				'label' => __( 'Monto a pagar en Bs.', 'kr-direct-payments' ),
				'value' => kr_dp_format_bs( $total_bs ),
				'copy'  => true,
			),
		);
	}

	/**
	 * Set order on-hold and finish.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array( 'result' => 'failure' );
		}

		// Congela la tasa BCV y el total en Bs al momento de la compra.
		if ( 'yes' === $this->get_option( 'show_bs', 'no' ) ) {
			$source = $this->get_option( 'bcv_source', 'euro' );
			$rate   = KR_DP_BCV::get_rate( $source );
			if ( $rate > 0 ) {
				$order->update_meta_data( '_kr_dp_bcv_rate', wc_format_decimal( $rate, 8 ) );
				$order->update_meta_data( '_kr_dp_bcv_source', $source );
				$order->update_meta_data( '_kr_dp_total_bs', wc_format_decimal( (float) $order->get_total() * $rate, 2 ) );
			}
		}

		$order->update_status( 'on-hold', sprintf( __( 'A la espera de la verificacion de %s.', 'kr-direct-payments' ), $this->method_title ) );
		wc_reduce_stock_levels( $order_id );
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
