=== KR Direct Payments ===
Contributors: karenrios
Tags: woocommerce, payment gateway, zelle, pago movil, binance, bank transfer
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Metodos de pago manuales para WooCommerce: Zelle, Transferencia Bancaria, Pago Movil y Binance. Apariencia configurable, formulario de verificacion y carga de comprobante.

== Description ==
KR Direct Payments agrega cuatro pasarelas de pago manuales a WooCommerce, pensadas para Venezuela y pagos directos:

* Zelle
* Transferencia Bancaria (con bancos de Venezuela)
* Pago Movil / InstaPago (con QR)
* Binance (Pay ID / wallet + QR)

Cada metodo:

* Muestra al cliente los datos de pago con botones de copiar.
* Muestra un formulario de verificacion adaptado al metodo.
* Permite adjuntar el comprobante (captura) en JPG, PNG, WEBP o PDF.
* Es 100% configurable desde el admin: colores (variables CSS), tipografia, radios, iconos/logo y mensajes.
* Soporta descuento opcional por metodo.
* Soporta recargo fijo por metodo (por defecto $5 en Transferencia Bancaria y Pago Movil), aplicado y retirado en vivo al cambiar de metodo.
* Muestra el total a pagar en Bs. calculado con la tasa oficial del BCV (euro del dia por defecto, dolar opcional) en el checkout, la pagina de pedido recibido, los correos y el admin. La tasa se congela en el pedido al momento de la compra.

Compatibilidad:

* HPOS (almacenamiento de pedidos de alto rendimiento).
* WooCommerce Cart/Checkout Blocks.
* PHP 7.4 a 8.x.

== Installation ==
1. Sube la carpeta kr-direct-payments a /wp-content/plugins/ o instala el ZIP desde Plugins > Anadir nuevo > Subir plugin.
2. Activa el plugin.
3. Ve a WooCommerce > Ajustes > Pagos y configura cada metodo (Zelle, Transferencia Bancaria, Pago Movil, Binance).

Nota: si tenias el plugin "KR Zelle Gateway" por separado, desactivalo antes de activar este. La configuracion de Zelle se conserva porque usa el mismo identificador (kr_zelle).

== Changelog ==
= 2.6.0 =
* Las fuentes ahora se COMBINAN: si una solo entrega USD (dolarapi), se sigue buscando la tasa EUR en las demas en vez de detenerse.
* Nueva fuente de ultimo recurso open.er-api.com (EUR y USD oficiales aproximados) para garantizar que la tasa euro siempre este disponible.
* pydolarve ahora prueba dos rutas del API (v1 y v2).
* El indicador muestra las fuentes combinadas y alerta en rojo si el metodo usa EUR y ninguna fuente la entrego.

= 2.5.1 =
* El indicador de ajustes muestra la tasa con los 8 decimales exactos que publica el BCV.

= 2.5.0 =
* Fuentes de respaldo para la tasa oficial: si bcv.org.ve no responde (bloquea IPs de datacenters), se consulta pydolarve.org (EUR y USD) y luego ve.dolarapi.com (USD).
* El indicador de estado ahora hace una prueba en vivo en cada carga de la pagina de ajustes y muestra que fuente funciono.

= 2.4.0 =
* Indicador de estado de la conexion BCV en la seccion de ajustes (muestra la tasa EUR/USD actual y fecha, o el error si el servidor no puede alcanzar bcv.org.ve).
* Nueva "Tasa manual de respaldo": se usa automaticamente si la tasa del BCV no esta disponible (hosting que bloquea conexiones salientes, BCV caido en instalacion nueva).

= 2.3.0 =
* La tasa BCV ahora se actualiza en segundo plano con WP-Cron: el checkout nunca espera por bcv.org.ve (responde siempre con la tasa guardada).
* Frecuencia de actualizacion configurable desde el admin (por defecto cada 30 minutos, minimo 5), antes fija en 3 horas.
* Si la tasa expira, se sirve la ultima conocida y se refresca al instante en segundo plano (stale-while-revalidate). Reintento tras fallo bajado de 15 a 5 minutos y timeout de la consulta de 15 a 8 segundos.

= 2.2.0 =
* Recargo fijo configurable por metodo de pago (activado por defecto con $5 en Transferencia Bancaria y Pago Movil). Se recalcula en vivo en el checkout clasico y en Checkout Blocks.
* Total a pagar en Bs. segun la tasa oficial publicada por el BCV (bcv.org.ve): euro del dia por defecto, dolar opcional. Cache de 3 horas con respaldo de la ultima tasa conocida.
* La tasa y el monto en Bs. se guardan en el pedido al comprar y se muestran en el checkout, pedido recibido, correos y pantalla de pedido del admin.

= 2.0.0 =
* Unificado: ahora incluye Zelle ademas de Transferencia Bancaria, Pago Movil y Binance (un solo plugin, sin conflictos).
* Pagina de gracias rediseñada (stepper, monto destacado, nota del pedido, "que pasa despues") para todos los metodos.
* Comprobante/captura y "Nombre de quien envia" disponibles en todos los metodos.
* Selector de idioma (Espanol por defecto / English) y acentos correctos en espanol.

= 1.0.0 =
* Version inicial unificada: Zelle + Transferencia Bancaria + Pago Movil + Binance.
* Formulario de verificacion por metodo y carga de comprobante.
* Apariencia configurable (colores, tipografia, iconos) por metodo.
* Compatibilidad con HPOS y Checkout Blocks.
