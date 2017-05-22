<?php

class maxTargetSettingsPage {
    public $options;
    public $settings_page_name = 'maxtarget_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        $this->options = get_option('maxtarget_options');
    }

    public function add_plugin_page() {
        add_options_page('Settings Admin', 'maxTarget', 'manage_options', $this->settings_page_name, array(
            $this,
            'create_admin_page'));
    }

    public function create_admin_page() {
        $this->options = get_option('maxtarget_options');
        ?>
        <div class="wrap">
            <div id="wrapper">
                <form id="settings_form" method="post"
                      action="<?php echo $_SERVER['REQUEST_URI'] ?>">
                    <h1>Плагин maxtarget</h1>
                    <?php
                    settings_fields('maxtarget_option_group');
                    do_settings_sections('maxtarget_settings');
                    ?>
                    <input type="submit" name="submit_btn" value="Save">
                </form>
            </div>
        </div>
        <?php
    }

    public function page_init() {
        register_setting('maxtarget_option_group', 'maxtarget_options', array($this, 'sanitize'));

        add_settings_section('setting_section_id', '', // Title
            array($this, 'print_section_info'), $this->settings_page_name);

        add_settings_field('maxtarget_key', 'Ключ', array(
            $this,
            'maxtarget_key_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_encode', 'Кодировка сайта', array(
            $this,
            'maxtarget_encode_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_result', 'Третья', array(
            $this,
            'maxtarget_result_callback'), $this->settings_page_name, 'setting_section_id');
    }

    public function sanitize($input) {
        $new_input = array();

        if (isset($input['maxtarget_key']))
            $new_input['maxtarget_key'] = $input['maxtarget_key'];

        if (isset($input['maxtarget_encode']))
            $new_input['maxtarget_encode'] = $input['maxtarget_encode'];

        if (isset($input['maxtarget_result']))
            $new_input['maxtarget_result'] = $input['maxtarget_result'];

        return $new_input;
    }

    public function print_section_info() {
    }

    public function maxtarget_key_callback() {
        printf('<input type="text" id="maxtarget_key" name="maxtarget_options[maxtarget_key]" value="%s" title="Введите ключ"/>', isset($this->options['maxtarget_key']) ? esc_attr($this->options['maxtarget_key']) : '');
    }

    public function maxtarget_encode_callback() {
        printf('<input type="text" id="maxtarget_encode" name="maxtarget_options[maxtarget_encode]" value="%s" title="Введите кодировку сайта" />', isset($this->options['maxtarget_encode']) ? esc_attr($this->options['maxtarget_encode']) : 'UTF-8');
    }

    public function maxtarget_result_callback() {
        printf('<input type="text" id="maxtarget_result" name="maxtarget_options[maxtarget_result]" value="%s" />', isset($this->options['maxtarget_result']) ? esc_attr($this->options['maxtarget_result']) : '');
    }
}

function maxtarget_set_default_options() {
    $options = get_option('maxtarget_options');
    if (is_bool($options)) {
        $options = array();
        $options['maxtarget_key'] = '';
        $options['maxtarget_encode'] = 'UTF-8';
        $options['maxtarget_result'] = '';
        update_option('maxtarget_options', $options);
    }
}

function maxtarget_shortcode() {
    return get_maxtarget_code();
}

add_shortcode('maxtarget', 'maxtarget_shortcode');

function get_maxtarget_code() {
    $options = get_option('maxtarget_options');
    $key = $options['maxtarget_key'];
    define('MT_USER', $key);
    require_once('maxtarget_api.php');
    $o['charset'] = $options['maxtarget_encode']; // кодировка сайта
    $maxtarget = new MaxtargetClient($o);
    unset($o);
    return $maxtarget->show_banner('300x250'); //размер баннера
}