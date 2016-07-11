<?php

class maxtargetSettingsPage {
    public $options;
    public $settings_page_name = 'maxtarget_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        $this->options = get_option('maxtarget_options');
    }

    public function add_plugin_page() {
        add_options_page('Settings Admin', 'maxtarget', 'manage_options', $this->settings_page_name, array($this, 'create_admin_page'));
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

        add_settings_field('maxtarget_first', 'Первая настройка', array($this, 'maxtarget_first_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_second', 'Вторая настройка', array($this, 'maxtarget_second_callback'), $this->settings_page_name, 'setting_section_id');

        add_settings_field('maxtarget_result', 'Результат', array($this, 'maxtarget_result_callback'), $this->settings_page_name, 'setting_section_id');
    }

    public function sanitize($input) {
        $new_input = array();

        if (isset($input['maxtarget_first'])) $new_input['maxtarget_first'] = $input['maxtarget_first'];

        if (isset($input['maxtarget_result'])) $new_input['maxtarget_result'] = $input['maxtarget_result'];

        if (isset($input['maxtarget_second'])) $new_input['maxtarget_second'] = $input['maxtarget_second'];

        return $new_input;
    }

    public function print_section_info() {
    }

    public function maxtarget_first_callback() {
        printf('<input type="text" id="maxtarget_first" name="maxtarget_options[maxtarget_first]" value="%s" title="Введите в данном поле Первую настройку"/>', isset($this->options['maxtarget_first']) ? esc_attr($this->options['maxtarget_first']) : '');
    }

    public function maxtarget_second_callback() {
        printf('<input type="text" id="maxtarget_second" name="maxtarget_options[maxtarget_second]" value="%s" title="Введите в данном поле Вторую настройку" />', isset($this->options['maxtarget_second']) ? esc_attr($this->options['maxtarget_second']) : '');
    }

    public function maxtarget_result_callback() {
        printf('<input type="text" id="maxtarget_result" name="maxtarget_options[maxtarget_result]" value="%s" />', isset($this->options['maxtarget_result']) ? esc_attr($this->options['maxtarget_result']) : '');
    }
}

function maxtarget_set_default_options() {
    $options = get_option('maxtarget_options');
    if (is_bool($options)) {
        $options = array();
        $options['maxtarget_first'] = '';
        $options['maxtarget_second'] = '';
        $options['maxtarget_result'] = '';
        update_option('maxtarget_options', $options);
    }
}
