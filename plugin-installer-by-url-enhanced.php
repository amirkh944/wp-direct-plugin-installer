<?php
/**
 * Plugin Name: نصب افزونه با لینک (نسخه پیشرفته)
 * Plugin URI: https://example.com
 * Description: امکان نصب و آپدیت افزونه‌های وردپرس با استفاده از URL - نسخه پیشرفته با دکمه در بالای صفحه
 * Version: 1.1.0
 * Author: Your Name
 * Text Domain: plugin-installer-by-url-enhanced
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PIBU_ENH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PIBU_ENH_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PIBU_ENH_VERSION', '1.1.0');

class PluginInstallerByURLEnhanced {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_install_plugin_by_url_enhanced', array($this, 'ajax_install_plugin'));
        add_action('wp_ajax_nopriv_install_plugin_by_url_enhanced', array($this, 'ajax_install_plugin'));
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
        
        wp_enqueue_script(
            'pibu-enh-script',
            PIBU_ENH_PLUGIN_URL . 'assets/js/plugin-installer-enhanced.js',
            array('jquery'),
            PIBU_ENH_VERSION,
            true
        );
        
        wp_localize_script('pibu-enh-script', 'pibu_enh_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pibu_enh_nonce'),
            'installing_text' => __('در حال نصب...', 'plugin-installer-by-url-enhanced'),
            'success_text' => __('افزونه با موفقیت نصب شد!', 'plugin-installer-by-url-enhanced'),
            'error_text' => __('خطا در نصب افزونه', 'plugin-installer-by-url-enhanced'),
            'invalid_url_text' => __('لینک وارد شده معتبر نیست', 'plugin-installer-by-url-enhanced'),
            'enter_url_text' => __('لطفاً لینک افزونه را وارد کنید', 'plugin-installer-by-url-enhanced'),
            'close_text' => __('بستن', 'plugin-installer-by-url-enhanced'),
            'cancel_text' => __('انصراف', 'plugin-installer-by-url-enhanced')
        ));
        
        wp_enqueue_style(
            'pibu-enh-style',
            PIBU_ENH_PLUGIN_URL . 'assets/css/plugin-installer-enhanced.css',
            array(),
            PIBU_ENH_VERSION
        );
    }
    
    public function add_header_button() {
        ?>
        <style>
        .page-title-action.pibu-add-link-btn {
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
        .page-title-action.pibu-add-link-btn:hover {
            background: #135e96;
            border-color: #135e96;
            color: #fff;
        }
        
        /* Modal Styles */
        .pibu-enh-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .pibu-enh-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 3px 6px rgba(0,0,0,0.3);
        }
        
        .pibu-enh-modal-header {
            padding: 20px 20px 0;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .pibu-enh-modal-header h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .pibu-enh-modal-body {
            padding: 0 20px 20px;
        }
        
        .pibu-enh-close {
            color: #666;
            float: left;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -5px;
        }
        
        .pibu-enh-close:hover {
            color: #000;
        }
        
        .pibu-enh-url-input {
            width: 100%;
            padding: 8px 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            direction: ltr;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .pibu-enh-url-input:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 1px #0073aa;
            outline: none;
        }
        
        .pibu-enh-buttons {
            margin-top: 20px;
            text-align: left;
        }
        
        .pibu-enh-install-btn {
            background: #2271b1;
            color: white;
            border: 1px solid #2271b1;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .pibu-enh-install-btn:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        .pibu-enh-install-btn:disabled {
            background: #f0f0f1;
            color: #a7aaad;
            border-color: #dcdcde;
            cursor: not-allowed;
        }
        
        .pibu-enh-cancel-btn {
            background: #f6f7f7;
            color: #2c3338;
            border: 1px solid #dcdcde;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .pibu-enh-cancel-btn:hover {
            background: #f0f0f1;
        }
        
        .pibu-enh-progress {
            display: none;
            margin: 15px 0;
        }
        
        .pibu-enh-progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .pibu-enh-progress-fill {
            height: 100%;
            background-color: #2271b1;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .pibu-enh-message {
            margin: 15px 0;
            padding: 12px;
            border-radius: 4px;
            display: none;
        }
        
        .pibu-enh-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .pibu-enh-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        
        .pibu-enh-info {
            background: #d1ecf1;
            color: #055160;
            border: 1px solid #b8daff;
        }
        
        .pibu-enh-help-text {
            font-size: 12px;
            color: #646970;
            margin-top: 5px;
        }
        
        /* RTL Support */
        body.rtl .pibu-enh-close {
            float: right;
        }
        
        body.rtl .pibu-enh-buttons {
            text-align: right;
        }
        
        body.rtl .pibu-enh-install-btn {
            margin-right: 10px;
            margin-left: 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add button next to "Add New" button
            var addNewBtn = $('.page-title-action').first();
            if (addNewBtn.length) {
                var addLinkBtn = $('<a href="#" class="page-title-action pibu-add-link-btn">افزودن با لینک</a>');
                addLinkBtn.insertAfter(addNewBtn);
                
                addLinkBtn.on('click', function(e) {
                    e.preventDefault();
                    $('#pibu-enh-modal').show();
                    $('#pibu-enh-url-input').focus();
                });
            }
        });
        </script>
        <?php
    }
    
    public function add_modal_html() {
        ?>
        <!-- Modal -->
        <div id="pibu-enh-modal" class="pibu-enh-modal">
            <div class="pibu-enh-modal-content">
                <div class="pibu-enh-modal-header">
                    <span class="pibu-enh-close">&times;</span>
                    <h2>نصب افزونه از طریق لینک</h2>
                </div>
                <div class="pibu-enh-modal-body">
                    <p>لینک مستقیم دانلود افزونه (فایل ZIP) را وارد کنید:</p>
                    <input type="url" id="pibu-enh-url-input" class="pibu-enh-url-input" placeholder="https://example.com/plugin.zip" />
                    <div class="pibu-enh-help-text">
                        مثال: https://downloads.wordpress.org/plugin/akismet.5.0.2.zip
                    </div>
                    
                    <div class="pibu-enh-progress">
                        <div class="pibu-enh-progress-bar">
                            <div class="pibu-enh-progress-fill"></div>
                        </div>
                        <div class="pibu-enh-progress-text">در حال دانلود...</div>
                    </div>
                    
                    <div id="pibu-enh-message" class="pibu-enh-message"></div>
                    
                    <div class="pibu-enh-buttons">
                        <button type="button" id="pibu-enh-install-btn" class="pibu-enh-install-btn">نصب افزونه</button>
                        <button type="button" id="pibu-enh-cancel-btn" class="pibu-enh-cancel-btn">انصراف</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var modal = $('#pibu-enh-modal');
            var closeBtn = $('.pibu-enh-close');
            var cancelBtn = $('#pibu-enh-cancel-btn');
            var installBtn = $('#pibu-enh-install-btn');
            var urlInput = $('#pibu-enh-url-input');
            var progress = $('.pibu-enh-progress');
            var progressFill = $('.pibu-enh-progress-fill');
            var progressText = $('.pibu-enh-progress-text');
            var message = $('#pibu-enh-message');
            
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
                    showMessage(pibu_enh_ajax.enter_url_text, 'error');
                    return;
                }
                
                if (!isValidUrl(url)) {
                    showMessage(pibu_enh_ajax.invalid_url_text, 'error');
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
                    new URL(string);
                    return string.toLowerCase().endsWith('.zip');
                } catch (_) {
                    return false;
                }
            }
            
            function installPlugin(url) {
                installBtn.prop('disabled', true);
                installBtn.text(pibu_enh_ajax.installing_text);
                progress.show();
                hideMessage();
                
                // Simulate progress
                var progressValue = 0;
                var progressInterval = setInterval(function() {
                    progressValue += Math.random() * 20;
                    if (progressValue > 90) progressValue = 90;
                    progressFill.css('width', progressValue + '%');
                }, 200);
                
                $.ajax({
                    url: pibu_enh_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'install_plugin_by_url_enhanced',
                        plugin_url: url,
                        nonce: pibu_enh_ajax.nonce
                    },
                    success: function(response) {
                        clearInterval(progressInterval);
                        progressFill.css('width', '100%');
                        
                        if (response.success) {
                            showMessage(pibu_enh_ajax.success_text, 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data || pibu_enh_ajax.error_text, 'error');
                        }
                    },
                    error: function() {
                        clearInterval(progressInterval);
                        showMessage(pibu_enh_ajax.error_text, 'error');
                    },
                    complete: function() {
                        installBtn.prop('disabled', false);
                        installBtn.text('نصب افزونه');
                        setTimeout(function() {
                            progress.hide();
                            progressFill.css('width', '0%');
                        }, 1000);
                    }
                });
            }
            
            function showMessage(text, type) {
                message.removeClass('pibu-enh-success pibu-enh-error pibu-enh-info')
                       .addClass('pibu-enh-' + type)
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
                progressFill.css('width', '0%');
                hideMessage();
            }
        });
        </script>
        <?php
    }
    
    public function ajax_install_plugin() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'pibu_enh_nonce')) {
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
new PluginInstallerByURLEnhanced();

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pibu_enh_add_settings_link');
function pibu_enh_add_settings_link($links) {
    $settings_link = '<a href="plugin-install.php">نصب افزونه</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Load text domain for translations
add_action('plugins_loaded', 'pibu_enh_load_textdomain');
function pibu_enh_load_textdomain() {
    load_plugin_textdomain('plugin-installer-by-url-enhanced', false, dirname(plugin_basename(__FILE__)) . '/languages');
}