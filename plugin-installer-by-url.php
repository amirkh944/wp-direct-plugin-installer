<?php
/**
 * Plugin Name: نصب افزونه با لینک
 * Plugin URI: https://example.com
 * Description: امکان نصب و آپدیت افزونه‌های وردپرس با استفاده از URL
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: plugin-installer-by-url
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PIBU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIBU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PIBU_VERSION', '1.0.0');

class PluginInstallerByURL {
    
    public function __construct() {
        add_action('admin_init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_install_plugin_by_url', array($this, 'ajax_install_plugin'));
        add_action('wp_ajax_nopriv_install_plugin_by_url', array($this, 'ajax_install_plugin'));
    }
    
    public function init() {
        // Add button to plugin installation page
        add_action('install_plugins_upload', array($this, 'add_install_button'));
        add_action('admin_head-plugin-install.php', array($this, 'add_button_styles'));
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'plugin-install.php') {
            return;
        }
        
        wp_enqueue_script(
            'pibu-script',
            PIBU_PLUGIN_URL . 'assets/js/plugin-installer.js',
            array('jquery'),
            PIBU_VERSION,
            true
        );
        
        wp_localize_script('pibu-script', 'pibu_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pibu_nonce'),
            'installing_text' => __('در حال نصب...', 'plugin-installer-by-url'),
            'success_text' => __('افزونه با موفقیت نصب شد!', 'plugin-installer-by-url'),
            'error_text' => __('خطا در نصب افزونه', 'plugin-installer-by-url'),
            'invalid_url_text' => __('لینک وارد شده معتبر نیست', 'plugin-installer-by-url'),
            'enter_url_text' => __('لطفاً لینک افزونه را وارد کنید', 'plugin-installer-by-url')
        ));
        
        wp_enqueue_style(
            'pibu-style',
            PIBU_PLUGIN_URL . 'assets/css/plugin-installer.css',
            array(),
            PIBU_VERSION
        );
    }
    
    public function add_install_button() {
        ?>
        <style>
        .pibu-install-section {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .pibu-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .pibu-button:hover {
            background: #005a87;
        }
        .pibu-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .pibu-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 4px;
            width: 500px;
            max-width: 90%;
        }
        .pibu-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .pibu-close:hover {
            color: black;
        }
        .pibu-url-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 3px;
            direction: ltr;
        }
        .pibu-install-btn {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
        }
        .pibu-install-btn:hover {
            background: #005a87;
        }
        .pibu-install-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .pibu-progress {
            display: none;
            margin: 10px 0;
        }
        .pibu-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
            display: none;
        }
        .pibu-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .pibu-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        </style>
        
        <div class="pibu-install-section">
            <h3>نصب افزونه با لینک</h3>
            <p>شما می‌توانید افزونه را مستقیماً از طریق لینک دانلود نصب کنید.</p>
            <button type="button" class="pibu-button" id="pibu-open-modal">افزودن با لینک</button>
        </div>
        
        <!-- Modal -->
        <div id="pibu-modal" class="pibu-modal">
            <div class="pibu-modal-content">
                <span class="pibu-close">&times;</span>
                <h3>نصب افزونه از طریق لینک</h3>
                <p>لینک مستقیم دانلود افزونه (فایل ZIP) را وارد کنید:</p>
                <input type="url" id="pibu-url-input" class="pibu-url-input" placeholder="https://example.com/plugin.zip" />
                <div class="pibu-progress">
                    <progress style="width: 100%; height: 20px;"></progress>
                </div>
                <div id="pibu-message" class="pibu-message"></div>
                <button type="button" id="pibu-install-btn" class="pibu-install-btn">نصب افزونه</button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var modal = $('#pibu-modal');
            var btn = $('#pibu-open-modal');
            var span = $('.pibu-close');
            var installBtn = $('#pibu-install-btn');
            var urlInput = $('#pibu-url-input');
            var progress = $('.pibu-progress');
            var message = $('#pibu-message');
            
            btn.on('click', function() {
                modal.show();
                urlInput.focus();
            });
            
            span.on('click', function() {
                modal.hide();
                resetModal();
            });
            
            $(window).on('click', function(event) {
                if (event.target == modal[0]) {
                    modal.hide();
                    resetModal();
                }
            });
            
            installBtn.on('click', function() {
                var url = urlInput.val().trim();
                
                if (!url) {
                    showMessage(pibu_ajax.enter_url_text, 'error');
                    return;
                }
                
                if (!isValidUrl(url)) {
                    showMessage(pibu_ajax.invalid_url_text, 'error');
                    return;
                }
                
                installPlugin(url);
            });
            
            urlInput.on('keypress', function(e) {
                if (e.which == 13) {
                    installBtn.click();
                }
            });
            
            function isValidUrl(string) {
                try {
                    new URL(string);
                    return string.toLowerCase().endsWith('.zip');
                } catch (_) {
                    return false;
                }
            }
            
            function installPlugin(url) {
                installBtn.prop('disabled', true);
                installBtn.text(pibu_ajax.installing_text);
                progress.show();
                hideMessage();
                
                $.ajax({
                    url: pibu_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'install_plugin_by_url',
                        plugin_url: url,
                        nonce: pibu_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(pibu_ajax.success_text, 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data || pibu_ajax.error_text, 'error');
                        }
                    },
                    error: function() {
                        showMessage(pibu_ajax.error_text, 'error');
                    },
                    complete: function() {
                        installBtn.prop('disabled', false);
                        installBtn.text('نصب افزونه');
                        progress.hide();
                    }
                });
            }
            
            function showMessage(text, type) {
                message.removeClass('pibu-success pibu-error')
                       .addClass('pibu-' + type)
                       .text(text)
                       .show();
            }
            
            function hideMessage() {
                message.hide();
            }
            
            function resetModal() {
                urlInput.val('');
                installBtn.prop('disabled', false);
                installBtn.text('نصب افزونه');
                progress.hide();
                hideMessage();
            }
        });
        </script>
        <?php
    }
    
    public function add_button_styles() {
        ?>
        <style>
        .upload-plugin .wp-upload-form {
            position: relative;
        }
        .pibu-add-button {
            margin-right: 10px;
        }
        </style>
        <?php
    }
    
    public function ajax_install_plugin() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pibu_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('install_plugins')) {
            wp_send_json_error('شما مجوز نصب افزونه ندارید');
            return;
        }
        
        $plugin_url = sanitize_url($_POST['plugin_url']);
        
        if (empty($plugin_url)) {
            wp_send_json_error('لینک افزونه وارد نشده است');
            return;
        }
        
        // Validate URL
        if (!filter_var($plugin_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('لینک وارد شده معتبر نیست');
            return;
        }
        
        // Check if URL ends with .zip
        if (!preg_match('/\.zip$/i', $plugin_url)) {
            wp_send_json_error('فایل باید از نوع ZIP باشد');
            return;
        }
        
        // Include required files
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        
        // Create a temporary file
        $temp_file = download_url($plugin_url);
        
        if (is_wp_error($temp_file)) {
            wp_send_json_error('خطا در دانلود فایل: ' . $temp_file->get_error_message());
            return;
        }
        
        // Install plugin
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install($temp_file);
        
        // Clean up temp file
        @unlink($temp_file);
        
        if (is_wp_error($result)) {
            wp_send_json_error('خطا در نصب افزونه: ' . $result->get_error_message());
            return;
        }
        
        if ($result === false) {
            wp_send_json_error('نصب افزونه با شکست مواجه شد');
            return;
        }
        
        wp_send_json_success('افزونه با موفقیت نصب شد');
    }
}

// Initialize the plugin
new PluginInstallerByURL();

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pibu_add_settings_link');
function pibu_add_settings_link($links) {
    $settings_link = '<a href="plugin-install.php">نصب افزونه</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Load text domain for translations
add_action('plugins_loaded', 'pibu_load_textdomain');
function pibu_load_textdomain() {
    load_plugin_textdomain('plugin-installer-by-url', false, dirname(plugin_basename(__FILE__)) . '/languages');
}