<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
$option_name = 'maxtarget_options';

delete_option($option_name);