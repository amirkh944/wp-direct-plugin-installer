# راهنمای عیب‌یابی

## مشکل: "خطا در نصب افزونه" پس از چند ثانیه

### گام 1: استفاده از نسخه رفع شده
استفاده کنید از: `plugin-installer-by-url-fixed.php`

### گام 2: فعال‌سازی Debug Mode
کدهای زیر را به فایل `wp-config.php` اضافه کنید (قبل از خط `require_once ABSPATH . 'wp-settings.php';`):

```php
// Enable debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Increase limits
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
ini_set('upload_max_filesize', '64M');
ini_set('post_max_size', '64M');

// Enable logging
ini_set('log_errors', 1);
ini_set('error_log', ABSPATH . 'wp-content/debug.log');
```

### گام 3: تست سیستم
1. بعد از فعال‌سازی افزونه، به صفحه افزونه‌ها بروید
2. روی لینک **"تست سیستم"** کلیک کنید
3. نتایج را بررسی کنید

### گام 4: بررسی مشکلات رایج

#### مشکل: Filesystem Access
اگر پیام "خطا در دسترسی به سیستم فایل" می‌بینید:

```php
// اضافه کردن به wp-config.php
define('FS_METHOD', 'direct');
```

#### مشکل: Permission Issues
اگر مشکل مجوزها دارید:

```php
// اضافه کردن به wp-config.php
define('DISALLOW_FILE_MODS', false);
define('DISALLOW_FILE_EDIT', false);
```

#### مشکل: Shared Hosting
برای هاست‌های اشتراکی:

```php
// اضافه کردن به wp-config.php
define('FS_METHOD', 'ftpext');
define('FTP_HOST', 'localhost');
define('FTP_USER', 'your-username');
define('FTP_PASS', 'your-password');
```

### گام 5: تست با لینک‌های مختلف

#### لینک‌های تست پیشنهادی:
```
https://downloads.wordpress.org/plugin/classic-editor.1.6.3.zip
https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip
https://downloads.wordpress.org/plugin/akismet.5.0.2.zip
```

### گام 6: بررسی Log ها

1. به پوشه `/wp-content/` بروید
2. فایل `debug.log` را باز کنید
3. خطاهای مربوط به افزونه را جستجو کنید

### گام 7: تنظیمات پیشرفته

#### برای سرورهای با محدودیت شبکه:
```php
// اضافه کردن به wp-config.php
define('WP_HTTP_BLOCK_EXTERNAL', false);
```

#### برای افزایش timeout:
```php
// اضافه کردن به wp-config.php
add_filter('http_request_timeout', function() { return 300; });
```

### گام 8: تست دستی

Browser Console را باز کنید (F12) و هنگام نصب افزونه:
1. در tab Network خطاها را بررسی کنید
2. در tab Console پیام‌های خطا را چک کنید

### مشکلات احتمالی و راه‌حل‌ها

#### 1. خطای 500 Internal Server Error
- محدودیت memory_limit یا max_execution_time
- مشکل در permissions پوشه‌ها
- تداخل با افزونه‌های دیگر

**راه‌حل:**
```php
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);
```

#### 2. خطای Timeout
- اتصال کند به اینترنت
- فایل افزونه بزرگ

**راه‌حل:**
```php
ini_set('default_socket_timeout', 600);
add_filter('http_request_timeout', function() { return 600; });
```

#### 3. خطای Permission Denied
- سرور اجازه نوشتن در wp-content/plugins نمی‌دهد

**راه‌حل:**
- تماس با هاستینگ برای تنظیم permissions
- یا استفاده از FTP credentials

#### 4. خطای cURL
- سرور cURL ندارد یا محدود است

**راه‌حل:**
```php
// اضافه کردن به wp-config.php
add_filter('http_request_args', function($args) {
    $args['sslverify'] = false;
    return $args;
});
```

### تست نهایی

1. افزونه را غیرفعال و مجدداً فعال کنید
2. Cache مرورگر را پاک کنید
3. با یک افزونه کوچک تست کنید
4. اگر همچنان مشکل دارید، Log ها را بررسی کنید

### دریافت کمک

اگر همچنان مشکل دارید:

1. محتویات فایل `debug.log` را کپی کنید
2. نتیجه صفحه "تست سیستم" را ذخیره کنید
3. پیام دقیق خطا را یادداشت کنید
4. مشخصات هاستینگ خود را بنویسید

---

**نکته مهم:** این افزونه نیاز به مجوزهای نوشتن در پوشه wp-content/plugins دارد.