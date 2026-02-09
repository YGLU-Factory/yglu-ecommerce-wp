<?php

/**
 * Devuelve los datos de "cabecera" del pedido
 *
 * @param WC_Order $order
 * @return array
 */
function yge_prepare_order_data($order)
{
    /**@var \WC_Order $order */
    $order_data = array(
        'id' => $order->get_id(),
        'status' => $order->get_status(),
        'create_invoice' => (get_option('create_invoice', 'no') == 'yes' ? true : false),
        'customer_fiscal_data' => yge_get_customer_fiscal_data($order),
        'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
        'discount_amount' => $order->get_total_discount(),
        'discount_percentage' => yge_calculate_discount_percentage($order),
        'shipping_amount' => floatval($order->get_shipping_total()),
        'shipping_tax' => floatval($order->get_shipping_tax()),
        'total_amount' => $order->get_total(),
        'total_without_tax' => $order->get_total() - $order->get_total_tax(),
        'order_lines' => yge_prepare_order_lines($order),
    );

    return $order_data;
}

/**
 * Devuelve los datos fiscales del cliente
 *
 * @param WC_Order $order
 * @return array
 */
function yge_get_customer_fiscal_data($order)
{
    /**@var \WC_Order $order */
    // Sacamos los datos del cliente del pedido
    $billing_company = $order->get_billing_company();
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name = $order->get_billing_last_name();
    $billing_country = $order->get_billing_country();
    $billing_state = $order->get_billing_state();
    $billing_city = $order->get_billing_city();
    $billing_address_1 = $order->get_billing_address_1();
    $billing_address_2 = $order->get_billing_address_2();
    $billing_postcode = $order->get_billing_postcode();
    $billing_phone = $order->get_billing_phone();
    $billing_email = $order->get_billing_email();

    // Comprobación del tipo de campo de NIF
    $nif_field_setting = get_option('show_nif_field', 'no');
    $fiscal_number = '';

    if ($nif_field_setting === 'yes') { // Campo agregado por YGLU e-commerce
        $fiscal_number = $order->get_meta('_yge_billing_nif') ?: '';
    } elseif ($nif_field_setting === 'custom') { // Campos custom de otro plugin
        $custom_field_name = get_option('show_nif_field_existent_fieldname', '');
        if (!empty($custom_field_name)) {
            $fiscal_number = $order->get_meta($custom_field_name) ?: '';
        }
    }

    // Si por lo que sea no tenemos NIF (probablemente campo custom inexistente) enviamos uno incorrecto
    if (empty($fiscal_number)) {
        $fiscal_number = '00000000X';
    }

    return array(
        'name' => trim($billing_first_name . ' ' . $billing_last_name),
        'company' => $billing_company,
        'nif' => $fiscal_number,
        'address' => array(
            'street' => $billing_address_1,
            'street_extra' => $billing_address_2,
            'city' => $billing_city,
            'state' => $billing_state,
            'postcode' => $billing_postcode,
            'country' => $billing_country
        ),
        'email' => $billing_email,
        'phone' => $billing_phone,
    );
}

/**
 * Devuelve los datos de "cabecera" del pedido
 *
 * @param WC_Order $order
 * @return int
 */
function yge_calculate_discount_percentage($order)
{
    $total = $order->get_total();
    $discount_total = $order->get_total_discount();

    if ($total > 0) {
        return ($discount_total / $total) * 100;
    }

    return 0;
}

/**
 * Devuelve las líneas del pedido
 *
 * @param WC_Order $order
 * @return array
 */
function yge_prepare_order_lines($order)
{
    $lines = array();
    $items = $order->get_items();

    $position = 1;
    foreach ($items as $item_id => $item) {

        $line_data = array(
            'name' => $item->get_name(),
            'position' => $position,
            'quantity' => $item->get_quantity(),
            'product_price_without_tax' => $item->get_subtotal() / max($item->get_quantity(), 1), // Precio por unidad sin tasas (Precio)
            'price_without_tax' => $item->get_subtotal(), // Precio de la línea sin tasas (ImporteBruto)
            'price_with_tax' => $item->get_total() + $item->get_total_tax(), // Precio de la línea con tasas (ImporteLiquido)
            'tax_percentage' => yge_get_item_tax_percentage($item), // Porcentaje de tasas de la línea (BaseImponible)
            'tax_amount' => $item->get_total_tax(), // Total de tasas para la línea (CuotaIva)
        );

        $lines[] = $line_data;
        $position++;
    }

    return $lines;
}

/**
 * Devuelve el porcentaje de IVA de un producto
 *
 * @param \WC_Order_Item $item
 * @return int
 */
function yge_get_item_tax_percentage($item)
{
    $taxCategories = [21.00, 10.00, 4.00];
    $amount = $item->get_total() > 0 ? ($item->get_total_tax() / $item->get_total()) * 100 : 0;
    foreach ($taxCategories as $iva) {
        if (abs($amount - $iva) < 0.05) {
            return $iva;
        }
    }
    return round($amount, 2);
}
