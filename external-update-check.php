<?php
/*
Plugin Name: External Update Check
Description: Provides a secret URL to check for updates to the WordPress core, plugins and themes, without requiring cookie-based authentication. Meant for use in external monitoring services.
Version: 0.5
Author: Claus Conrad
Author URI: http://www.clausconrad.com/
License: BSD 2-Clause

Copyright (c) 2013, Claus Conrad
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

define('EXTERNAL_UPDATE_CHECK_SECRET_OPTION_NAME', 'external_update_check_secret');
define('EXTERNAL_UPDATE_CHECK_URL_DISPLAYED_OPTION_NAME', 'external_update_check_url_displayed');

function _external_update_check_admin_warnings() {
    global $pagenow;
    if (is_admin() && $pagenow == 'plugins.php' && !$_GET['plugin'] && !get_option(EXTERNAL_UPDATE_CHECK_URL_DISPLAYED_OPTION_NAME)) {
        add_action('admin_notices', '_external_update_check_display_private_url');
        update_option(EXTERNAL_UPDATE_CHECK_URL_DISPLAYED_OPTION_NAME, '1');
    }
}

function external_update_check_activate() {
    _external_update_check_set_secret(_external_update_check_generate_secret());
}

function external_update_check_deactivate() {
    _external_update_check_delete_secret();
}

function external_update_check_do() {
    if ($_GET['secret'] === get_option(EXTERNAL_UPDATE_CHECK_SECRET_OPTION_NAME)) {
        require_once(ABSPATH . 'wp-admin/includes/update.php');
        $updates = array();
        $core_updates = get_core_updates();
        $core_updates = array_filter($core_updates, '_external_update_check_filter_core_updates');
        $plugin_updates = get_plugin_updates();
        $theme_updates = get_theme_updates();
        if (count($core_updates)) {
            $updates['core'] = $core_updates;
        }
        if (count($plugin_updates)) {
            $updates['plugins'] = $plugin_updates;
        }
        if (count($theme_updates)) {
            $updates['themes'] = $theme_updates;
        }
        if (count($updates)) {
            header("Content-Type: application/json; charset=UTF-8");
            die(json_encode($updates));
        }
    }
    die('0');
}

function _external_update_check_filter_core_updates($core_update) {
    return $core_update->response !== 'latest';
}

function _external_update_check_display_private_url() {
    echo '<div class="updated"><p><strong>Your private update check URL is:</strong></p><p><a href="' . admin_url('admin-ajax.php?action=externalUpdateCheck&secret=' . _external_update_check_get_secret()) . '">' . admin_url('admin-ajax.php?action=externalUpdateCheck&secret=' . _external_update_check_get_secret()) . '</a></p><p>This URL will only be displayed this once.</p></div>';
}

function _external_update_check_get_secret() {
    return get_option(EXTERNAL_UPDATE_CHECK_SECRET_OPTION_NAME);
}

function _external_update_check_set_secret($secret) {
    update_option(EXTERNAL_UPDATE_CHECK_SECRET_OPTION_NAME, $secret);
}

function _external_update_check_generate_secret() {
    $length = 32;
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;
}

function _external_update_check_delete_secret() {
    delete_option(EXTERNAL_UPDATE_CHECK_SECRET_OPTION_NAME);
    delete_option(EXTERNAL_UPDATE_CHECK_URL_DISPLAYED_OPTION_NAME);
}

register_activation_hook(__FILE__, 'external_update_check_activate');
register_deactivation_hook(__FILE__, 'external_update_check_deactivate');
register_uninstall_hook(__FILE__, 'external_update_check_deactivate');
add_action('wp_ajax_nopriv_externalUpdateCheck', 'external_update_check_do');
add_action('wp_ajax_externalUpdateCheck', 'external_update_check_do');
_external_update_check_admin_warnings();
