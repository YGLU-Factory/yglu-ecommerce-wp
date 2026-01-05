<?php

/**
 * Devuelve los datos de "cabecera" del pedido
 *
 * @return array
 */
function yge_prepare_order_data($order)
{
    $order_data = array(
        'id' => $order->get_id(),
        'customer_fiscal_data' => yge_get_customer_fiscal_data($order),
        'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
        'discount_total' => $order->get_total_discount(),
        'discount_percentage' => yge_calculate_discount_percentage($order),
        'total_amount' => $order->get_total(),
        'total_without_tax' => $order->get_total() - $order->get_total_tax(),
        'order_lines' => yge_prepare_order_lines($order),
    );

    return $order_data;
}

/**
 * Devuelve los datos fiscales del cliente
 *
 * @return array
 */
function yge_get_customer_fiscal_data($order)
{
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
        $fiscal_number = $order->get_meta('_billing_nif') ?: '';
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
        'fiscal_number' => $fiscal_number,
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
 * @return array
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
            'price_without_tax' => $item->get_subtotal() / $item->get_quantity(), // Precio por unidad sin tasas
            'price_with_tax' => ($item->get_total() / $item->get_quantity()) + ($item->get_total_tax() / $item->get_quantity()), // Precio por unidad con tasas
            'tax_percentage' => yge_get_item_tax_percentage($item),
            'tax_amount' => $item->get_total_tax() // Total de tasas para la línea
        );

        $lines[] = $line_data;
        $position++;
    }

    return $lines;
}

/**
 * Devuelve el porcentaje de IVA de un producto
 *
 * @return float
 */
function yge_get_item_tax_percentage($item)
{
     $subtotal = $item->get_subtotal();

    if ($subtotal > 0) {
        return ($item->get_subtotal_tax() / $subtotal) * 100;
    }

    return 0; // Retorna 0 si el importe del subtotal es 0
}
