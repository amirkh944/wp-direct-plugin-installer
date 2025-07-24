<?php
/**
 * Plugin Name: نصب افزونه از URL
 * Description: نصب مستقیم افزونه‌های وردپرس از طریق لینک دانلود
 * Version: 2.0.0
 * Author: Developer
 * Text Domain: wp-plugin-url-installer
 */

// جلوگیری از دسترسی مستقیم
defined('ABSPATH') || exit;

/**
 * کلاس اصلی افزونه
 */
class WP_Plugin_URL_Installer {
    
    /**
     * سازنده
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_wpui_install_plugin', array($this, 'handle_install_request'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * اضافه کردن منو در پنل مدیریت
     */
    public function add_admin_menu() {
        add_plugins_page(
            'نصب افزونه از URL',
            'نصب از URL',
            'install_plugins',
            'plugin-url-installer',
            array($this, 'admin_page')
        );
    }
    
    /**
     * بارگذاری فایل‌های CSS و JS
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'plugins_page_plugin-url-installer') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'wpui_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpui_install_nonce')
        ));
    }
    
    /**
     * صفحه مدیریت افزونه
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>نصب افزونه از URL</h1>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2 class="title">دانلود و نصب افزونه</h2>
                
                <form id="wpui-install-form" method="post">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="plugin-url">لینک دانلود افزونه</label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="plugin-url" 
                                       name="plugin_url" 
                                       class="regular-text" 
                                       placeholder="https://example.com/plugin.zip"
                                       required />
                                <p class="description">
                                    لینک مستقیم فایل ZIP افزونه را وارد کنید
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div id="wpui-progress" style="display: none; margin: 20px 0;">
                        <p><strong>در حال نصب افزونه...</strong></p>
                        <div style="background: #f1f1f1; border-radius: 3px; padding: 3px;">
                            <div id="wpui-progress-bar" style="background: #0073aa; height: 20px; border-radius: 3px; width: 0%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    
                    <div id="wpui-messages"></div>
                    
                    <p class="submit">
                        <input type="submit" 
                               id="wpui-install-btn" 
                               class="button button-primary" 
                               value="نصب افزونه" />
                        <span class="spinner" id="wpui-spinner"></span>
                    </p>
                </form>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>راهنمای استفاده</h3>
                <ul>
                    <li>لینک باید مستقیماً به فایل ZIP افزونه اشاره کند</li>
                    <li>فایل باید شامل افزونه معتبر وردپرس باشد</li>
                    <li>بعد از نصب، به صفحه افزونه‌ها بروید تا آن را فعال کنید</li>
                </ul>
                
                <h4>نمونه لینک‌های معتبر:</h4>
                <ul>
                    <li><code>https://downloads.wordpress.org/plugin/akismet.5.0.2.zip</code></li>
                    <li><code>https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip</code></li>
                </ul>
            </div>
        </div>
        
        <style>
        #wpui-messages {
            margin: 15px 0;
        }
        .wpui-notice {
            padding: 8px 12px;
            margin: 5px 0;
            border-left: 4px solid;
            border-radius: 2px;
        }
        .wpui-notice.success {
            background: #d1e7dd;
            border-color: #0f5132;
            color: #0f5132;
        }
        .wpui-notice.error {
            background: #f8d7da;
            border-color: #842029;
            color: #842029;
        }
        .wpui-notice.info {
            background: #d1ecf1;
            border-color: #055160;
            color: #055160;
        }
        #wpui-spinner {
            float: none;
            margin-left: 10px;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var form = $('#wpui-install-form');
            var urlInput = $('#plugin-url');
            var installBtn = $('#wpui-install-btn');
            var spinner = $('#wpui-spinner');
            var progress = $('#wpui-progress');
            var progressBar = $('#wpui-progress-bar');
            var messages = $('#wpui-messages');
            
            form.on('submit', function(e) {
                e.preventDefault();
                
                var url = urlInput.val().trim();
                if (!url) {
                    showMessage('لطفاً لینک افزونه را وارد کنید', 'error');
                    return;
                }
                
                if (!isValidUrl(url)) {
                    showMessage('لینک وارد شده معتبر نیست', 'error');
                    return;
                }
                
                installPlugin(url);
            });
            
            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
            
            function installPlugin(url) {
                installBtn.prop('disabled', true);
                spinner.addClass('is-active');
                progress.show();
                messages.empty();
                
                // شبیه‌سازی پیشرفت
                var progressValue = 0;
                var progressInterval = setInterval(function() {
                    progressValue += Math.random() * 15;
                    if (progressValue > 90) progressValue = 90;
                    progressBar.css('width', progressValue + '%');
                }, 300);
                
                $.ajax({
                    url: wpui_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpui_install_plugin',
                        plugin_url: url,
                        nonce: wpui_ajax.nonce
                    },
                    timeout: 60000,
                    success: function(response) {
                        clearInterval(progressInterval);
                        progressBar.css('width', '100%');
                        
                        if (response.success) {
                            showMessage('افزونه با موفقیت نصب شد! صفحه در حال بارگذاری مجدد...', 'success');
                            setTimeout(function() {
                                window.location.href = admin_url + 'plugins.php';
                            }, 2000);
                        } else {
                            showMessage(response.data || 'خطا در نصب افزونه', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        clearInterval(progressInterval);
                        var errorMsg = 'خطا در برقراری ارتباط با سرور';
                        if (status === 'timeout') {
                            errorMsg = 'زمان درخواست به پایان رسید. لطفاً دوباره تلاش کنید.';
                        }
                        showMessage(errorMsg, 'error');
                    },
                    complete: function() {
                        installBtn.prop('disabled', false);
                        spinner.removeClass('is-active');
                        setTimeout(function() {
                            progress.hide();
                            progressBar.css('width', '0%');
                        }, 1000);
                    }
                });
            }
            
            function showMessage(message, type) {
                var notice = $('<div class="wpui-notice ' + type + '">' + message + '</div>');
                messages.append(notice);
                
                $('html, body').animate({
                    scrollTop: messages.offset().top - 100
                }, 500);
            }
        });
        </script>
        <?php
    }
    
    /**
     * مدیریت درخواست نصب افزونه
     */
    public function handle_install_request() {
        // بررسی امنیت
        if (!wp_verify_nonce($_POST['nonce'], 'wpui_install_nonce')) {
            wp_send_json_error('خطای امنیتی - لطفاً صفحه را reload کنید');
        }
        
        // بررسی مجوز
        if (!current_user_can('install_plugins')) {
            wp_send_json_error('شما مجوز نصب افزونه ندارید');
        }
        
        $plugin_url = sanitize_url($_POST['plugin_url']);
        
        if (empty($plugin_url)) {
            wp_send_json_error('لینک افزونه وارد نشده است');
        }
        
        // اعتبارسنجی URL
        if (!filter_var($plugin_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('لینک وارد شده معتبر نیست');
        }
        
        // نصب افزونه
        $result = $this->install_plugin_from_url($plugin_url);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('افزونه با موفقیت نصب شد');
        }
    }
    
    /**
     * نصب افزونه از URL
     */
    private function install_plugin_from_url($url) {
        // Include کردن فایل‌های مورد نیاز
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        
        // تست دسترسی به URL
        $response = wp_remote_head($url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('url_error', 'نمی‌توان به لینک دسترسی پیدا کرد: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('url_error', 'لینک در دسترس نیست (کد خطا: ' . $response_code . ')');
        }
        
        // دانلود فایل
        $temp_file = download_url($url, 300);
        
        if (is_wp_error($temp_file)) {
            return new WP_Error('download_error', 'خطا در دانلود فایل: ' . $temp_file->get_error_message());
        }
        
        // بررسی فایل
        if (!file_exists($temp_file)) {
            return new WP_Error('file_error', 'فایل دانلود شده وجود ندارد');
        }
        
        $file_size = filesize($temp_file);
        if ($file_size < 100) {
            @unlink($temp_file);
            return new WP_Error('file_error', 'فایل دانلود شده خیلی کوچک است');
        }
        
        // راه‌اندازی سیستم فایل
        global $wp_filesystem;
        if (!WP_Filesystem()) {
            @unlink($temp_file);
            return new WP_Error('filesystem_error', 'خطا در دسترسی به سیستم فایل');
        }
        
        // نصب افزونه
        $upgrader = new Plugin_Upgrader(new WP_Upgrader_Skin());
        $install_result = $upgrader->install($temp_file);
        
        // پاک کردن فایل موقت
        @unlink($temp_file);
        
        if (is_wp_error($install_result)) {
            return $install_result;
        }
        
        if ($install_result === false) {
            return new WP_Error('install_error', 'نصب افزونه ناموفق بود');
        }
        
        return true;
    }
}

// راه‌اندازی افزونه
new WP_Plugin_URL_Installer();

// اضافه کردن لینک تنظیمات
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('plugins.php?page=plugin-url-installer') . '">نصب از URL</a>';
    array_unshift($links, $settings_link);
    return $links;
});
?>