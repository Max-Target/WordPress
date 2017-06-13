<?php

class MxTrSettingsPage {
    public $options;
    public $settings_page_name = 'maxtarget_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'mxtr_add_plugin_page'));
        add_action('admin_init', array($this, 'mxtr_page_init'));
        add_action('admin_enqueue_scripts', array($this, 'mxtr_admin_styles'));
        $this->options = get_option('maxtarget_options');
    }

    public function mxtr_add_plugin_page() {
        add_options_page('MaxTarget Settings', 'MaxTarget', 'manage_options', $this->settings_page_name, array(
            $this,
            'mxtr_create_admin_page'));
    }


    public function mxtr_admin_styles() {
        wp_register_style('mxtr_admin_menu_styles', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/css/maxtarget.css', array());
        wp_enqueue_style('mxtr_admin_menu_styles');
    }

    public function mxtr_create_admin_page() {
        $this->options = get_option('maxtarget_options');
        ?>
        <div class="wrap">
            <div id="wrapper">
                <style>
                    .maxtarget-toggler {
                        margin: 20px 0;
                    }

                    .maxtarget-toggler .maxtarget-toggler-button {
                        color: gray;
                        border-bottom: 1px dashed gray;
                        display: inline-block;
                        cursor: pointer;
                    }

                    .maxtarget-toggler .maxtarget-toggler-button span {
                        line-height: 10px;
                        display: inline-block;
                        height: 10px;
                    }

                    .maxtarget-toggler .maxtarget-toggler-button.collapsed .maxtarget-toggler-arrow {
                        -webkit-transform: rotate(180deg);
                        -moz-transform: rotate(180deg);
                        -ms-transform: rotate(180deg);
                        -o-transform: rotate(180deg);
                        transform: rotate(180deg);
                    }

                    .show-on-click {
                        margin: 20px;
                    }

                    .maxtarget_label {
                        font-weight: bold;
                    }
                </style>
                <form id="settings_form" method="post"
                      action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                    <h1>Плагин MaxTarget</h1>
                    <?php
                    settings_fields('maxtarget_option_group');
                    do_settings_sections('maxtarget_settings');
                    ?>

                    <div class="maxtarget-toggler">
                        <div id="showHiddenButton" class="maxtarget-toggler-button">
                            Расширенные настройки
                            <span class="maxtarget-toggler-arrow"> ⌵</span>
                        </div>
                        <script>
                            (function ($) {
                                $(document).ready(function () {

                                    $('#showHiddenButton').on('click', function (e) {
                                        e.preventDefault();
                                        $(this).toggleClass('collapsed');
                                        $('.show-on-click').toggleClass('hidden');
                                    });

                                });
                            })(jQuery);
                        </script>
                    </div>
                    <div class="show-on-click hidden">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>
                                    <label for="maxtarget_multi_site" class="maxtarget_label">Multi Site:</label>
                                    <p class="description">Для нескольких доменов на одном сервере</p>
                                </th>
                                <th>
                                    <?php
                                    if ($this->options['maxtarget_multi_site'] == '1') {
                                        $ms_checked = ' checked="checked" ';
                                    }
                                    echo "<input " . $ms_checked . " id='maxtarget_multi_site' value='1' name='maxtarget_options[maxtarget_multi_site]' type='checkbox' />";
                                    ?>
                                </th>
                            </tr>
                            <tr>
                                <th>
                                    <label for="maxtarget_debug" class="maxtarget_label">Отладка:</label>
                                    <p class="description">Для разработчиков</p>
                                </th>
                                <th>
                                    <?php
                                    if ($this->options['maxtarget_debug'] == '1') {
                                        $debug_checked = ' checked="checked" ';
                                    }
                                    echo "<input " . $debug_checked . " id='maxtarget_debug' value='1' name='maxtarget_options[maxtarget_debug]' type='checkbox' />";
                                    ?>
                                </th>
                            </tr>
                            <tr>
                                <th>
                                    <label for="maxtarget_force_code" class="maxtarget_label">Force Code (не
                                        рекомендуется):</label>
                                    <p class="description">Отображение маркеров индексации</p>
                                </th>
                                <th>
                                    <?php
                                    if ($this->options['maxtarget_force_code'] == '1') {
                                        $fc_checked = ' checked="checked" ';
                                    }
                                    echo "<input " . $fc_checked . " id='maxtarget_force_code' value='1' name='maxtarget_options[maxtarget_force_code]' type='checkbox' />";
                                    ?>
                                </th>
                            </tr>
                            <tr>
                                <th>
                                    <label for="maxtarget_custom" class="maxtarget_label">Доп. параметры (не
                                        рекомендуется):</label>
                                    <p class="description">Для разработчиков</p>
                                </th>
                                <th>
                                    <?php
                                    echo "<input id='maxtarget_custom' name='maxtarget_options[maxtarget_custom]' type='text' value='" . esc_attr($this->options['maxtarget_custom']) . "'/>";
                                    ?>
                                </th>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="submit" name="submit_btn" value="Сохранить настройки">
                </form>
            </div>
        </div>
        <?php
    }

    public function mxtr_page_init() {
        register_setting('maxtarget_option_group', 'maxtarget_options', array($this, 'mxtr_sanitize'));

        add_settings_section('setting_section_id', '', array($this, 'mxtr_print_section_info'), $this->settings_page_name);

        add_settings_field('maxtarget_key', 'Ключ', array(
            $this,
            'mxtr_key_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_encode', 'Кодировка сайта', array(
            $this,
            'mxtr_encode_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_size', 'Размер баннера по умолчанию', array(
            $this,
            'mxtr_size_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_user_code', 'Код рекламной сети', array(
            $this,
            'mxtr_user_code_callback'), $this->settings_page_name, 'setting_section_id');
    }

    public function mxtr_sanitize($input) {
        $new_input = array();

        if (isset($input['maxtarget_key']))
            $new_input['maxtarget_key'] = esc_attr($input['maxtarget_key']);

        if (isset($input['maxtarget_encode']))
            $new_input['maxtarget_encode'] = $input['maxtarget_encode'];

        if (isset($input['maxtarget_size']))
            $new_input['maxtarget_size'] = $input['maxtarget_size'];

        if (isset($input['maxtarget_debug']))
            $new_input['maxtarget_debug'] = $input['maxtarget_debug'];

        if (isset($input['maxtarget_force_code']))
            $new_input['maxtarget_force_code'] = $input['maxtarget_force_code'];

        if (isset($input['maxtarget_multi_site']))
            $new_input['maxtarget_multi_site'] = $input['maxtarget_multi_site'];

        if (isset($input['maxtarget_user_code']))
            $new_input['maxtarget_user_code'] = esc_js($input['maxtarget_user_code']);

        if (isset($input['maxtarget_custom']))
            $new_input['maxtarget_custom'] = esc_attr($input['maxtarget_custom']);

        return $new_input;
    }

    public function mxtr_print_section_info() {
    }

    public function mxtr_key_callback() {
        printf('<input type="text" id="maxtarget_key" name="maxtarget_options[maxtarget_key]" size="45" value="%s" title="Введите ключ"/>', isset($this->options['maxtarget_key']) ? esc_attr($this->options['maxtarget_key']) : '');
    }

    public function mxtr_encode_callback() {
        $options = get_option('maxtarget_options');
        $items = array('UTF-8', 'KOI8-R', 'Windows-1251');
        echo "<select id='maxtarget_encode' name='maxtarget_options[maxtarget_encode]'>";
        foreach ($items as $item) {
            $selected = ($options['maxtarget_encode'] == $item) ? 'selected="selected"' : '';
            echo "<option value='$item' $selected>$item</option>";
        }
        echo "</select>";
    }

    public function mxtr_size_callback() {
        $options = get_option('maxtarget_options');
        $items = array('240x400', '300x250', '728x90');
        echo "<select id='maxtarget_size' name='maxtarget_options[maxtarget_size]'>";
        foreach ($items as $item) {
            $selected = ($options['maxtarget_size'] == $item) ? 'selected="selected"' : '';
            echo "<option value='$item' $selected>$item</option>";
        }
        echo "</select>";
    }

    public function mxtr_user_code_callback() {
        $desc = 'Вставьте код рекламной сети, который будет демонстрироваться в отсутствии рекламы MaxTarget. Это необходимо для сохранения вашего заработка: если в данный момент нет заявок на размещение баннеров - вы продолжите зарабатывать на текущих рекламных сетях. Поддерживаются все виды рекламных сетей: рекламная сеть Яндекса, Google AdSence, тизерные и RTB сети.';
        printf('<textarea type="text" id="maxtarget_user_code" name="maxtarget_options[maxtarget_user_code]" rows="6" cols="45" placeholder="Скопируйте сюда js код вашей рекламной сети" title="%s">%s</textarea>', $desc, isset($this->options['maxtarget_user_code']) ? esc_attr($this->options['maxtarget_user_code']) : '');
    }
}

function mxtr_set_default_options() {
    $options = get_option('maxtarget_options');
    if (is_bool($options)) {
        $options = array();
        $options['maxtarget_key'] = '';
        $options['maxtarget_encode'] = 'UTF-8';
        $options['maxtarget_size'] = '240x400';
        $options['maxtarget_debug'] = '0';
        $options['maxtarget_force_code'] = '0';
        $options['maxtarget_multi_site'] = '0';
        $options['maxtarget_user_code'] = '';
        $options['maxtarget_custom'] = '';

        update_option('maxtarget_options', $options);
    }
}

function mxtr_shortcode() {
    return mxtr_get_code();
}

function mxtr_shortcode1() {
    return mxtr_get_code('240x400');
}

function mxtr_shortcode2() {
    return mxtr_get_code('300x250');
}

function mxtr_shortcode3() {
    return mxtr_get_code('728x90');
}

add_shortcode('maxtarget', 'mxtr_shortcode');
add_shortcode('maxtarget240x400', 'mxtr_shortcode1');
add_shortcode('maxtarget300x250', 'mxtr_shortcode2');
add_shortcode('maxtarget728x90', 'mxtr_shortcode3');

function mxtr_get_code($size = '') {
    $options = get_option('maxtarget_options');

    $o['key'] = $options['maxtarget_key'];
    $o['charset'] = $options['maxtarget_encode'];

    if ($options['maxtarget_debug'] == 1) {
        $o['debug'] = 'true';
    }
    else $o['debug'] = 'false';

    if ($options['maxtarget_force_code'] == 1) {
        $o['force_show_code'] = 'true';
    }
    else $o['force_show_code'] = 'false';

    if ($options['maxtarget_multi_site'] == 1) {
        $o['multi_site'] = true;
    }
    else $o['multi_site'] = 'false';

    if (!$size) {
        $size = $options['maxtarget_size'];
    }

    if ($options['maxtarget_custom']) {
        $data = unserialize($options['maxtarget_custom']);
        if ($data) {
            array_merge($data, $o);
        }
    }

    require_once('maxtarget_api.php');
    $maxtarget = new MxTrClient($o);
    unset($o);
    return $maxtarget->mxtr_show_banner($size, wp_specialchars_decode($options['maxtarget_user_code']));
}

add_action('admin_menu', 'mxtr_admin_actions');

function mxtr_admin_actions() {
    if (current_user_can('manage_options')) {
        if (function_exists('add_meta_box')) {
            add_menu_page('MaxTarget Settings', 'MaxTarget', 'manage_options', 'maxtarget_settings', 'mxtr_custom_menu_page', null, 100);
        }
    }
}

function mxtr_custom_menu_page() {
    $mxtr_settings_page = new MxTrSettingsPage();
    if (!isset($mxtr_settings_page)) {
        wp_die(__('Plugin maxTarget has been installed incorrectly.'));
    }
    if (function_exists('add_plugins_page')) {
        add_plugins_page('maxTarget Settings', 'MaxTarget', 'manage_options', 'maxtarget_settings', array(
            &$mxtr_settings_page,
            'mxtr_create_admin_page'));
    }
}