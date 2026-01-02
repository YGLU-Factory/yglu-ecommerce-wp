<?php

/**
 * Agregar el menú a la sidebar de WP
 */
add_action("admin_menu", "yge_add_menu");
function yge_add_menu()
{
    add_menu_page(
        "YGLU e-commerce", // Título de la página
        "YGLU e-commerce", // Título del side menu
        "manage_yglu_ecommerce", // Permiso
        YGE_PLUGIN_SLUG, // Link (slug) del side menu
        "yge_render_admin_page", // Función que devolverá contenido a renderizar en la página
        file_get_contents(YGE_PLUGIN_PATH . '/assets/logo.b64'), // Icono del side menu
        6 // Posición en el side menu
    );
}

/**
 * Agregar un permiso para los roles de administrador y editor de forma que puedan acceder a los ajustes
 */
add_action("admin_init", "yge_add_capability");
function yge_add_capability()
{
    $roles = array("administrator", "editor");

    foreach ($roles as $role) {
        $role = get_role($role);
        $role->add_cap("manage_yglu_ecommerce");
    }
}

/**
 *
 */
function yge_render_admin_page()
{
?>
    <div class="wrap">
        <h1>YGLU e-commerce Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('yge_settings'); // Crear el grupo de ajustes para YGLU e-commerce
            do_settings_sections(YGE_PLUGIN_SLUG); // Renderiza todas las secciones que se hayan agregado a la página del slug del plugin
            submit_button();
            ?>
        </form>
    </div>
<?php
}

add_action('admin_init',  'yge_settings_fields');
function yge_settings_fields() // Agregar campos a la página de ajustes
{
    add_settings_section('yge_settings_integration', 'Integración', '', YGE_PLUGIN_SLUG); // Agrega una sección de ajustes

    register_setting('yge_settings', 'api_key'); // Registra un ajuste, para crear su espacio en DB
    add_settings_field( // Campo de la clave API
        'api_key',
        'Clave API',
        'yge_field_input',
        YGE_PLUGIN_SLUG,
        'yge_settings_integration',
        [
            'id' => 'api_key',
            'classes' => ['regular-text'],
            'name' => 'api_key',
            'type' => 'text',
            'placeholder' => 'Clave API de tu cuenta de YGLU',
            'value' => get_option('api_key'),
            'helper' => 'Puedes encontrarla en Configuración de empresa'
        ]
    );

    register_setting('yge_settings', 'show_nif_field');
    register_setting('yge_settings', 'show_nif_field_existent_fieldname');
    add_settings_field( // Selector de agregar campo NIF o no al checkout
        'show_nif_field',
        'Agregar campo NIF',
        'yge_field_radio_with_input',
        YGE_PLUGIN_SLUG,
        'yge_settings_integration',
        [
            'id' => 'show_nif_field',
            'name' => 'show_nif_field',
            'label' => 'Agregar campo NIF',
            'value' => get_option('show_nif_field', 'no'),
            'input_name' => 'show_nif_field_existent_fieldname',
            'input_value' => get_option('show_nif_field_existent_fieldname', ''),
            'options' => [
                ['value' => 'yes', 'title' => 'Sí'],
                ['value' => 'custom', 'title' => 'Usar otro', 'show_input' => true, 'input_label' => 'Nombre del campo:']
            ],
            'helper' => 'Selecciona si deseas que YGLU e-commerce agregue un campo NIF al checkout o usar un campo ya agregado por otro plugin. En este último caso, debes indicar el nombre interno del campo.',
        ]
    );
}

/**
 * Devuelve un campo `input` estándar
 */
function yge_field_input($args)
{
    if (!isset($args['name']) || empty($args['name'])) return;

    $args = wp_parse_args($args, [
        'id' => '',
        'classes' => [],
        'type' => 'text',
        'name' => '',
        'label' => '',
        'placeholder' => '',
        'value' => '',
        'helper' => '',
    ]);
?>

    <div class="yge_settings" id="<?php echo esc_attr($args['id']); ?>">
        <?php if (!empty($args['label'])): ?>
            <label for="<?php echo esc_attr($args['name']); ?>"><?php echo esc_html($args['label']); ?></label><br>
        <?php endif; ?>
        <input
            type="<?php echo esc_attr($args['type']); ?>"
            name="<?php echo esc_attr($args['name']); ?>"
            id="<?php echo esc_attr($args['name']); ?>"
            placeholder="<?php echo esc_attr($args['placeholder']); ?>"
            value="<?php echo esc_attr($args['value']); ?>"
            class="<?php echo esc_attr(implode(' ', $args['classes'])); ?>">
        <?php if (!empty($args['helper'])): ?>
            <br><span class="description"><?php echo esc_html($args['helper']); ?></span><br>
        <?php endif; ?>
    </div>

<?php
}

/**
 * Devuelve un set de radiobuttons
 */
function yge_field_radio($args)
{
    if (!isset($args['name']) || empty($args['name'])) return;

    $args = wp_parse_args($args, [
        'id' => '',
        'name' => '',
        'options' => [],
        'label' => '',
        'value' => '',
        'helper' => '',
    ]);
?>

    <fieldset id="<?php echo $args['id']; ?>">
        <?php if (!empty($args['label'])): ?>
            <legend class="screen-reader-text"><span><?php echo esc_html($args['label']); ?></span></legend>
        <?php endif; ?>

        <?php foreach ($args['options'] as $option):
            $field_id = esc_attr($args['name'] . '_' . $option['value']);
        ?>
            <label for="<?php echo $field_id; ?>">
                <input type="radio"
                    name="<?php echo esc_attr($args['name']); ?>"
                    id="<?php echo $field_id; ?>"
                    value="<?php echo esc_attr($option['value']); ?>"
                    <?php checked($args['value'], $option['value']); ?> />
                <span><?php echo esc_html($option['title']); ?></span>
            </label><br>
        <?php endforeach; ?>

        <?php if (!empty($args['helper'])): ?>
            <span class="description"><?php echo esc_html($args['helper']); ?></span>
        <?php endif; ?>
    </fieldset>

<?php
}

/**
 * Devuelve un set de radiobuttons con opción de que lleven un campo de texto asociado
 */
function yge_field_radio_with_input($args)
{
    if (!isset($args['name']) || empty($args['name'])) return;

    $args = wp_parse_args($args, [
        'id' => '',
        'name' => '',
        'options' => [],
        'label' => '',
        'value' => '',
        'input_name' => '',
        'input_value' => '',
        'helper' => '',
    ]);
?>

    <fieldset id="<?php echo esc_attr($args['id']); ?>">
        <?php if (!empty($args['label'])): ?>
            <legend class="screen-reader-text"><span><?php echo esc_html($args['label']); ?></span></legend>
        <?php endif; ?>

        <?php foreach ($args['options'] as $option):
            $field_id = esc_attr($args['name'] . '_' . $option['value']);
            $is_checked = $args['value'] === $option['value'];
        ?>
            <label for="<?php echo $field_id; ?>">
                <input type="radio"
                    name="<?php echo esc_attr($args['name']); ?>"
                    id="<?php echo $field_id; ?>"
                    value="<?php echo esc_attr($option['value']); ?>"
                    <?php checked($is_checked); ?>
                    class="yge-radio-toggle"
                    data-target="<?php echo isset($option['show_input']) ? esc_attr($args['input_name'] . '_container') : ''; ?>" />
                <span><?php echo esc_html($option['title']); ?></span>
            </label><br>

            <?php if (isset($option['show_input']) && $option['show_input']): ?>
                <div id="<?php echo esc_attr($args['input_name'] . '_container'); ?>"
                    class="yge-dependent-input"
                    style="margin-left: 20px; margin-top: 5px; <?php echo !$is_checked ? 'display: none;' : ''; ?>">
                    <?php if (isset($option['input_label'])): ?>
                        <label for="<?php echo esc_attr($args['input_name']); ?>">
                            <?php echo esc_html($option['input_label']); ?>
                        </label>
                    <?php endif; ?>
                    <input
                        type="text"
                        name="<?php echo esc_attr($args['input_name']); ?>"
                        id="<?php echo esc_attr($args['input_name']); ?>"
                        value="<?php echo esc_attr($args['input_value']); ?>"
                        class="regular-text" />
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!empty($args['helper'])): ?>
            <span class="description"><?php echo esc_html($args['helper']); ?></span>
        <?php endif; ?>
    </fieldset>

    <script>
        jQuery(document).ready(function($) {
            $('.yge-radio-toggle').on('change', function() {
                $('.yge-dependent-input').hide();

                // Mostrar el campo input para el nombre del campo de NIF existente
                var targetId = $(this).data('target');
                if (targetId) {
                    $('#' + targetId).show();
                }
            });
        });
    </script>

<?php
}
