<?php

add_filter('woocommerce_my_account_my_orders_actions', 'yge_add_invoice_order_action', 10, 2);
add_action('admin_post_yge_invoice', 'yge_handle_invoice_request');

/**
 * Añade el botón "Factura" al listado de pedidos de Mi cuenta si el pedido tiene factura en YGLU.
 *
 * @param array    $actions Acciones ya registradas por WooCommerce.
 * @param WC_Order $order   Pedido del listado.
 * @return array Acciones incluyendo "Factura" cuando aplica.
 */
function yge_add_invoice_order_action($actions, $order)
{
    if (!$order instanceof WC_Order) {
        return $actions;
    }

    if (empty($order->get_meta('_yge_invoice_id'))) {
        return $actions;
    }

    $actions['invoice'] = array(
        'url'  => yge_get_invoice_url($order->get_id()),
        'name' => 'Factura',
    );

    return $actions;
}

/**
 * Construye la URL autenticada de descarga de factura para un pedido de WooCommerce.
 *
 * @param int $order_id ID del pedido de WooCommerce.
 * @return string URL hacia admin-post.php con nonce.
 */
function yge_get_invoice_url($order_id)
{
    $order_id = absint($order_id);

    return wp_nonce_url(
        admin_url('admin-post.php?action=yge_invoice&order_id=' . $order_id),
        'yge_invoice_' . $order_id
    );
}

/**
 * Proxifica el PDF de la factura de YGLU al navegador del cliente logueado.
 *
 * @return void
 */
function yge_handle_invoice_request()
{
    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    if ($order_id < 1) {
        wp_die('Pedido no válido.', 'YGLU', array('response' => 400));
    }

    check_admin_referer('yge_invoice_' . $order_id);

    if (!current_user_can('view_order', $order_id)) {
        wp_die('No tienes permiso para ver esta factura.', 'YGLU', array('response' => 403));
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_die('Pedido no encontrado.', 'YGLU', array('response' => 404));
    }

    $invoice_id = (int) $order->get_meta('_yge_invoice_id');
    if ($invoice_id < 1) {
        wp_die('Este pedido no tiene factura asociada.', 'YGLU', array('response' => 404));
    }

    $api_key = get_option('yge_api_key');
    if (empty($api_key)) {
        error_log('YGLU e-commerce: Sin clave API, no se puede descargar la factura.');
        wp_die('No se ha podido obtener la factura. Inténtalo de nuevo más tarde.', 'YGLU', array('response' => 500));
    }

    $response = wp_remote_get(
        YGE_API_ENDPOINT . 'sales/invoices/' . $invoice_id . '/pdf',
        array(
            'headers' => array(
                'Apikey' => $api_key,
            ),
            'sslverify' => false,
            'timeout' => 30,
        )
    );

    if (is_wp_error($response)) {
        error_log('YGLU API Error: ' . $response->get_error_message());
        wp_die('No se ha podido obtener la factura. Inténtalo de nuevo más tarde.', 'YGLU', array('response' => 502));
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($response_code !== 200 || empty($body['success']) || empty($body['data']['contentBase64'])) {
        error_log('YGLU API Response Error: ' . wp_remote_retrieve_body($response));
        wp_die('No se ha podido obtener la factura. Inténtalo de nuevo más tarde.', 'YGLU', array('response' => 502));
    }

    $pdf = base64_decode($body['data']['contentBase64'], true);
    if ($pdf === false || $pdf === '') {
        error_log('YGLU: No se ha podido decodificar el PDF de la factura ' . $invoice_id);
        wp_die('No se ha podido obtener la factura. Inténtalo de nuevo más tarde.', 'YGLU', array('response' => 502));
    }

    $filename = !empty($body['data']['filename']) ? $body['data']['filename'] : ('Factura-' . $invoice_id . '.pdf');
    $filename = sanitize_file_name($filename);

    nocache_headers();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}
