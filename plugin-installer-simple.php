<?php
/**
 * Plugin Name: نصب افزونه با لینک (نسخه ساده)
 * Plugin URI: https://example.com
 * Description: امکان نصب و آپدیت افزونه‌های وردپرس با استفاده از URL - نسخه ساده و بدون خطا
 * Version: 1.5.0
 * Author: Your Name
 * Text Domain: plugin-installer-simple
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PIBU_SIMPLE_VERSION', '1.5.0');

// Main plugin class
class PluginInstallerSimple {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_head-plugin-install.php', array($this, 'add_header_button'));
        add_action('admin_footer-plugin-install.php', array($this, 'add_modal_html'));
        add_action('wp_ajax_install_plugin_simple', array($this, 'ajax_install_plugin'));
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'plugin-install.php') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_localize_script('jquery', 'pibu_simple_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pibu_simple_nonce'),
            'installing_text' => 'در حال نصب...',
            'success_text' => 'افزونه با موفقیت نصب شد!',
            'error_text' => 'خطا در نصب افزونه',
            'invalid_url_text' => 'لینک وارد شده معتبر نیست',
            'enter_url_text' => 'لطفاً لینک افزونه را وارد کنید'
        ));
    }
    
    public function add_header_button() {
        ?>
        <style>
        .page-title-action.pibu-simple-btn {
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            text-shadow: none;
            display: inline-block;
            margin-right: 10px;
            padding: 6px 10px;
            font-size: 13px;
            line-height: 2.15384615;
            border-radius: 3px;
            border: 1px solid;
            cursor: pointer;
        }
        .page-title-action.pibu-simple-btn:hover {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
        }
        .pibu-simple-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .pibu-simple-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        }
        .pibu-simple-close {
            color: #666;
            float: left;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -10px;
        }
        .pibu-simple-close:hover { color: #000; }
        .pibu-simple-url-input {
            width: 100%;
            padding: 8px 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            direction: ltr;
            font-size: 14px;
            box-sizing: border-box;
        }
        .pibu-simple-url-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        .pibu-simple-buttons { margin-top: 20px; text-align: left; }
        .pibu-simple-install-btn {
            background: #2271b1;
            color: white;
            border: 1px solid #2271b1;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .pibu-simple-install-btn:hover {
            background: #135e96;
            border-color: #135e96;
        }
        .pibu-simple-install-btn:disabled {
            background: #f0f0f1;
            color: #a7aaad;
            border-color: #dcdcde;
            cursor: not-allowed;
        }
        .pibu-simple-cancel-btn {
            background: #f6f7f7;
            color: #2c3338;
            border: 1px solid #dcdcde;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .pibu-simple-cancel-btn:hover { background: #f0f0f1; }
        .pibu-simple-progress {
            display: none;
            margin: 15px 0;
            text-align: center;
        }
        .pibu-simple-message {
            margin: 15px 0;
            padding: 12px;
            border-radius: 4px;
            display: none;
        }
        .pibu-simple-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        .pibu-simple-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .pibu-simple-help-text {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var addNewBtn = $('.page-title-action').first();
            if (addNewBtn.length) {
                var addLinkBtn = $('<a href="#" class="page-title-action pibu-simple-btn">افزودن با لینک</a>');
                addLinkBtn.insertAfter(addNewBtn);
                
                addLinkBtn.on('click', function(e) {
                    e.preventDefault();
                    $('#pibu-simple-modal').show();
                    $('#pibu-simple-url-input').focus();
                });
            }
        });
        </script>
        <?php
    }
    
    public function add_modal_html() {
        ?>
        <div id="pibu-simple-modal" class="pibu-simple-modal">
            <div class="pibu-simple-modal-content">
                <span class="pibu-simple-close">&times;</span>
                <h2>نصب افزونه از طریق لینک</h2>
                <p>لینک مستقیم دانلود افزونه (فایل ZIP) را وارد کنید:</p>
                <input type="url" id="pibu-simple-url-input" class="pibu-simple-url-input" placeholder="https://example.com/plugin.zip" />
                <div class="pibu-simple-help-text">
                    مثال: https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip
                </div>
                
                <div class="pibu-simple-progress">
                    <p>در حال پردازش... لطفاً صبر کنید</p>
                </div>
                
                <div id="pibu-simple-message" class="pibu-simple-message"></div>
                
                <div class="pibu-simple-buttons">
                    <button type="button" id="pibu-simple-install-btn" class="pibu-simple-install-btn">نصب افزونه</button>
                    <button type="button" id="pibu-simple-cancel-btn" class="pibu-simple-cancel-btn">انصراف</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var modal = $('#pibu-simple-modal');
            var closeBtn = $('.pibu-simple-close');
            var cancelBtn = $('#pibu-simple-cancel-btn');
            var installBtn = $('#pibu-simple-install-btn');
            var urlInput = $('#pibu-simple-url-input');
            var progress = $('.pibu-simple-progress');
            var message = $('#pibu-simple-message');
            
            closeBtn.on('click', closeModal);
            cancelBtn.on('click', closeModal);
            
            $(window).on('click', function(event) {
                if (event.target == modal[0]) {
                    closeModal();
                }
            });
            
            installBtn.on('click', function() {
                var url = urlInput.val().trim();
                
                if (!url) {
                    showMessage(pibu_simple_ajax.enter_url_text, 'error');
                    return;
                }
                
                if (!isValidUrl(url)) {
                    showMessage(pibu_simple_ajax.invalid_url_text, 'error');
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
                    var url = new URL(string);
                    return url.protocol === 'http:' || url.protocol === 'https:';
                } catch (_) {
                    return false;
                }
            }
            
            function installPlugin(url) {
                installBtn.prop('disabled', true);
                installBtn.text(pibu_simple_ajax.installing_text);
                progress.show();
                hideMessage();
                
                $.ajax({
                    url: pibu_simple_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'install_plugin_simple',
                        plugin_url: url,
                        nonce: pibu_simple_ajax.nonce
                    },
                    timeout: 120000,
                    success: function(response) {
                        if (response.success) {
                            showMessage(pibu_simple_ajax.success_text + '\n\nصفحه در حال بارگذاری مجدد...', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            showMessage(response.data || pibu_simple_ajax.error_text, 'error');
                        }
                    },
                    error: function() {
                        showMessage(pibu_simple_ajax.error_text, 'error');
                    },
                    complete: function() {
                        installBtn.prop('disabled', false);
                        installBtn.text('نصب افزونه');
                        progress.hide();
                    }
                });
            }
            
            function showMessage(text, type) {
                message.removeClass('pibu-simple-success pibu-simple-error')
                       .addClass('pibu-simple-' + type)
                       .text(text)
                       .show();
            }
            
            function hideMessage() {
                message.hide();
            }
            
            function closeModal() {
                modal.hide();
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
    
    public function ajax_install_plugin() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pibu_simple_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('install_plugins')) {
            wp_send_json_error('شما مجوز نصب افزونه ندارید');
            return;
        }
        
        $plugin_url = isset($_POST['plugin_url']) ? sanitize_url($_POST['plugin_url']) : '';
        
        if (empty($plugin_url)) {
            wp_send_json_error('لینک افزونه وارد نشده است');
            return;
        }
        
        // Validate URL
        if (!filter_var($plugin_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('لینک وارد شده معتبر نیست');
            return;
        }
        
        // Include WordPress files
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        
        // Test URL first
        $response = wp_remote_head($plugin_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('لینک در دسترس نیست: ' . $response->get_error_message());
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wp_send_json_error('لینک در دسترس نیست (کد خطا: ' . $response_code . ')');
            return;
        }
        
        // Download file
        $temp_file = download_url($plugin_url, 300);
        
        if (is_wp_error($temp_file)) {
            wp_send_json_error('خطا در دانلود فایل: ' . $temp_file->get_error_message());
            return;
        }
        
        // Check file
        if (!file_exists($temp_file) || filesize($temp_file) < 100) {
            @unlink($temp_file);
            wp_send_json_error('فایل دانلود شده معتبر نیست');
            return;
        }
        
        // Initialize filesystem
        global $wp_filesystem;
        if (!WP_Filesystem()) {
            @unlink($temp_file);
            wp_send_json_error('خطا در دسترسی به سیستم فایل');
            return;
        }
        
        // Install plugin
        $upgrader = new Plugin_Upgrader();
        $result = $upgrader->install($temp_file);
        
        // Clean up
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

// Initialize plugin
new PluginInstallerSimple();

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="plugin-install.php">نصب افزونه</a>';
    array_unshift($links, $settings_link);
    return $links;
});