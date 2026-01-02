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

require_once YGE_PLUGIN_PATH . "admin.php";

register_activation_hook(__FILE__, "yge_activate_plugin");
function yge_activate_plugin() {
    global $wp_version;
    if (version_compare($wp_version, '4.7', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('YGLU e-commerce necesita WordPress 4.7 o superior.');
    }
}

function get_url($file) {
    return YGE_PLUGIN_URL . $file;
}

function get_path($file) {
    return YGE_PLUGIN_PATH . $file;
}

function yge_enqueue_styles() {
    wp_enqueue_style("yge-style", get_url("style.css"), array(), filemtime(get_path("style.css")));
}

function yge_enqueue_scripts() {
    wp_enqueue_script("yge-script", get_url("script.js"), array("jquery"), filemtime(get_path("script.js")));
}

add_action("wp_enqueue_scripts", "yge_enqueue_styles");
add_action("wp_enqueue_scripts", "yge_enqueue_scripts");

