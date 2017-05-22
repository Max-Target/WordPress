<?php
/*
Plugin Name: MaxTarget
Plugin URI: http://maxtarget.ru/
Description: MaxTarget — demo plugin for WordPress.
Version: 1.0.0
Author: maxTarget Team
Author URI: http://maxtarget.ru
*/

include 'maxtarget_options.php';

//добавляем ссылку для настроек на странице выбора плагина
add_filter('plugin_action_links', 'maxtarget_plugin_action_links', 10, 2);

function maxtarget_plugin_action_links($actions, $plugin_file) {
    if (false === strpos($plugin_file, basename(__FILE__))) return $actions;
    $settings_link = '<a href="options-general.php?page=maxtarget_settings">Настройки</a>';
    array_unshift($actions, $settings_link);
    return $actions;
}

add_filter('plugin_row_meta', 'maxtarget_plugin_description_links', 10, 4);

function maxtarget_plugin_description_links($meta, $plugin_file) {
    if (false === strpos($plugin_file, basename(__FILE__))) return $meta;
    $meta[] = '<a href="options-general.php?page=maxtarget_settings">Настройки</a>';
    return $meta;
}

$options = get_option('maxtarget_options');

if (is_admin()) {
    $options = get_option('maxtarget_options');

    if (is_bool($options)) {
        maxtarget_set_default_options();
    }

    $url = get_site_url();

    if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_REQUEST['maxtarget_options']))) {
        //выполняем код с нашими настройками
        //например, результат сохранения настроект, будет операция слияния строк первого и второго в одну
        $options = $_REQUEST['maxtarget_options'];
        update_option('maxtarget_options', $options);
    } else {
        $options = get_option('maxtarget_options');
    }
    $my_settings_page = new maxTargetSettingsPage();
}

