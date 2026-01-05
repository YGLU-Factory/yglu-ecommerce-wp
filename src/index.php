<?php

/**
 * Plugin Name: YGLU e-commerce
 * Plugin URI: https://tuyglu.com/
 * Description: Conecta su sitio a YGLU para sincronizar pedidos y poder generar facturas verificadas
 * Version: 0.1
 * Requires at least: 4.7
 * Tested up to: 6.9
 * Author: YGLU Factory
 * Author URI: https://tuyglu.com/
 * Requires Plugins: woocommerce
 **/

define("YGE_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("YGE_PLUGIN_URL", plugin_dir_url(__FILE__));
define("YGE_PLUGIN_SLUG", "yglu-ecommerce");
define("YGE_API_ENDPOINT", "https://yglu.dev.local/api/");

require_once YGE_PLUGIN_PATH . "admin.php";
require_once YGE_PLUGIN_PATH . "sync.php";

register_activation_hook(__FILE__, "yge_activate_plugin");
function yge_activate_plugin()
{
    global $wp_version;
    if (version_compare($wp_version, '4.7', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('YGLU e-commerce necesita WordPress 4.7 o superior.');
    }
}

function get_url($file)
{
    return YGE_PLUGIN_URL . $file;
}

function get_path($file)
{
    return YGE_PLUGIN_PATH . $file;
}

function yge_enqueue_styles()
{
    wp_enqueue_style("yge-style", get_url("style.css"), array(), filemtime(get_path("style.css")));
}

function yge_enqueue_scripts()
{
    wp_enqueue_script("yge-script", get_url("script.js"), array("jquery"), filemtime(get_path("script.js")));
}

add_action("wp_enqueue_scripts", "yge_enqueue_styles");
add_action("wp_enqueue_scripts", "yge_enqueue_scripts");



// Comprobar si hay que añadir el campo de NIF
function do_add_nif_field()
{
    $asda = get_option('show_nif_field', 'no');
    return (get_option('show_nif_field', 'no') == 'yes' ? true : false);
}

// Añadir campo NIF al checkout
add_filter('woocommerce_billing_fields', function ($fields) {
    if (!do_add_nif_field()) return $fields;

    $fields['_yge_billing_nif'] = array(
        'label'       => __('NIF', 'woocommerce'),
        'placeholder' => __('Tu NIF', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 25,
        'clear'       => true,
    );
    return $fields;
}, 10, 1);

// Agregar el campo en caso de que se use WooCommerce Blocks
add_action( 'woocommerce_init', function() {
    if (!do_add_nif_field()
        || !WC_Blocks_Utils::has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' )
        || !function_exists( 'woocommerce_register_additional_checkout_field' ) )
    {
        return;
    }

    woocommerce_register_additional_checkout_field(
        array(
            'id'       => 'yge/billing_nif',
            'label'    => __( 'NIF', 'yglu-ecommerce'),
            'location' => 'address',
            'type'     => 'text',
            'required' => true,
            'sanitize_callback' => function( $value ) {
                return sanitize_text_field( $value );
            },
            'validate_callback' => 'validate_nif'
        )
    );
});

// Validación del NIF
add_action('woocommerce_checkout_process', function () {
    if (!do_add_nif_field()) return;

    if (empty($_POST['_yge_billing_nif'])) {
        wc_add_notice(__('Por favor, introduce tu NIF.', 'woocommerce'), 'error');
    } else {
        $nif = strtoupper(trim($_POST['_yge_billing_nif']));
        if (!validate_nif($nif)) {
            wc_add_notice(__('El NIF introducido no es válido.', 'woocommerce'), 'error');
        }
    }
});

// Guardar NIF en meta del pedido
add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    if (!empty($_POST['_yge_billing_nif'])) {
        update_post_meta($order_id, '_yge_billing_nif', sanitize_text_field($_POST['_yge_billing_nif']));
    }
});

// Mostrar NIF en el panel de administración del pedido
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
    $nif = $order->get_meta('_yge_billing_nif');
    if ($nif) {
        echo '<p><strong>' . __('NIF', 'woocommerce') . ':</strong> ' . esc_html($nif) . '</p>';
    }
});

// Guardar NIF en el perfil del usuario (cuando se completa el pedido)
add_action('woocommerce_checkout_update_user_meta', function ($customer_id, $posted) {
    if (!empty($posted['_yge_billing_nif'])) {
        update_user_meta($customer_id, '_yge_billing_nif', sanitize_text_field($posted['_yge_billing_nif']));
    }
}, 10, 2);

// Rellenar automáticamente el NIF guardado del usuario
add_filter('woocommerce_checkout_get_value', function ($value, $input) {
    if ($input === '_yge_billing_nif' && is_user_logged_in()) {
        $saved_nif = get_user_meta(get_current_user_id(), '_yge_billing_nif', true);
        if (!empty($saved_nif)) {
            return $saved_nif;
        }
    }
    return $value;
}, 10, 2);

// Función básica de validación de NIF/NIE/CIF español
if (!function_exists('validate_nif')) {
    function validate_nif($nif)
    {
        // Elimina espacios
        $nif = strtoupper(trim($nif));

        // Valida DNI (8 números + letra)
        if (preg_match('/^[0-9]{8}[A-Z]$/', $nif)) {
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            return $nif[8] === $letras[$nif % 23];
        }

        // Valida NIE (X/Y/Z + 7 números + letra)
        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $nif)) {
            $map = array('X' => '0', 'Y' => '1', 'Z' => '2');
            $num = str_replace(array_keys($map), array_values($map), substr($nif, 0, 1)) . substr($nif, 1, 7);
            $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
            return $nif[8] === $letras[$num % 23];
        }

        // Valida CIF básico (1 letra + 7 números + 1 dígito/letra)
        if (preg_match('/^[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J]$/', $nif)) {
            return true; // validación básica, no completa de CIF
        }

        return false;
    }
}