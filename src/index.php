<?php
/**
* Plugin Name: YGLU e-commerce
* Plugin URI: https://tuyglu.com/
* Description: Conecta su sitio a YGLU para sincronizar pedidos y poder generar facturas verificadas
* Version: 0.1
* Author: YGLU Factory
* Author URI: https://tuyglu.com/
* Requires Plugins: woocommerce
**/

define("YGE_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("YGE_PLUGIN_URL", plugin_dir_url(__FILE__));
define("YGE_PLUGIN_SLUG", "yglu-ecommerce");

require_once YGE_PLUGIN_PATH . "admin.php";

register_activation_hook(__FILE__, "activatePlugin");
function activatePlugin() {

}

function get_url($file) {
    return YGE_PLUGIN_URL . $file;
}

function get_path($file) {
    return YGE_PLUGIN_PATH . $file;
}

function enqueue_my_styles() {
    wp_enqueue_style("my-style", get_url("style.css"), array(), filemtime(get_path("style.css")));
}

function enqueue_my_scripts() {
    wp_enqueue_script("my-script", get_url("script.js"), array("jquery"), filemtime(get_path("script.js")));
}

add_action("wp_enqueue_scripts", "enqueue_my_styles");
add_action("wp_enqueue_scripts", "enqueue_my_scripts");

