<?php

require_once YGE_PLUGIN_PATH . 'utils.php';

add_action('woocommerce_order_status_changed', 'yge_sync_order_to_api', 10, 3);
function yge_sync_order_to_api($order_id, $old_status, $new_status)
{
    // Sólo se sincronizan pedidos en estado "Procesando" o "Completado"
    if ($new_status === 'processing' || $new_status === 'completed') {
        yge_send_order_to_api($order_id);
    }
}

/**
 * Envía un pedido a YGLU
 *
 * @param int $order_id
 * @return boolean
 */
function yge_send_order_to_api($order_id)
{
    $order = wc_get_order($order_id);

    if (!$order) {
        return false;
    }

    $api_key = get_option('api_key');

    if (empty($api_key)) {
        error_log('YGLU e-commerce: Sin clave API, no se enviarán datos a YGLU.');
        return false;
    }

    $order_data = wp_json_encode(yge_prepare_order_data($order));
    $api_url = YGE_API_ENDPOINT.'orders/create';

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Apikey' => $api_key,
        ),
        'body' => $order_data,
        'timeout' => 30,
    );
return;

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        error_log('YGLU API Error: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200 && $response_code !== 201) {
        error_log('YGLU API Response Error: ' . wp_remote_retrieve_body($response));
        return false;
    }

    error_log('YGLU: Order ' . $order_id . ' synced successfully');
    return true;
}
