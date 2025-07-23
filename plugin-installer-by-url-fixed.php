<?php
/**
 * Plugin Name: نصب افزونه با لینک (نسخه رفع شده)
 * Plugin URI: https://example.com
 * Description: امکان نصب و آپدیت افزونه‌های وردپرس با استفاده از URL - نسخه رفع شده
 * Version: 1.2.0
 * Author: Your Name
 * Text Domain: plugin-installer-by-url-fixed
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PIBU_FIXED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIBU_FIXED_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PIBU_FIXED_VERSION', '1.2.0');

class PluginInstallerByURLFixed {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_install_plugin_by_url_fixed', array($this, 'ajax_install_plugin'));
        // Enable debug logging
        add_action('wp_ajax_debug_plugin_install', array($this, 'debug_install'));
    }
    
    public function init() {
        // Add button to plugin installation page header
        add_action('admin_head-plugin-install.php', array($this, 'add_header_button'));
        add_action('admin_footer-plugin-install.php', array($this, 'add_modal_html'));
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'plugin-install.php') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_localize_script('jquery', 'pibu_fixed_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pibu_fixed_nonce'),
            'installing_text' => 'در حال نصب...',
            'success_text' => 'افزونه با موفقیت نصب شد!',
            'error_text' => 'خطا در نصب افزونه',
            'invalid_url_text' => 'لینک وارد شده معتبر نیست',
            'enter_url_text' => 'لطفاً لینک افزونه را وارد کنید',
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }
    
    public function add_header_button() {
        ?>
        <style>
        .page-title-action.pibu-fixed-add-link-btn {
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
        .page-title-action.pibu-fixed-add-link-btn:hover {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
        }
        
        /* Modal Styles */
        .pibu-fixed-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .pibu-fixed-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        }
        
        .pibu-fixed-close {
            color: #666;
            float: left;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -10px;
        }
        
        .pibu-fixed-close:hover {
            color: #000;
        }
        
        .pibu-fixed-url-input {
            width: 100%;
            padding: 8px 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            direction: ltr;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .pibu-fixed-url-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .pibu-fixed-buttons {
            margin-top: 20px;
            text-align: left;
        }
        
        .pibu-fixed-install-btn {
            background: #2271b1;
            color: white;
            border: 1px solid #2271b1;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .pibu-fixed-install-btn:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        .pibu-fixed-install-btn:disabled {
            background: #f0f0f1;
            color: #a7aaad;
            border-color: #dcdcde;
            cursor: not-allowed;
        }
        
        .pibu-fixed-cancel-btn {
            background: #f6f7f7;
            color: #2c3338;
            border: 1px solid #dcdcde;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .pibu-fixed-cancel-btn:hover {
            background: #f0f0f1;
        }
        
        .pibu-fixed-progress {
            display: none;
            margin: 15px 0;
            text-align: center;
        }
        
        .pibu-fixed-message {
            margin: 15px 0;
            padding: 12px;
            border-radius: 4px;
            display: none;
        }
        
        .pibu-fixed-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .pibu-fixed-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        
        .pibu-fixed-debug {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        
        .pibu-fixed-help-text {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add button next to "Add New" button
            var addNewBtn = $('.page-title-action').first();
            if (addNewBtn.length) {
                var addLinkBtn = $('<a href="#" class="page-title-action pibu-fixed-add-link-btn">افزودن با لینک</a>');
                addLinkBtn.insertAfter(addNewBtn);
                
                addLinkBtn.on('click', function(e) {
                    e.preventDefault();
                    $('#pibu-fixed-modal').show();
                    $('#pibu-fixed-url-input').focus();
                });
            }
        });
        </script>
        <?php
    }
    
    public function add_modal_html() {
        ?>
        <!-- Modal -->
        <div id="pibu-fixed-modal" class="pibu-fixed-modal">
            <div class="pibu-fixed-modal-content">
                <span class="pibu-fixed-close">&times;</span>
                <h2>نصب افزونه از طریق لینک</h2>
                <p>لینک مستقیم دانلود افزونه (فایل ZIP) را وارد کنید:</p>
                <input type="url" id="pibu-fixed-url-input" class="pibu-fixed-url-input" placeholder="https://example.com/plugin.zip" />
                <div class="pibu-fixed-help-text">
                    مثال: https://downloads.wordpress.org/plugin/akismet.5.0.2.zip
                </div>
                
                <div class="pibu-fixed-progress">
                    <p>در حال پردازش... لطفاً صبر کنید</p>
                </div>
                
                <div id="pibu-fixed-message" class="pibu-fixed-message"></div>
                
                <div class="pibu-fixed-buttons">
                    <button type="button" id="pibu-fixed-install-btn" class="pibu-fixed-install-btn">نصب افزونه</button>
                    <button type="button" id="pibu-fixed-cancel-btn" class="pibu-fixed-cancel-btn">انصراف</button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var modal = $('#pibu-fixed-modal');
            var closeBtn = $('.pibu-fixed-close');
            var cancelBtn = $('#pibu-fixed-cancel-btn');
            var installBtn = $('#pibu-fixed-install-btn');
            var urlInput = $('#pibu-fixed-url-input');
            var progress = $('.pibu-fixed-progress');
            var message = $('#pibu-fixed-message');
            
            // Close modal events
            closeBtn.on('click', function() {
                closeModal();
            });
            
            cancelBtn.on('click', function() {
                closeModal();
            });
            
            $(window).on('click', function(event) {
                if (event.target == modal[0]) {
                    closeModal();
                }
            });
            
            // Install button click
            installBtn.on('click', function() {
                var url = urlInput.val().trim();
                
                if (!url) {
                    showMessage(pibu_fixed_ajax.enter_url_text, 'error');
                    return;
                }
                
                if (!isValidUrl(url)) {
                    showMessage(pibu_fixed_ajax.invalid_url_text, 'error');
                    return;
                }
                
                installPlugin(url);
            });
            
            // Enter key support
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
                installBtn.text(pibu_fixed_ajax.installing_text);
                progress.show();
                hideMessage();
                
                var requestData = {
                    action: 'install_plugin_by_url_fixed',
                    plugin_url: url,
                    nonce: pibu_fixed_ajax.nonce
                };
                
                console.log('Sending request:', requestData);
                
                $.ajax({
                    url: pibu_fixed_ajax.ajax_url,
                    type: 'POST',
                    data: requestData,
                    timeout: 120000, // 2 minutes timeout
                    success: function(response) {
                        console.log('Response received:', response);
                        
                        if (response.success) {
                            showMessage(pibu_fixed_ajax.success_text + '\n\nصفحه در حال بارگذاری مجدد...', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            var errorMsg = response.data || pibu_fixed_ajax.error_text;
                            if (pibu_fixed_ajax.debug && response.debug) {
                                errorMsg += '\n\nاطلاعات debug:\n' + response.debug;
                            }
                            showMessage(errorMsg, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', {xhr: xhr, status: status, error: error});
                        var errorMsg = 'خطا در برقراری ارتباط با سرور';
                        if (pibu_fixed_ajax.debug) {
                            errorMsg += '\n\nجزئیات خطا:\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                        }
                        showMessage(errorMsg, 'error');
                    },
                    complete: function() {
                        installBtn.prop('disabled', false);
                        installBtn.text('نصب افزونه');
                        progress.hide();
                    }
                });
            }
            
            function showMessage(text, type) {
                message.removeClass('pibu-fixed-success pibu-fixed-error pibu-fixed-debug')
                       .addClass('pibu-fixed-' + type)
                       .text(text)
                       .show();
            }
            
            function hideMessage() {
                message.hide();
            }
            
            function closeModal() {
                modal.hide();
                resetModal();
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
    
    public function ajax_install_plugin() {
        // Enable error reporting for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
        
        $debug_info = array();
        
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pibu_fixed_nonce')) {
                wp_send_json_error('Security check failed', array('debug' => 'Invalid nonce'));
                return;
            }
            
            // Check permissions
            if (!current_user_can('install_plugins')) {
                wp_send_json_error('شما مجوز نصب افزونه ندارید', array('debug' => 'User lacks install_plugins capability'));
                return;
            }
            
            $plugin_url = isset($_POST['plugin_url']) ? sanitize_url($_POST['plugin_url']) : '';
            $debug_info[] = 'Plugin URL: ' . $plugin_url;
            
            if (empty($plugin_url)) {
                wp_send_json_error('لینک افزونه وارد نشده است', array('debug' => 'Empty plugin URL'));
                return;
            }
            
            // Validate URL
            if (!filter_var($plugin_url, FILTER_VALIDATE_URL)) {
                wp_send_json_error('لینک وارد شده معتبر نیست', array('debug' => 'Invalid URL format'));
                return;
            }
            
            $debug_info[] = 'URL validation passed';
            
            // Include required WordPress files
            if (!class_exists('Plugin_Upgrader')) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            }
            if (!function_exists('download_url')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            if (!function_exists('request_filesystem_credentials')) {
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }
            
            $debug_info[] = 'WordPress files included';
            
            // Test URL accessibility
            $response = wp_remote_head($plugin_url, array(
                'timeout' => 30,
                'redirection' => 5
            ));
            
            if (is_wp_error($response)) {
                wp_send_json_error('لینک در دسترس نیست: ' . $response->get_error_message(), array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                wp_send_json_error('لینک در دسترس نیست (کد خطا: ' . $response_code . ')', array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            $debug_info[] = 'URL accessibility test passed (HTTP ' . $response_code . ')';
            
            // Download the file
            $temp_file = download_url($plugin_url, 300); // 5 minutes timeout
            
            if (is_wp_error($temp_file)) {
                wp_send_json_error('خطا در دانلود فایل: ' . $temp_file->get_error_message(), array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            $debug_info[] = 'File downloaded to: ' . $temp_file;
            
            // Check if file exists and is readable
            if (!file_exists($temp_file) || !is_readable($temp_file)) {
                wp_send_json_error('فایل دانلود شده در دسترس نیست', array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            $file_size = filesize($temp_file);
            $debug_info[] = 'File size: ' . $file_size . ' bytes';
            
            if ($file_size < 100) {
                wp_send_json_error('فایل دانلود شده کوچک‌تر از حد مجاز است', array('debug' => implode('\n', $debug_info)));
                @unlink($temp_file);
                return;
            }
            
            // Initialize filesystem
            global $wp_filesystem;
            if (!WP_Filesystem()) {
                wp_send_json_error('خطا در دسترسی به سیستم فایل', array('debug' => implode('\n', $debug_info)));
                @unlink($temp_file);
                return;
            }
            
            $debug_info[] = 'Filesystem initialized';
            
            // Create a custom upgrader skin to capture output
            if (!class_exists('PIBU_Fixed_Upgrader_Skin')) {
                class PIBU_Fixed_Upgrader_Skin extends WP_Upgrader_Skin {
                    public $messages = array();
                    
                    public function feedback($string, ...$args) {
                        if (isset($this->upgrader->strings[$string])) {
                            $string = $this->upgrader->strings[$string];
                        }
                        
                        if (strpos($string, '%') !== false) {
                            if ($args) {
                                $string = vsprintf($string, $args);
                            }
                        }
                        
                        $this->messages[] = $string;
                    }
                }
            }
            
            // Install plugin
            $skin = new PIBU_Fixed_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            
            $debug_info[] = 'Starting plugin installation';
            
            $result = $upgrader->install($temp_file);
            
            // Clean up temp file
            @unlink($temp_file);
            
            $debug_info[] = 'Installation result: ' . ($result ? 'true' : 'false');
            $debug_info[] = 'Upgrader messages: ' . implode('; ', $skin->messages);
            
            if (is_wp_error($result)) {
                wp_send_json_error('خطا در نصب افزونه: ' . $result->get_error_message(), array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            if ($result === false) {
                wp_send_json_error('نصب افزونه با شکست مواجه شد', array('debug' => implode('\n', $debug_info)));
                return;
            }
            
            $debug_info[] = 'Plugin installed successfully';
            
            wp_send_json_success('افزونه با موفقیت نصب شد', array('debug' => implode('\n', $debug_info)));
            
        } catch (Exception $e) {
            $debug_info[] = 'Exception: ' . $e->getMessage();
            wp_send_json_error('خطای سیستمی: ' . $e->getMessage(), array('debug' => implode('\n', $debug_info)));
        }
    }
    
    public function debug_install() {
        if (!current_user_can('install_plugins')) {
            wp_die('Access denied');
        }
        
        echo '<h2>تست سیستم نصب افزونه</h2>';
        
        // Test filesystem
        if (WP_Filesystem()) {
            echo '<p>✅ سیستم فایل: در دسترس</p>';
        } else {
            echo '<p>❌ سیستم فایل: مشکل دار</p>';
        }
        
        // Test required classes
        if (class_exists('Plugin_Upgrader')) {
            echo '<p>✅ Plugin_Upgrader: موجود</p>';
        } else {
            echo '<p>❌ Plugin_Upgrader: موجود نیست</p>';
        }
        
        // Test functions
        if (function_exists('download_url')) {
            echo '<p>✅ download_url: موجود</p>';
        } else {
            echo '<p>❌ download_url: موجود نیست</p>';
        }
        
        // Test permissions
        echo '<p>مجوزهای کاربر جاری:</p>';
        echo '<ul>';
        echo '<li>install_plugins: ' . (current_user_can('install_plugins') ? '✅' : '❌') . '</li>';
        echo '<li>activate_plugins: ' . (current_user_can('activate_plugins') ? '✅' : '</li>';
        echo '<li>delete_plugins: ' . (current_user_can('delete_plugins') ? '✅' : '❌') . '</li>';
        echo '</ul>';
        
        // Test temp directory
        $temp_dir = get_temp_dir();
        echo '<p>پوشه موقت: ' . $temp_dir . '</p>';
        if (is_writable($temp_dir)) {
            echo '<p>✅ پوشه موقت قابل نوشتن است</p>';
        } else {
            echo '<p>❌ پوشه موقت قابل نوشتن نیست</p>';
        }
        
        wp_die();
    }
}

// Initialize the plugin
new PluginInstallerByURLFixed();

// Add debug page for administrators
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            null, // No parent menu
            'تست نصب افزونه',
            'تست نصب افزونه',
            'manage_options',
            'pibu-debug',
            function() {
                $plugin = new PluginInstallerByURLFixed();
                $plugin->debug_install();
            }
        );
    }
});

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pibu_fixed_add_settings_link');
function pibu_fixed_add_settings_link($links) {
    $settings_link = '<a href="plugin-install.php">نصب افزونه</a>';
    $debug_link = '<a href="' . admin_url('admin.php?page=pibu-debug') . '">تست سیستم</a>';
    array_unshift($links, $settings_link, $debug_link);
    return $links;
}

// Load text domain for translations
add_action('plugins_loaded', 'pibu_fixed_load_textdomain');
function pibu_fixed_load_textdomain() {
    load_plugin_textdomain('plugin-installer-by-url-fixed', false, dirname(plugin_basename(__FILE__)) . '/languages');
}