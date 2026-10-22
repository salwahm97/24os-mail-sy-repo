<?php
/**
* Plugin Name: 24os Mail Sy
* Plugin URI: http://www.24os.de/24os-mail-sy
* * Author URI: http://www.24os.de
* Author: 24os.de, Salwa Hammmami
* Version: 1.4.0
* Description: Order management and tracking (official documents / mail forwarding) with Angular front-ends. Developed by Salwa Hammami (https://github.com/salwahm97).
*/

    if(!defined("ABSPATH"))
        exit;

    // Private settings (IBAN, e-mails, ...). Copy config.sample.php to config.php and edit it.
    if(file_exists(plugin_dir_path(__FILE__)."config.php"))
        require_once plugin_dir_path(__FILE__)."config.php";
    foreach(array(
        "OS24_MAIL_SY_COMPANY_NAME"=>"Your Company", "OS24_MAIL_SY_COMPANY_IBAN"=>"", "OS24_MAIL_SY_COMPANY_BIC"=>"",
        "OS24_MAIL_SY_COMPANY_ADDRESS"=>"", "OS24_MAIL_SY_ADMIN_EMAIL_DOCS"=>get_option("admin_email"),
        "OS24_MAIL_SY_ADMIN_EMAIL_MAIL"=>get_option("admin_email"),
        "OS24_MAIL_SY_CAPABILITY"=>"edit_posts") as $k=>$v)
        if(!defined($k)) define($k,$v);

    if(!defined("OS24_MAIL_SY_PLUGIN_DIR_PATH"))  
        define("OS24_MAIL_SY_PLUGIN_DIR_PATH", plugin_dir_path(__FILE__));

    if(!defined("OS24_MAIL_SY_PLUGIN_URL"))
        define("OS24_MAIL_SY_PLUGIN_URL", plugins_url()."/24os-mail-sy");  

    if(!defined("OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION"))
        define("OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION", "24os_mail_sy_language_option"); 


// ---------------------------------------------------------------------------
// Security helpers
// ---------------------------------------------------------------------------

/** Only logged-in staff (capability OS24_MAIL_SY_CAPABILITY) may call admin endpoints. Needs X-WP-Nonce header. */
function os24_mail_sy_permission_admin(){
    return current_user_can(OS24_MAIL_SY_CAPABILITY);
}

/** Allowed languages (whitelist, also used inside SQL). */
function os24_mail_sy_valid_lang($lang, $default = 'en'){
    $lang = is_string($lang) ? strtolower(trim($lang)) : '';
    return in_array($lang, array('ar','en','de'), true) ? $lang : $default;
}

/** Simple per-IP rate limit using transients. Returns true when the limit is exceeded. */
function os24_mail_sy_rate_limited($action, $max, $window_seconds){
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $key = 'os24ms_rl_' . md5($action . '|' . $ip);
    $count = (int) get_transient($key);
    if($count >= $max){
        return true;
    }
    set_transient($key, $count + 1, $window_seconds);
    return false;
}

/** Query helper: all queries are built with intval()/esc_sql() before reaching here. */
function os24_mail_sy_q($query){
    return $query;
}

function os24_mail_sy_error_response($message, $status = 200){
    $response = new WP_REST_Response(array("message"=>$message));
    $response->set_status($status);
    return $response;
}

// create custom table database on active plugin
function os24_mail_sy_active()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // table '24os_mail_sy_category'
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_category ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_name VARCHAR(255) UNIQUE NOT NULL,
            category_group VARCHAR(11) NOT NULL,
            category_duration TEXT NULL,
            category_price TEXT NULL,
            category_price_total TEXT NULL,
            category_price_desc TEXT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_category_translate'
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_category_translate ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_id bigint(20) NOT NULL,
            category_name VARCHAR(255) UNIQUE NOT NULL,
            category_group VARCHAR(11) NOT NULL,
            category_duration TEXT NULL,
            category_price TEXT NULL,
            category_price_total TEXT NULL,
            category_price_desc TEXT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_item'
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_item ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_name VARCHAR(255) UNIQUE NOT NULL,
            item_desc TEXT NULL,
            item_type VARCHAR(100) NULL,
            item_duration TEXT NULL,
            item_price TEXT NULL,
            item_price_total TEXT NULL,
            item_price_desc TEXT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_item_translate'
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_item_translate ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            item_id bigint(20) NOT NULL,
            item_name VARCHAR(255) UNIQUE NOT NULL,
            item_desc TEXT NULL,
            item_duration TEXT NULL,
            item_price TEXT NULL,
            item_price_total TEXT NULL,
            item_price_desc TEXT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_order'
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_order ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            no VARCHAR(200) NULL,
            email VARCHAR(200) NULL, 
            firstname VARCHAR(200) NOT NULL, 
            lastname VARCHAR(200) NOT NULL, 
            rec_firstname VARCHAR(200) NULL, 
            rec_lastname VARCHAR(200) NULL, 
            address_from TEXT NULL, 
            address_to TEXT NULL, 
            city VARCHAR(200) NULL,
            phone VARCHAR(100) NULL, 
            rec_phone VARCHAR(100) NULL, 
            id_number VARCHAR(100) NULL,
            img LONGTEXT NULL, 
            items LONGTEXT NULL,
            sheet_number int(11) NULL,
            notes LONGTEXT NULL,
            cost TEXT NULL,
            cost_extra TEXT NULL,
            total_duration TEXT NULL,
            lang VARCHAR(20) NOT NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            type VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_status'
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_status ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            status TEXT NOT NULL,
            type VARCHAR(11) NOT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_status_translate'
    $table_name_status_translate = $wpdb->prefix . '24os_mail_sy_status_translate';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_status_translate ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            status_id bigint(20) NOT NULL,
            status TEXT NOT NULL,
            type VARCHAR(11) NOT NULL,
            lang VARCHAR(20) NOT NULL,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // table '24os_mail_sy_order_status'
    $table_name_order_status = $wpdb->prefix . '24os_mail_sy_order_status';

    $sql = "CREATE TABLE IF NOT EXISTS ". $table_name_order_status ."
        (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            status_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            img LONGTEXT NULL, 
            notes LONGTEXT NULL,
            owner VARCHAR(50) NULL,
            date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        )". $charset_collate .";";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // add custom language option for this plugin by add option   
    add_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION, 'ar');


    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بيان عائلي",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" =>  "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Family statement",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Familienerklärung",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));


    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بيان زواج",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Marriage statement",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Heiratsurkunde",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));


    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بيان ولادة",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Birth Certificate",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Geburtsurkunde",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بيان طلاق",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "divorce statement",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Scheidungserklärung",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "اخراج قيد فردي",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Issuing an individual entry",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Ausstellung eines Einzeleintrags",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "تسجيل حالة واقعة مدنية",
        "item_desc" => "بدون تصديق وترجمة",
        "item_type" => "officialDocsWithoutCats",
        "item_duration" => "6-8 اسبوع",
        "item_price" =>"515€ + 19%",
        "item_price_total" =>"612.85€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Civil accident case registration",
        "item_desc" => "Without Certification and Translation",
        "item_duration" => "6-8 week",
        "lang" => "en",
        "item_price" =>"515€ + 19%",
        "item_price_total" =>"612.85€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Registrierung eines zivilrechtlichen Unfallfalls",
        "item_desc" => "Ohne Zertifizierung und Übersetzung",
        "item_duration" => "6-8 woche",
        "lang" => "de",
        "item_price" =>"515€ + 19%",
        "item_price_total" =>"612.85€",
        "item_price_desc" => "",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "شهادات ومصدقات",
        "item_desc" => "بدون تصديق وترجمة",
        "item_type" => "officialDocsWithoutCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "بعد الاتصال",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Certificates and Endorsements",
        "item_desc" => "Without Certification and Translation",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "after call",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Zertifikate und Vermerke",
        "item_desc" => "Ohne Zertifizierung und Übersetzung",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "nach anruf",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بريد سريع",
        "item_desc" => "...",
        "item_type" => "mail",
        "item_duration" => "3-5 يوم",
        "item_price" =>"",
        "item_price_total" =>"49.99€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Express mail",
        "item_desc" => "...",
        "item_duration" => "3-5 day",
        "lang" => "en",
        "item_price" =>"",
        "item_price_total" =>"49.99€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Eilbrief",
        "item_desc" => "...",
        "item_duration" => "3-5 Tag",
        "lang" => "de",
        "item_price" =>"",
        "item_price_total" =>"49.99€",
        "item_price_desc" => "",
    ));



    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "بريد عادي",
        "item_desc" => "...",
        "item_type" => "mail",
        "item_duration" => "1-2 اسبوع",
        "item_price" =>"",
        "item_price_total" =>"35.00€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "normal mail",
        "item_desc" => "...",
        "item_duration" => "1-2 week",
        "lang" => "en",
        "item_price" =>"",
        "item_price_total" =>"35.00€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "normale Post",
        "item_desc" => "...",
        "item_duration" => "1-2 woche",
        "lang" => "de",
        "item_price" =>"",
        "item_price_total" =>"35.00€",
        "item_price_desc" => "",
    ));
    

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "غير ذلك",
        "item_desc" => "...",
        "item_type" => "officialDocsWithoutCats",
        "item_duration" => "10-15 يوم",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "بعد الاتصال",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Other",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "after call",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "Andere",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"",
        "item_price_total" =>"",
        "item_price_desc" => "nach anruf",
    ));

    $sql = $wpdb->insert( $table_name_item, array(
        "item_name" => "لا حكم عليه",
        "item_desc" => "...",
        "item_type" => "officialDocsWithCats",
        "item_duration" =>  "10-15 يوم",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
        "lang" => "ar",
    ));
    $item_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "No Conviction",
        "item_desc" => "...",
        "item_duration" => "10-15 day",
        "lang" => "en",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));
    $sql = $wpdb->insert( $table_name_item_translate, array(
        "item_id" =>  $item_id,
        "item_name" => "keine Verurteilung",
        "item_desc" => "...",
        "item_duration" => "10-15 Tag",
        "lang" => "de",
        "item_price" =>"70€ + 19%",
        "item_price_total" =>"83.3€",
        "item_price_desc" => "",
    ));

    // insert categories 
    $sql = $wpdb->insert( $table_name_category, array(
        "category_name" => "بدون تصديق سفارة المانية",
        "category_group" => "1",
        "category_duration" => "2-4 يوم",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "ar",
    ));
    $cat_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Without the attestation of the German embassy",
        "category_group" => "1",
        "category_duration" => "2-4 day",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "en",
    ));
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Ohne Bescheinigung der deutschen Botschaft",
        "category_group" => "1",
        "category_duration" => "2-4 Tag",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "de",
    ));

    $sql = $wpdb->insert( $table_name_category, array(
        "category_name" => "تصديق سفارة المانية",
        "category_group" => "1",
        "category_duration" => "4-8 اسبوع",
        "category_price" =>"200€ + 19%",
        "category_price_total" =>"238€",
        "category_price_desc" => "يتم الدفع في حال تصديق ورقة واحدة ورسم 50€ لكل ورقة اضافية",
        "lang" => "ar",
    ));
    $cat_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "With the attestation of the German embassy",
        "category_group" => "1",
        "category_duration" => "4-8 week",
        "category_price" =>"200€ + 19%",
        "category_price_total" =>"238€",
        "category_price_desc" => "Payment is made in case of attesting one sheet and a fee of 50€ for each additional sheet",
        "lang" => "en",
    ));
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Mit Bescheinigung der deutschen Botschaft",
        "category_duration" => "4-8 woche",
        "category_price" =>"200€ + 19%",
        "category_price_total" =>"238€",
        "category_price_desc" => "Bei der Beglaubigung eines Blattes wird eine Gebühr erhoben, für jedes weitere Blatt wird eine Gebühr von 50€ erhoben",
        "category_group" => "1",
        "lang" => "de",
    ));

    $sql = $wpdb->insert( $table_name_category, array(
        "category_name" => "سفارات اخرى",
        "category_group" => "1",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "سيتم تطبيق تكلفة إضافية ووقت عمل إضافي، وسنقوم بإعلامكم بذلك لاحقًا، وفقًا للسفارة",
        "lang" => "ar",
    ));
    $cat_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Other embassies",
        "category_group" => "1",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "Additional cost and additional working time will be applied, which we will inform you later, according to the embassy",
        "lang" => "en",
    ));
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Andere Botschaften",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "Es fallen zusätzliche Kosten und zusätzliche Arbeitszeit an, über die wir Sie später informieren, so die Botschaft",
        "category_group" => "1",
        "lang" => "de",
    ));

    $sql = $wpdb->insert( $table_name_category, array(
        "category_name" => "مع ترجمة",
        "category_group" => "2",
        "category_duration" => "",
        "category_price" =>"10€ + 19%",
        "category_price_total" =>"11.90€",
        "category_price_desc" => "",
        "lang" => "ar",
    ));
    $cat_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "With translate",
        "category_duration" => "",
        "category_price" =>"10€ + 19%",
        "category_price_total" =>"11.90€",
        "category_price_desc" => "",
        "category_group" => "2",
        "lang" => "en",
    ));
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Mit übersetzen",
        "category_duration" => "",
        "category_price" =>"10€ + 19%",
        "category_price_total" =>"11.90€",
        "category_price_desc" => "",
        "category_group" => "2",
        "lang" => "de",
    ));

    $sql = $wpdb->insert( $table_name_category, array(
        "category_name" => "بدون ترجمة",
        "category_group" => "2",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "ar",
    ));
    $cat_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Without translate",
        "category_group" => "2",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "en",
    ));
    $sql = $wpdb->insert( $table_name_category_translate, array(
        "category_id" =>  $cat_id,
        "category_name" => "Ohne übersetzen",
        "category_group" => "2",
        "category_duration" => "",
        "category_price" =>"",
        "category_price_total" =>"",
        "category_price_desc" => "",
        "lang" => "de",
    ));

    // insert statuses 
    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم استلام طلب ارسال بريد",
        "type" => "mail",
        "lang" => "ar"
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Mail request received",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Eine E-Mail-Anfrage ist eingegangen",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم استلام البريد",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Mail received",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Mail erhalten",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "الأجور مسددة",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The wages are paid",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Löhne werden gezahlt",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم ارسال البريد الى لبنان بيروت للتحويل لسوريا",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The mail was sent to Lebanon Beirut for transfer to Syria",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Post wurde zur Weiterleitung nach Syrien nach Libanon, Beirut, geschickt",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم وصول البريد للبنان",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Mail has arrived in Lebanon",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Post ist im Libanon angekommen",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم الارسال الى سوريا دمشق/المركز الرئيسي",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Sent to Syria Damascus/Head Office",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Gesendet nach Syrien, Damaskus/Hauptzentrum",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم الارسال للمحافظة",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Sent to the city",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "In die Stadt geschickt",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم تسليم البريد",
        "type" => "mail",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Mail delivered",
        "type" => "mail",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Post wurde zugestellt",
        "type" => "mail",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم استلام الطلب",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Order received",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Bestellung ist eingegangen",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "الأجور مسددة",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The wages are paid",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Löhne werden gezahlt",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم ارسال الطلب لسوريا للمباشرة بالاجراءات",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The order has been sent to Syria to begin the procedures",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die Anordnung wurde nach Syrien geschickt, um das Verfahren einzuleiten",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم استخراج الوثيقة/الوثائق المطلوبة",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The required document(s) have been extracted.",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die erforderlichen Dokumente wurden extrahiert",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم ترجمة الوثائق المطلوبة",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "The required documents have been translated.",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Die erforderlichen Dokumente wurden übersetzt",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم التصديق من",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "authenticate by",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Zertifiziert durch",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم الارسال للقنصلية للتصديق",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Sent to the consulate for authentication",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Es wurde zur Beglaubigung an das Konsulat geschickt",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم التصديق من القنصلية",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Authenticated by the consulate",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "Beglaubigt durch das Konsulat",
        "type" => "docs",
        "lang" => "de"
    ));

    $sql = $wpdb->insert( $table_name_status, array(
        "status" => "تم الارسال",
        "type" => "docs",
        "lang" => "ar",
    ));
    $status_id = $wpdb->insert_id;
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "has been sent",
        "type" => "docs",
        "lang" => "en"
    ));
    $sql = $wpdb->insert( $table_name_status_translate, array(
        "status_id" =>  $status_id,
        "status" => "wurde gesendet",
        "type" => "docs",
        "lang" => "de"
    ));
}
register_activation_hook(__FILE__, "os24_mail_sy_active");

// delete custom table database on uninstall plugin
function os24_mail_sy_uninstall()
{
    global $wpdb;

    //drop 24os_mail_sy_category table
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';
    $sql = "DROP TABLE IF EXISTS ".$table_name_category;
    $wpdb->query($sql);

    //drop 24os_mail_sy_item table
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $sql = "DROP TABLE IF EXISTS ".$table_name_item;
    $wpdb->query($sql);

    //drop 24os_mail_sy_order table
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';
    $sql = "DROP TABLE IF EXISTS ".$table_name_order;
    $wpdb->query($sql);
    
    //drop 24os_mail_sy_item_translate table
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    $sql = "DROP TABLE IF EXISTS ".$table_name_item_translate;
    $wpdb->query($sql);

    //drop 24os_mail_sy_category_translate table
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';
    $sql = "DROP TABLE IF EXISTS ".$table_name_category_translate;
    $wpdb->query($sql);

    //drop 24os_mail_sy_status table
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';
    $sql = "DROP TABLE IF EXISTS ".$table_name_status;
    $wpdb->query($sql);

    //drop 24os_mail_sy_status_translate table
    $table_name_status_translate = $wpdb->prefix . '24os_mail_sy_status_translate';
    $sql = "DROP TABLE IF EXISTS ".$table_name_status_translate;
    $wpdb->query($sql);

    //drop 24os_mail_sy_order_status table
    $table_name_order_status = $wpdb->prefix . '24os_mail_sy_order_status';
    $sql = "DROP TABLE IF EXISTS ".$table_name_order_status;
    $wpdb->query($sql);

    // delete language option
    delete_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION);
    

}
register_uninstall_hook(__FILE__, "os24_mail_sy_uninstall");
// register_deactivation_hook(__FILE__, "os24_mail_sy_uninstall");




function os24_mail_sy_load_scripts() {
    global $parent_file;
    if(isset($_GET["page"]))
    {
        if ('24os-mail-sy' == $parent_file) 
        {
            $current_page = $_GET["page"];
            if('24os-mail-sy' == $current_page)
            { 
                // small script: points the prebuilt Angular app to THIS site's REST API and sends the REST nonce
                wp_register_script( 'os24_mail_sy_api_rewrite', OS24_MAIL_SY_PLUGIN_URL . '/api-rewrite.js', array(), '1.4.0', false );
                wp_add_inline_script( 'os24_mail_sy_api_rewrite',
                    'window.OS24_MAIL_SY = ' . wp_json_encode(array(
                        'root'  => rest_url('24os-mail-sy/v1'),
                        'nonce' => wp_create_nonce('wp_rest'),
                    )) . ';', 'before' );
                $deps = array('os24_mail_sy_api_rewrite');
                wp_register_script( 'os24_mail_sy_orders_ng_main', OS24_MAIL_SY_PLUGIN_URL . '/orders/main.f8a7607b42f3ef71.js', $deps );
                wp_register_script( 'os24_mail_sy_orders_ng_polyfills', OS24_MAIL_SY_PLUGIN_URL . '/orders/polyfills.d35e12648e717ebf.js', $deps );
                wp_register_script( 'os24_mail_sy_orders_ng_runtime', OS24_MAIL_SY_PLUGIN_URL . '/orders/runtime.5ee4853c6ef9a462.js', $deps );
                wp_register_style( 'os24_mail_sy_orders_ng_styles', OS24_MAIL_SY_PLUGIN_URL . '/orders/styles.ec5c2053c72930be.css' );
                wp_register_style( 'os24_mail_sy_orders_ng_icon', OS24_MAIL_SY_PLUGIN_URL . '/orders/icon.css' );

            }
            elseif('24os-mail-sy-products-list' == $current_page) 
            {
                // wp_register_script( 'os24_mail_sy_products_ng_main', OS24_MAIL_SY_PLUGIN_URL . '/products/main.77c2cd952d2cbc08.js' );
                // wp_register_script( 'os24_mail_sy_products_ng_polyfills', OS24_MAIL_SY_PLUGIN_URL . '/products/polyfills.b9294cef6dd4a4bb.js' );
                // wp_register_script( 'os24_mail_sy_products_ng_runtime', OS24_MAIL_SY_PLUGIN_URL . '/products/runtime.a82dfb2c4c09aafb.js' );
                // wp_register_style( 'os24_mail_sy_products_ng_styles', OS24_MAIL_SY_PLUGIN_URL . '/products/styles.f5f03eddccccb64a.css' );
                // wp_register_style( 'os24_mail_sy_products_ng_icon', OS24_MAIL_SY_PLUGIN_URL . '/products/icon.css' );

            }

        }
        
    }
}
add_action( 'admin_enqueue_scripts', 'os24_mail_sy_load_scripts' );


// function os24_mail_sy_products_list_attach_ng_cb(){ 
//     echo do_shortcode("[mail_sy_products_list_app]");
// }

function os24_mail_sy_orders_list_attach_ng_cb(){ 
      if (function_exists('LiteSpeed_Cache_API')) {
        //LiteSpeed_Cache_API::purge_all();
         LiteSpeed_Cache_API::purge(LiteSpeed_Cache_API::TYPE_JS);
        }
    echo do_shortcode("[mail_sy_orders_list_app]");   
}
function os24_mail_sy_plugin_menus(){

    add_menu_page("Mail Sy", 
                    "Mail Sy", 
                    OS24_MAIL_SY_CAPABILITY, 
                    "24os-mail-sy", 
                    "os24_mail_sy_orders_list_attach_ng_cb",
                    "dashicons-money-alt");

    add_submenu_page("24os-mail-sy", 
                    "Orders List", 
                    "Orders List", 
                    OS24_MAIL_SY_CAPABILITY, 
                    "24os-mail-sy", 
                    "os24_mail_sy_orders_list_attach_ng_cb");  

    // add_submenu_page("24os-mail-sy", 
    //                 "Products List", 
    //                 "Products List", 
    //                 "read", 
    //                 "24os-mail-sy-products-list", 
    //                 "os24_mail_sy_products_list_attach_ng_cb");  
}
add_action("admin_menu", "os24_mail_sy_plugin_menus");



// angular app
// function os24_mail_sy_products_list_shortcode_cb() {
    // wp_enqueue_script( 'os24_mail_sy_products_ng_main' );
    // wp_enqueue_script( 'os24_mail_sy_products_ng_polyfills' );
    // wp_enqueue_script( 'os24_mail_sy_products_ng_runtime' );
    // wp_enqueue_style( 'os24_mail_sy_products_ng_styles' );
    // wp_enqueue_style( 'os24_mail_sy_products_ng_icon' );
    
    // return "<app-root></app-root>";
//     return "<h1>Products List</h1>";

// }
// add_shortcode( 'mail_sy_products_list_app', 'os24_mail_sy_products_list_shortcode_cb' );

function os24_mail_sy_orders_list_shortcode_cb() {
    wp_enqueue_script( 'os24_mail_sy_orders_ng_main' );
    wp_enqueue_script( 'os24_mail_sy_orders_ng_polyfills' );
    wp_enqueue_script( 'os24_mail_sy_orders_ng_runtime' );
    wp_enqueue_style( 'os24_mail_sy_orders_ng_styles' );
    wp_enqueue_style( 'os24_mail_sy_orders_ng_icon' );

    return "<app-root></app-root>";

}
add_shortcode( 'mail_sy_orders_list_app', 'os24_mail_sy_orders_list_shortcode_cb' );


function os24_mail_sy_form_shortcode_cb($attr)
{
    $lang = os24_mail_sy_valid_lang(isset($attr['lang']) ? $attr['lang'] : 'en');
    $src  = add_query_arg(array(
                'lang' => $lang,
                'rest' => rest_url('24os-mail-sy/v1'),
            ), OS24_MAIL_SY_PLUGIN_URL . '/app/');
    return '<iframe class="fix-heigt" id="order-form" src="'.esc_url($src).'" title="Post-Sy App" height="100%" width="100%"></iframe>';
}
add_shortcode( 'mail_sy_form_app', 'os24_mail_sy_form_shortcode_cb' );

function os24_mail_sy_tracker_shortcode_cb($attr)
{
    $lang = os24_mail_sy_valid_lang(isset($attr['lang']) ? $attr['lang'] : 'en');
    $args = array('lang' => $lang, 'rest' => rest_url('24os-mail-sy/v1'));
    $order_code_var = get_query_var('order_code');
    if(!empty($order_code_var) && preg_match('/^[DM]*[0-9]+$/', $order_code_var)){
        $args['order_code'] = $order_code_var;
    }
    $src = add_query_arg($args, OS24_MAIL_SY_PLUGIN_URL . '/tracker/');
    return '<iframe class="fix-heigt" id="tracker" src="'.esc_url($src).'" height="100%" width="100%"></iframe>';
}
add_shortcode( 'mail_sy_tracker_app', 'os24_mail_sy_tracker_shortcode_cb' );


function os24_mail_sy_add_custom_query_var($vars) 
{
    $vars[] = "order_code";
    return $vars;
}
add_filter('query_vars', 'os24_mail_sy_add_custom_query_var');

// Custom Endpoint API 
/**
 * READABLE   = 'GET';
 * CREATABLE  = 'POST';
 * EDITABLE   = 'POST, PUT, PATCH';
 * DELETABLE  = 'DELETE';
 * ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
 */
function os24_mail_sy_adding_custom_routes_rest_api() {
    $my_plugin = "24os-mail-sy";
    $version = "1";
    $namespace = $my_plugin.'/v'.$version;

    // get items when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/items
    register_rest_route( $namespace, 'items/(?P<lang>[a-z]+)',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_items',
            'permission_callback' => '__return_true'
        )
    ));

    // get selected items when POST request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/items
    register_rest_route( $namespace, 'items',array(
        array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_get_selected_items',
            'permission_callback' => '__return_true'
        )
    ));

    // get type selected items when POST request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/type-items
    register_rest_route( $namespace, 'type-items',array(
        array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_get_type_selected_items',
            'permission_callback' => '__return_true'
        )
    ));

    // get categories when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/category/
    register_rest_route( $namespace, 'category/(?P<lang>[a-z]+)',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_categories',
            'permission_callback' => '__return_true'
        )
    ));

    // get selected categories when POST request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/category/
    register_rest_route( $namespace, 'category',array(
        array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_get_selected_categories',
            'permission_callback' => '__return_true'
        )
    ));

    // add order when POST request
    // route : http://localhost/wp-json/24os-mail-sy/v1/order/
    register_rest_route( $namespace, 'order',array(
        array(
            'methods' =>  WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_add_order',
            'permission_callback' => '__return_true'
        )
    ));

    // get orders when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/order
    register_rest_route( $namespace, 'order',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_orders',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));

    // get order by id when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/order
    register_rest_route( $namespace, 'order/(?P<id>[0-9]+)',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_order',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));

    // get order status by order id when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/status
    register_rest_route( $namespace, 'status/(?P<order_id>[0-9]+)',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_order_status',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));

    // get order status by order code and email when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/track
    register_rest_route( $namespace, 'track/(?P<order_code>[DM]*[0-9]+)',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_track_order_status_by_code',
            'permission_callback' => '__return_true'
        )
    ));

    // get all statuses id when GET request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/status
    register_rest_route( $namespace, 'status',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_statuses',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));

    // add status to order when POST request
    // route : http://localhost/wp-json/24os-mail-sy/v1/status/
    register_rest_route( $namespace, 'status/(?P<order_id>[0-9]+)',array(
        array(
            'methods' =>  WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_add_order_status',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));
    /********************************
            language routes 
    *********************************/
    // get language when GET request
    // set language when POST request 
    // route : http://localhost/wp-json/24os-mail-sy/v1/lang
    register_rest_route( $namespace, 'lang',array(
        array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => 'os24_mail_sy_get_language',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        ),
        array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => 'os24_mail_sy_set_language',
            'permission_callback' => 'os24_mail_sy_permission_admin'
        )
    ));

}
add_action('rest_api_init', "os24_mail_sy_adding_custom_routes_rest_api");


function os24_mail_sy_get_items($request){
    global $wpdb; 
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';

    $officialDocsItems = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_item . " where item_type IN('officialDocsWithCats', 'officialDocsWithoutCats')"
        )
    ); 
    $lang = $request['lang'];
    if( $lang != "ar")
    {
        $temp = array();
        foreach ($officialDocsItems as $value) {
            $officialDocsItems_translate = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_item_translate . " where lang = '".esc_sql($lang)."' and item_id = ".$value->id
                )
            );
            array_push( $temp, $officialDocsItems_translate );
            
        }
        $officialDocsItems = $temp;
    }
    
    $mailItems = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_item . " where item_type = 'mail'"
        )
    ); 

    if( $lang != "ar")
    {
        $temp = array();
        foreach ($mailItems as $value) {
            $mailItems_translate = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_item_translate . " where lang = '".esc_sql($lang)."' and item_id = ".$value->id
                )
            );
            array_push( $temp, $mailItems_translate );
            
        }
        $mailItems = $temp;
    }

    $response = new WP_REST_Response(array("officialDocsItems"=>$officialDocsItems, "mailItems"=>$mailItems));
    $response->set_status(200);

    return $response;

}

function os24_mail_sy_get_type_selected_items($request){
    global $wpdb; 
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    if( isset($request['items']) && count($request['items']) > 0){
        $selected_items = $request['items'];
        $item_type = "";
        $flag = false;
        foreach ($selected_items as $selected_item_id) {
            if($request['lang'] == "ar"){
                $item = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_item . " where id = " . intval($selected_item_id)
                    )
                ); 
                $item_type = $item->item_type;  
            }
            else{
                $item_translate = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_item_translate . " where id = " . intval($selected_item_id)
                    )
                ); 
                $item = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_item . " where id = " . intval($item_translate->item_id)
                    )
                ); 
                $item_type = $item->item_type;  
            }

            if($item_type == "officialDocsWithCats"){
                $flag = true;
                break;
            }
        }

        $response = new WP_REST_Response(array("message"=>true, "flag"=>$flag));
        $response->set_status(200);

        return $response;
    }
    else{
        $response = new WP_REST_Response(array("message"=>"error"));
        $response->set_status(200);

        return $response;
    }
}

function os24_mail_sy_get_selected_categories($request){
    global $wpdb; 
    $table_name_category= $wpdb->prefix . '24os_mail_sy_category';
    $table_name_category_translate= $wpdb->prefix . '24os_mail_sy_category_translate';
    if(isset($request['cat'])){
        $selected_cat = $request['cat'];
        if($request['lang'] == "ar")
        {
            $cat = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_category . " where id = " . intval($selected_cat)
                )
            ); 

            $response = new WP_REST_Response(array("message"=>true, "selected_cat"=>$cat));
            $response->set_status(200);

            return $response;     
            
        }
        else{
            $cat = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_category_translate . " where id = " . intval($selected_cat)
                )
            );
            
            $response = new WP_REST_Response(array("message"=>true, "selected_cat"=>$cat));
            $response->set_status(200);

            return $response;
        }
    }
    else{
        $response = new WP_REST_Response(array("message"=>"error"));
        $response->set_status(200);

        return $response;
    }
}

function os24_mail_sy_get_selected_items($request){
    global $wpdb; 
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    $table_name_category= $wpdb->prefix . '24os_mail_sy_category';
    $table_name_category_translate= $wpdb->prefix . '24os_mail_sy_category_translate';

    if( isset($request['items']) && count($request['items']) > 0 && isset($request['cats'])){

        $selected_items = $request['items'];
        $selected_cats = $request['cats'];
        $sheet_number = 1;
        if(intval($request['sheet_number']) > 0){
            $sheet_number = $request['sheet_number'];
        }

        $total_price_number = 0.0;
        $total_price = '';
        $selected_items_array = array();
        $selected_cats_array = array();
        $array_duration = array();
        if($request['lang'] == "ar")
        {
            foreach ($selected_items as $item_id) {
                $item = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_item . " where id = " . intval($item_id)
                    )
                ); 
                array_push($selected_items_array, $item);
                if($item->item_price_total != ""){
                    
                    $total_price_number += number_format((float) filter_var( $item->item_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                    $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $item->item_price_total));
                }
                if($item->item_duration != ""){
                    array_push($array_duration, $item->item_duration);
                }
            }
            foreach ($selected_cats as $cat_id) {
                if(!empty($cat_id)){
                    $cat = $wpdb->get_row(
                        os24_mail_sy_q(
                            "SELECT * from ". $table_name_category . " where id = " . intval($cat_id)
                        )
                    ); 
    
                    if($cat->category_price_total != ""){
                        $number_format = number_format((float) filter_var( $cat->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                        $total_price_number += $number_format;
                        if($cat->category_name == "تصديق سفارة المانية"){
                            $total_price_number += ($sheet_number - 1) * 50;
                        }
                        if($cat->category_name == "مع ترجمة"){
                            $total_price_number += $number_format * ($sheet_number - 1);
                        }
                        $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $cat->category_price_total));
                    }
                    if($cat->category_duration != ""){
                        array_push($array_duration, $cat->category_duration);
                    }

                    array_push($selected_cats_array, $cat);

                }
                else
                {
                    array_push($selected_cats_array, "");

                }
                
            }
            
        }
        else{
            foreach ($selected_items as $item_id) {
                $item = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_item_translate . " where id = " . intval($item_id)
                    )
                ); 
                array_push($selected_items_array, $item);
                if($item->item_price_total != ""){
                    $total_price_number += number_format((float) filter_var( $item->item_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                    $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $item->item_price_total));
                }
                if($item->item_duration != ""){
                    array_push($array_duration, $item->item_duration);
                }
            }
            foreach ($selected_cats as $cat_id) {
                $cat = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_category_translate . " where id = " . intval($cat_id)
                    )
                ); 

                if($cat->category_price_total != ""){
                    $number_format = number_format((float) filter_var( $cat->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                    $total_price_number += $number_format; 
                    if($cat->category_name == "With the attestation of the German embassy" ||
                        $cat->category_name == "Mit Bescheinigung der deutschen Botschaft"){
                        $total_price_number += ($sheet_number - 1) * 50;
                    }
                    if($cat->category_name == "With translate" || 
                        $cat->category_name == "Mit übersetzen")
                    {
                        $total_price_number += $number_format * ($sheet_number - 1);
                    }
                    $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $cat->category_price_total));
                }
                if($cat->category_duration != ""){
                    array_push($array_duration, $cat->category_duration);
                }
                array_push($selected_cats_array, $cat);
            }
        }

        $days = array();
        $weeks = array();
        $total_duration = "";
        foreach ($array_duration as $value) {
            if(preg_match("/اسبوع/i", $value) ||
                preg_match("/week/i", $value) ||
                preg_match("/woche/i", $value)
            )
            {
                array_push($weeks, $value);
            }
            else{
                array_push($days, $value);
            }
        }
        if(count($weeks) > 0)
        {
            $total_duration = os24_mail_sy_calculate_max_duration_weeks($weeks);
        }
        elseif(count($weeks) == 0 && count($days) > 0){
            $total_duration = os24_mail_sy_calculate_max_duration_days($days);
        }
        
        
        $response = new WP_REST_Response(array("message"=>true, "selected_items"=>$selected_items_array, "selected_cats"=>$selected_cats_array, "total_price"=> $total_price, "total_duration"=>$total_duration));
        $response->set_status(200);

        return $response;
    }
    else{
        $response = new WP_REST_Response(array("message"=>"error"));
        $response->set_status(200);

        return $response;
    }
    

}

function os24_mail_sy_get_categories($request){
    global $wpdb; 
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';


    $categories_group_1 = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT ct.* FROM ". $table_name_category." AS ct 
                JOIN (SELECT category_group
                FROM ". $table_name_category."
                GROUP BY category_group
                HAVING category_group = '1' ) AS ct2
                ON ct.category_group = ct2.category_group"
        )

    ); 
    $categories_group_2 = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT ct.* FROM ". $table_name_category." AS ct 
                JOIN (SELECT category_group
                FROM ". $table_name_category."
                GROUP BY category_group
                HAVING category_group = '2' ) AS ct2
                ON ct.category_group = ct2.category_group"
        )

    ); 
    $lang = $request['lang'];
    if( $lang != "ar")
    {
        $temp = array();
        foreach ($categories_group_1 as $value) {
            $categories_group_1_translate = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT ct.* FROM ". $table_name_category_translate." AS ct 
                    JOIN (SELECT category_group
                    FROM ". $table_name_category_translate." 
                    GROUP BY category_group
                    HAVING category_group = '1' ) AS ct2
                    ON ct.category_group = ct2.category_group and 
                    lang = '".esc_sql($lang)."' and category_id = ".$value->id
                )
            );
            array_push( $temp, $categories_group_1_translate );
            
        }
        $categories_group_1 = $temp;

        $temp2 = array();
        foreach ($categories_group_2 as $value) {
            $categories_group_2_translate = $wpdb->get_row(
                os24_mail_sy_q(
                    "SELECT ct.* FROM ". $table_name_category_translate." AS ct 
                    JOIN (SELECT category_group
                    FROM ". $table_name_category_translate." 
                    GROUP BY category_group
                    HAVING category_group = '2' ) AS ct2
                    ON ct.category_group = ct2.category_group and 
                    lang = '".esc_sql($lang)."' and category_id = ".$value->id
                )
            );
            array_push( $temp2, $categories_group_2_translate );
            
        }
        $categories_group_2 = $temp2;
    }

    $response = new WP_REST_Response(array("categories_group_1"=>$categories_group_1,"categories_group_2"=>$categories_group_2));
    $response->set_status(200);

    return $response;

}


/**
 * Save the image on the server.
 */
function save_image( $base64_img, $img_type, $no, $firstname, $lastname, $type ) {
    if(empty($base64_img)){
        return "";
    }
    $img_type = ($img_type === 'jpg') ? 'jpeg' : $img_type;
    if(!in_array($img_type, array('png','jpeg'), true)){
        return "";
    }

    $img     = preg_replace('#^data:image/[a-z]+;base64,#i', '', $base64_img);
    $img     = str_replace( ' ', '+', $img );
    // max. ~7 MB of base64 text (~5 MB image)
    if(strlen($img) > 7000000){
        return "";
    }
    $decoded = base64_decode( $img, true );
    if($decoded === false){
        return "";
    }
    // the content must really be a png/jpeg image
    $info = @getimagesizefromstring($decoded);
    if($info === false || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG), true)){
        return "";
    }
    $ext = ($info[2] === IMAGETYPE_PNG) ? 'png' : 'jpg';

    $upload_dir = wp_upload_dir();
    $upload_path = trailingslashit($upload_dir['path']);

    // random, unguessable file name (images contain ID documents)
    $filename = 'os24ms_' . sanitize_key($type) . '_' . intval($no) . '_' . wp_generate_password(24, false, false) . '.' . $ext;

    if(file_put_contents( $upload_path . $filename, $decoded ) === false){
        return "";
    }
    return trailingslashit($upload_dir['url']) . $filename;
}

function check_image( $base64_img ) {

    if(!empty($base64_img)){
    
        $img_type = explode('data:image/', $base64_img);
        $img_type = explode(';base64', $img_type[1]);
        if($img_type[0] == 'png' || $img_type[0] == 'jpeg' || $img_type[0] == 'jpg'){
            
            return $img_type[0];
        }
        else{
            return "error";
        }
        
    }
    else {
        return "";
    }
	
}

function os24_mail_sy_add_order($request){
    global $wpdb; 
    // max. 10 orders per IP per hour
    if(os24_mail_sy_rate_limited('add_order', 10, HOUR_IN_SECONDS)){
        return os24_mail_sy_error_response("too many requests", 429);
    }
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';

    
    if( isset($request['data']['personalInfo']['firstname']) && !empty($request['data']['personalInfo']['firstname']) && 
        isset($request['data']['personalInfo']['lastname']) && !empty($request['data']['personalInfo']['lastname']) && 
        isset($request['data']['personalInfo']['address']) && !empty($request['data']['personalInfo']['address']) && 
        isset($request['data']['personalInfo']['phone']) && !empty($request['data']['personalInfo']['phone']))
    {
        $firstname = trim($request['data']['personalInfo']['firstname']);
        $lastname = trim($request['data']['personalInfo']['lastname']);
        $address = sanitize_text_field(trim($request['data']['personalInfo']['address']));
        $phone = sanitize_text_field(trim(preg_replace("/\s/", "", $request['data']['personalInfo']['phone'])));
        
        
        // if( !preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/im", $phone) ){
        //     $response = new WP_REST_Response(array("message"=>"error phone"));
        //     $response->set_status(200);

        //     return $response;
        // } 
        if( !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$firstname) || !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$lastname) )
        {
            $response = new WP_REST_Response(array("message"=>"error fullname"));
            $response->set_status(200);

            return $response;
        }

        $company_account = 'IBAN: '.OS24_MAIL_SY_COMPANY_IBAN;
        $company_name = 'NAME: '.OS24_MAIL_SY_COMPANY_NAME;
        $company_BIC = 'BIC: '.OS24_MAIL_SY_COMPANY_BIC;
        $verwendungszweck = 'Verwendungszweck: Nr. ';
        $company_address = OS24_MAIL_SY_COMPANY_ADDRESS;

        $email = '';
        if(isset($request['data']['personalInfo']['email']) && !empty($request['data']['personalInfo']['email']))
        {
            $email = strtolower(trim($request['data']['personalInfo']['email']));
            if(!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,10}$/ix", $email))
            {
                $response = new WP_REST_Response(array("message"=>"error email"));
                $response->set_status(200);

                return $response;
            }
            
        }
        $firstname_order = $firstname;
        $lastname_order = $lastname;
        $phone_order = $phone;
        $address_order = $address;
        $email_order = $email;
        
        $images_order = array();

        $id_number_order = '';
        $total_price_number = 0;
        $total_price = '';
        $order_id = 0;
        $order_no = '';
        $body = '';
        $body2 = '';
        if(isset($request['data']['officialDocsInfo'])){
            $items = $request['data']['officialDocsInfo']['g1']['items'];
            $categories1 = $request['data']['officialDocsInfo']['g1']['categories1'];
            $categories2 = $request['data']['officialDocsInfo']['g1']['categories2'];
            $notes = sanitize_textarea_field(trim($request['data']['officialDocsInfo']['g1']['notes']));
            $id_number = sanitize_text_field(trim($request['data']['officialDocsInfo']['g2']['id_number']));
            $sheet_number = $request['data']['officialDocsInfo']['g1']['sheet_number'];
            $translate_language = sanitize_text_field($request['data']['officialDocsInfo']['g1']['translate_language']);
            $images = $request['data']['images'];
            $total_duration = sanitize_text_field($request['data']['total_duration']);

            if($request['data']['lang'] == "ar"){
                $total_duration .= " عمل ";
                $total_duration .= " (قد يستغرق الأمر وقتًا أطول من ذلك بسبب الظروف الحالية))";
            }elseif($request['data']['lang'] == "de"){
                $total_duration .= " arbeiten";
                $total_duration .= " (kann aufgrund der aktuellen Umstände länger dauern)";
            }
            else{
                $total_duration .= " working";
                $total_duration .= " (may take longer than that due to current circumstances)";
            }
            array_push($images_order, $images[0]);
            array_push($images_order, $images[1]);

            if( !empty($id_number) && !preg_match("/\d/u", $id_number) ){
                $response = new WP_REST_Response(array("message"=>"error id number"));
                $response->set_status(200);

                return $response;
            }
            $id_number_order = $id_number;

            if( intval($sheet_number) < 1 ){
                $response = new WP_REST_Response(array("message"=>"error sheet number"));
                $response->set_status(200);

                return $response;
            }
            $sheet_number_order = intval($sheet_number);

            if( isset($items) && count($items) > 0 &&
                isset($categories1) && isset($categories2) ){

                $items_order = array();
                $items_order_id = array();
                $items_info_order = array();
                $category_order = "";
                $category_order_id = array();
                $attestation = false;
                if($request['data']['lang'] == "ar")
                {
                    foreach ($items as $item_id) {
                        $item = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_item . " where id = " . intval($item_id)
                            )
                        ); 
                        array_push($items_order, $item->item_name);
                        array_push($items_order_id, $item->id);
                        array_push($items_info_order, $item);
                        if($item->item_price_total != ""){
                            $total_price_number += number_format((float) filter_var( $item->item_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $item->item_price_total));
                        }
                    }

                    if(!empty($categories1) && !empty($categories2))
                    {
                        $category_order1 = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_category . " where id = " . intval($categories1)
                            )
                        ); 
                        $category_order2 = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_category . " where id = " . intval($categories2)
                            )
                        ); 
                        $category_order =  $category_order1->category_name ." , " . $category_order2->category_name;
                    
                        array_push($category_order_id, $category_order1->id);
                        array_push($category_order_id, $category_order2->id);
                    
                        if($category_order1->category_price_total != ""){
                            $number_format = number_format((float) filter_var( $category_order1->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            $total_price_number += $number_format;
                            if($category_order1->category_name == "تصديق سفارة المانية"){
                                $total_price_number += ($sheet_number_order - 1) * 50;
                                $attestation = true;
                            }
                        
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $category_order1->category_price_total));

                        }
                    
                        if($category_order2->category_price_total != ""){
                            $number_format = number_format((float) filter_var( $category_order2->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            $total_price_number += $number_format;
                            if($category_order2->category_name == "مع ترجمة"){
                                $total_price_number += $number_format * ($sheet_number_order - 1);
                            }
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $category_order2->category_price_total));
                        }

                    }
                }
                else{
                    foreach ($items as $item_id) {
                        $item = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_item_translate . " where lang = '".esc_sql(os24_mail_sy_valid_lang($request['data']['lang']))."' and id = " . intval($item_id)
                            )
                        ); 
                        array_push($items_order, $item->item_name);
                        array_push($items_order_id, $item->id);
                        array_push($items_info_order, $item);
                        if($item->item_price_total != ""){
                            $total_price_number += number_format((float) filter_var( $item->item_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $item->item_price_total));
                        }
                    }

                    if(!empty($categories1) && !empty($categories2))
                    {
                        $category_order1 = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_category_translate . " where lang = '".esc_sql(os24_mail_sy_valid_lang($request['data']['lang']))."' and id = " . intval($categories1)
                            )
                        ); 
                        $category_order2 = $wpdb->get_row(
                            os24_mail_sy_q(
                                "SELECT * from ". $table_name_category_translate . " where lang = '".esc_sql(os24_mail_sy_valid_lang($request['data']['lang']))."' and id = " . intval($categories2)
                            )
                        ); 
                        $category_order = $category_order1->category_name ." , " . $category_order2->category_name;
                        array_push($category_order_id, $category_order1->id);
                        array_push($category_order_id, $category_order2->id);

                        if($category_order1->category_price_total != ""){
                            $total_price_number += number_format((float) filter_var( $category_order1->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            if($category_order1->category_name == "With the attestation of the German embassy" ||
                                $category_order1->category_name == "Mit Bescheinigung der deutschen Botschaft"){
                                $total_price_number += ($sheet_number_order - 1) * 50;
                                $attestation = true;
                                
                            }
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $category_order1->category_price_total));
                        }
                        if($category_order2->category_price_total != ""){
                            $number_format = number_format((float) filter_var( $category_order2->category_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                            $total_price_number += $number_format;
                            if($category_order2->category_name == "With translate"|| 
                                $category_order2->category_name == "Mit übersetzen"){
                                $total_price_number += $number_format * ($sheet_number_order - 1);
                            }
                            $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $category_order2->category_price_total));
                        }
                    }
                }

                $type_image_front = check_image( $images_order[0] );
                $type_image_back = check_image( $images_order[1] );

                if($type_image_front != "error" && $type_image_back != "error"){

                    $sql = $wpdb->insert( $table_name_order, array(
                        "email" => $email_order,
                        "firstname" => $firstname_order, 
                        "lastname" => $lastname_order, 
                        "address_from" => $address_order, 
                        "sheet_number" => $sheet_number_order, 
                        "id_number"=>$id_number_order,
                        "phone" => $phone_order,  
                        "notes" => $notes,
                        "cost"=>$total_price,
                        "lang"=>$request['data']['lang'],
                        "total_duration"=>$total_duration,
                        "type"=> "docs",
                        "items" => json_encode(array("items_order"=>$items_order_id, "category_order"=>$category_order_id, "translate_language"=>$translate_language)) ,
                    ));
                    $order_id = $wpdb->insert_id;
                    $order_no = "D".date('y').$order_id;
                    $image_front = save_image( $images_order[0], $type_image_front, $order_no, $firstname_order, $lastname_order, "frontside");
                    $image_back = save_image( $images_order[1], $type_image_back, $order_no, $firstname_order, $lastname_order, "backside");

                    $wpdb->update( $table_name_order, array(
                        "no"=> $order_no,
                        'img' => json_encode(array("front_side"=>$image_front, "back_side"=>$image_back))),
                        array( 'id' => $order_id )
                    );

                    if(get_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION) == 'ar'){
                
                        $body .= '<html> 
                        <body>
                            <table style="width:100%">
                                <tr>
                                    <th style="text-align:start;width:130px;">رقم الطلب:</th>
                                    <td style="text-align:start;">'. $order_no .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الاسم:</th>
                                    <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الهاتف:</th>
                                    <td style="text-align:start;">'. $phone_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">البريد الالكتروني:</th>
                                    <td style="text-align:start;">'. $email_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الرقم الوطني:</th>
                                    <td style="text-align:start;">'. $id_number_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">العنوان:</th>
                                    <td style="text-align:start;">'. $address_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">نوع الطلب:</th>
                                    <td style="text-align:start;">وثائق رسمية</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الهوية الشخصية:</th>
                                    <td style="display:flex;">
                                        <a href="'.$image_front.'">
                                            <img src="'.$image_front.'" style="width:250px;height:160px;" alt="frontside">
                                        </a>
                                        <a href="'.$image_back.'">
                                            <img src="'.$image_back.'" style="width:250px;height:160px;" alt="backside">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">العناصر المطلوبة:</th>
                                    <td style="text-align:start;">'. implode(", ", $items_order) .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">تصنيف:</th>
                                    <td style="text-align:start;">'. $category_order .'</td>
                                </tr>';
                                if($translate_language != ""){
                                    $body .= '<tr>
                                            <th style="text-align:start;width:130px;">لغة الترجمة:</th>
                                            <td style="text-align:start;">'. $translate_language .'</td>
                                        </tr>';
                                }
                                $body .= '<tr>
                                    <th style="text-align:start;width:130px;">ملاحظات:</th>
                                    <td style="text-align:start;">'. $notes .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">عدد الاوراق:</th>
                                    <td style="text-align:start;">'. $sheet_number_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">التكلفة الكلية:</th>
                                    <td style="text-align:start;">'. $total_price .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">مدة العمل الكلية:</th>
                                    <td style="text-align:start;">'. $total_duration .'</td>
                                </tr>
                            </table>         
                        </body> 
                        </html>'; 
                        
                        
                    }

                    // $order_statuses_link = get_site_url().'/ar/order-tracker-mail-sy-ar/?order_code='. $order_no;
                    // $order_statuses_link = '<a href="'.$order_statuses_link.'" target="_blank">'.$order_statuses_link.'</a>';
                    if($request['data']['lang'] == "ar")
                    {
                        $data_order['order_id'] = 'رقم الطلب:'. $order_no;
                        $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';

                        $data_order['msg'] .= 'يرجى تحويل مبلغ';
                        $data_order['msg'] .= ' '. $total_price . ' ';
                        $data_order['msg'] .= 'لحساب الشركة تحت رقم الطلب وسيتم المباشرة بالاوراق المطلوبة';
                        // $data_order['msg'] .= '<br>'.'يمكنك تتبع حالة الطلب عبر الرابط التالي';
                        // $data_order['msg'] .= '<br>'.$order_statuses_link;
                        $data_order['msg'] .= '<br><br>'.'معلومات البنك';
                        $data_order['msg'] .= '<br>- '. $company_name;
                        $data_order['msg'] .= '<br>- '. $company_account;
                        $data_order['msg'] .= '<br>- '. $company_BIC;
                        $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;

                        $data_order['msg'] .= '<small><br><br>'.' - جميع الأسعار تتضمن ضريبة القيمة المضافة MwSt';
                        $data_order['msg'] .= '<br>'.'- في حال لم يتم تحويل المبلغ لحساب الشركة تحت رقم الطلب او اسم صاحب الطلب لن يتم تنفيذ المعاملة';
                        $data_order['msg'] .= '<br>'.'- الاسعار متضمنة سعر البريد للعنوان في جميع انحاء العالم في حال البريد العادي اما في حال الرغبة باستلام بريدك بالبريد السريع يوجد رسوم اضافية';
                        if($attestation === true)
                        {
                            $data_order['msg'] .= '<br>'.'- تصديق السفارة يضيف الى مدة العمل 4-8 اسابيع عمل';
                        }
                        $data_order['msg'] .= '<br>'.'- مدة العمل الكلية: '.$total_duration;
                        $data_order['msg'] .= '<br>- في حال الغائكم للطلب في وقت لاحق يرجى أخذ العلم أنه سيتم خصم 10% أجور التحويل مدة استرجاع المبلغ المدفوع قد تتجاوز ال 3 اسابيع';
                        $data_order['msg'] .= '<br>- في حال وجود أي خطأ في الطلب, يمكنكم ببساطة تجاهل الطلب وعدم تسديده, وانشاء طلب جديد. سيتم مسح الطلب أوتوماتيكيا في حال عدم التسديد بعد 10 أيام من تقديمه.</small>';

                        $body2 .= '<html> 
                        <body>
                            <table style="width:100%">
                                <tr>
                                    <th style="text-align:start;width:130px;">رقم الطلب:</th>
                                    <td style="text-align:start;">'. $order_no .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الاسم:</th>
                                    <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الهاتف:</th>
                                    <td style="text-align:start;">'. $phone_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">البريد الالكتروني:</th>
                                    <td style="text-align:start;">'. $email_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الرقم الوطني:</th>
                                    <td style="text-align:start;">'. $id_number_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">العنوان:</th>
                                    <td style="text-align:start;">'. $address_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">نوع الطلب:</th>
                                    <td style="text-align:start;">وثائق رسمية</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">الهوية الشخصية:</th>
                                    <td style="display:flex;">
                                        <a href="'.$image_front.'">
                                            <img src="'.$image_front.'" style="width:250px;height:160px;" alt="frontside">
                                        </a>
                                        <a href="'.$image_back.'">
                                            <img src="'.$image_back.'" style="width:250px;height:160px;" alt="backside">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">العناصر المطلوبة:</th>
                                    <td style="text-align:start;">'. implode(", ", $items_order) .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">تصنيف:</th>
                                    <td style="text-align:start;">'. $category_order .'</td>
                                </tr>';
                                if($translate_language != ""){
                                    $body2 .= '<tr>
                                            <th style="text-align:start;width:130px;">لغة الترجمة:</th>
                                            <td style="text-align:start;">'. $translate_language .'</td>
                                        </tr>';
                                }
                                $body2 .= '<tr>
                                    <th style="text-align:start;width:130px;">ملاحظات:</th>
                                    <td style="text-align:start;">'. $notes .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">عدد الاوراق:</th>
                                    <td style="text-align:start;">'. $sheet_number_order .'</td>
                                </tr>
                            </table>         
                        '; 
                    }
                    else if($request['data']['lang'] == "en"){
                        $data_order['order_id'] = 'order number:'. $order_no;
                        $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';
                        $data_order['msg'] .= 'Please transfer an amount '. $total_price . ' For the account of the company under the application number, and the required papers will be processed.';
                        // $data_order['msg'] .= '<br>You can track the status of your order via the following link: '.$order_statuses_link;
                        $data_order['msg'] .= '<br><br>'.'Info Bank';
                        $data_order['msg'] .= '<br>- '. $company_name;
                        $data_order['msg'] .= '<br>- '. $company_account;
                        $data_order['msg'] .= '<br>- '. $company_BIC;
                        $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;
                        $data_order['msg'] .= '<small><br><br>- all prices include value added tax MwSt.';
                        $data_order['msg'] .= '<br>- if the amount is not transferred to the company’s account under the order number or the name of the applicant, the transaction will not be executed.';
                        $data_order['msg'] .= '<br>- prices include postage to addresses worldwide in the case of regular mail. However, if you wish to receive your mail by express mail, there are additional fees.';
                        if($attestation === true)
                        {
                            $data_order['msg'] .= '<br>- embassy attestation adds to work duration 4-8 working week';
                        }
                        $data_order['msg'] .= '<br>'.'- total working duration: '.$total_duration;
                        $data_order['msg'] .= '<br>- If you cancel the order later, please note that 10% of the transfer fees will be deducted. The refund period may exceed 3 weeks.';
                        $data_order['msg'] .= '<br>- If there is any error in the order, you can simply ignore the order, not pay it, and create a new order. The application will be automatically deleted if payment is not made 10 days after submission.</small>';

                        $body2 .= '<html> 
                        <body>
                            <table style="width:100%">
                                <tr>
                                    <th style="text-align:start;width:130px;">order number:</th>
                                    <td style="text-align:start;">'. $order_no .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">fullname:</th>
                                    <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">phone:</th>
                                    <td style="text-align:start;">'. $phone_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">email:</th>
                                    <td style="text-align:start;">'. $email_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">ID Number:</th>
                                    <td style="text-align:start;">'. $id_number_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">address:</th>
                                    <td style="text-align:start;">'. $address_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">order type:</th>
                                    <td style="text-align:start;">Official documents</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">personal identification:</th>
                                    <td style="display:flex;">
                                        <a href="'.$image_front.'">
                                            <img src="'.$image_front.'" style="width:250px;height:160px;" alt="frontside">
                                        </a>
                                        <a href="'.$image_back.'">
                                            <img src="'.$image_back.'" style="width:250px;height:160px;" alt="backside">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">items:</th>
                                    <td style="text-align:start;">'. implode(", ", $items_order) .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">category:</th>
                                    <td style="text-align:start;">'. $category_order .'</td>
                                </tr>';
                                if($translate_language != ""){
                                    $body2 .= '<tr>
                                            <th style="text-align:start;width:130px;">Translation language:</th>
                                            <td style="text-align:start;">'. $translate_language .'</td>
                                        </tr>';
                                }
                                $body2 .= '<tr>
                                    <th style="text-align:start;width:130px;">notes:</th>
                                    <td style="text-align:start;">'. $notes .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">sheets number:</th>
                                    <td style="text-align:start;">'. $sheet_number_order .'</td>
                                </tr>
                                
                            </table>         
                        '; 
                    }
                    else if($request['data']['lang'] == "de"){
                        $data_order['order_id'] = 'Bestellnummer:'. $order_no;
                        $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';
                        $data_order['msg'] .= 'Bitte überweisen Sie einen Betrag '. $total_price . ' Auf Rechnung des Unternehmens unter der Antragsnummer werden die erforderlichen Unterlagen bearbeitet.';
                        // $data_order['msg'] .= '<br>Den Status Ihrer Bestellung können Sie über den folgenden Link verfolgen: '.$order_statuses_link;
                        $data_order['msg'] .= '<br><br>'.'Infobank';
                        $data_order['msg'] .= '<br>- '. $company_name;
                        $data_order['msg'] .= '<br>- '. $company_account;
                        $data_order['msg'] .= '<br>- '. $company_BIC;
                        $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;
                        $data_order['msg'] .= '<small><br><br>- alle Preise die Mehrwertsteuer enthalten MwSt';
                        $data_order['msg'] .= '<br>- Wenn der Betrag nicht unter der Bestellnummer oder dem Namen des Antragstellers auf das Konto des Unternehmens überwiesen wird, wird die Transaktion nicht ausgeführt.';
                        $data_order['msg'] .= '<br>- Im Preis ist der Portopreis für Adressen weltweit bei regulärer Post enthalten. Wenn Sie Ihre Post jedoch per Expresspost erhalten möchten, fallen zusätzliche Gebühren an.';
                        if($attestation === true)
                        {
                            $data_order['msg'] .= '<br>- die bescheinigung der botschaft erhöht die arbeitsdauer um 4-8 arbeitswochen';
                        }
                        $data_order['msg'] .= '<br>'.'- Gesamtarbeitsdauer: '.$total_duration;
                        $data_order['msg'] .= '<br>- Wenn Sie die Bestellung später stornieren, beachten Sie bitte, dass 10 % der Überweisungsgebühren abgezogen werden. Die Rückerstattungsfrist kann 3 Wochen überschreiten.';
                        $data_order['msg'] .= '<br>- Wenn die Bestellung einen Fehler enthält, können Sie die Bestellung einfach ignorieren, nicht bezahlen und eine neue Bestellung erstellen. Die Bewerbung wird automatisch gelöscht, wenn die Zahlung nicht 10 Tage nach Einreichung erfolgt.</small>';

                        $body2 .= '<html> 
                        <body>
                            <table style="width:100%">
                                <tr>
                                    <th style="text-align:start;width:130px;">Bestellnummer:</th>
                                    <td style="text-align:start;">'. $order_no .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">vollständiger Name:</th>
                                    <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Telefon:</th>
                                    <td style="text-align:start;">'. $phone_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">email:</th>
                                    <td style="text-align:start;">'. $email_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">ID-Nummer:</th>
                                    <td style="text-align:start;">'. $id_number_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Adresse:</th>
                                    <td style="text-align:start;">'. $address_order .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Auftragsart:</th>
                                    <td style="text-align:start;">Offizielle Dokumente</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Personalausweis:</th>
                                    <td style="display:flex;">
                                        <a href="'.$image_front.'">
                                            <img src="'.$image_front.'" style="width:250px;height:160px;" alt="frontside">
                                        </a>
                                        <a href="'.$image_back.'">
                                            <img src="'.$image_back.'" style="width:250px;height:160px;" alt="backside">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Artikel:</th>
                                    <td style="text-align:start;">'. implode(", ", $items_order) .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Kategorie:</th>
                                    <td style="text-align:start;">'. $category_order .'</td>
                                </tr>';
                                if($translate_language != ""){
                                    $body2 .= '<tr>
                                            <th style="text-align:start;width:130px;">Übersetzungssprache:</th>
                                            <td style="text-align:start;">'. $translate_language .'</td>
                                        </tr>';
                                }
                                $body2 .= '<tr>
                                    <th style="text-align:start;width:130px;">Anmerkungen:</th>
                                    <td style="text-align:start;">'. $notes .'</td>
                                </tr>
                                <tr>
                                    <th style="text-align:start;width:130px;">Blattnummer:</th>
                                    <td style="text-align:start;">'. $sheet_number_order .'</td>
                                </tr>
                                
                            </table>         
                        '; 
                    }
                }
                else{
                    $response = new WP_REST_Response(array("message"=>"image must to be (.jpeg, .png, .jpg)"));
                    $response->set_status(200);
    
                    return $response;
                }
            
            }
            else{
                $response = new WP_REST_Response(array("message"=>"error"));
                $response->set_status(200);

                return $response;
            }
            
            wp_mail(OS24_MAIL_SY_ADMIN_EMAIL_DOCS, 'New Order: No. '.$order_no, $body); //this function to send email but not work without plugin WP Mail SMTP

        }
        elseif(isset($request['data']['mailInfo']))
        {
            $rec_address = sanitize_text_field(trim($request['data']['mailInfo']['g2']['rec_address']));
            $mail_type = $request['data']['mailInfo']['g1']['mail_type'];
            $city = trim($request['data']['mailInfo']['g2']['city']);
            $rec_firstname = trim($request['data']['mailInfo']['g2']['rec_firstname']);
            $rec_parentname = trim($request['data']['mailInfo']['g2']['rec_parentname']);
            $rec_lastname = trim($request['data']['mailInfo']['g2']['rec_lastname']);
            $rec_phone = sanitize_text_field(trim(preg_replace("/\s/", "", $request['data']['mailInfo']['g2']['rec_phone'])));

            $total_duration = sanitize_text_field($request['data']['total_duration']);

           if($request['data']['lang'] == "ar"){
                $total_duration .= " عمل ";
                $total_duration .= " (قد يستغرق الأمر وقتًا أطول من ذلك بسبب الظروف الحالية))";
            }elseif($request['data']['lang'] == "de"){
                $total_duration .= " arbeiten";
                $total_duration .= " (kann aufgrund der aktuellen Umstände länger dauern)";
            }
            else{
                $total_duration .= " working";
                $total_duration .= " (may take longer than that due to current circumstances)";
            }

            if( isset($rec_address) && !empty($rec_address) &&
                isset($mail_type) && !empty($mail_type) &&
                isset($city) && !empty($city) && isset($rec_phone) && !empty($rec_phone) && 
                isset($rec_firstname) && !empty($rec_firstname) && 
                isset($rec_parentname) && !empty($rec_parentname) && 
                isset($rec_lastname) && !empty($rec_lastname) ){
                // if( !preg_match("/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,8}$/im", $rec_phone) ){
                //     $response = new WP_REST_Response(array("message"=>"error phone"));
                //     $response->set_status(200);
        
                //     return $response;
                // } 
                if( !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$rec_firstname) ||
                    !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$rec_parentname) ||
                    !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$rec_lastname) )
                {
                    $response = new WP_REST_Response(array("message"=>"error fullname"));
                    $response->set_status(200);
        
                    return $response;
                }
                if( !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$city) )
                {
                    $response = new WP_REST_Response(array("message"=>"error city"));
                    $response->set_status(200);
        
                    return $response;
                }

                $item_order = '';
                if($request['data']['lang'] == "ar"){
                    $item_order = $wpdb->get_results(
                        os24_mail_sy_q(
                            "SELECT * from ". $table_name_item . " where id = " . intval($mail_type)
                        )
                    ); 
                }
                else{
                    $item_order = $wpdb->get_results(
                        os24_mail_sy_q(
                            "SELECT * from ". $table_name_item_translate . " where lang = '".esc_sql(os24_mail_sy_valid_lang($request['data']['lang']))."' and id = " . intval($mail_type)
                        )
                    );
                }
                if($item_order[0]->item_price_total != ""){
                    $total_price_number += number_format((float) filter_var(  $item_order[0]->item_price_total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ), 2, '.', '');
                    $total_price = $total_price_number . preg_replace("/\./u", "", preg_replace("/\d/u", "", $item_order[0]->item_price_total));
                }
                $sql = $wpdb->insert( $table_name_order, array(
                    "email" => $email_order,
                    "firstname" => $firstname_order, 
                    "lastname" => $lastname_order, 
                    "address_from" => $address_order, 
                    "address_to" => trim($rec_address),
                    "city" => $city,  
                    "phone" => $phone_order, 
                    "rec_firstname" => $rec_firstname . " " . $rec_parentname, 
                    "rec_lastname" => $rec_lastname, 
                    "rec_phone" => $rec_phone, 
                    "cost"=>$total_price, 
                    "total_duration"=>$total_duration,
                    "lang"=>$request['data']['lang'],
                    "type"=> "mail",
                    "items" => json_encode(array("items_order"=>[$item_order[0]->id], "category_order"=>array())),
                ));
                $order_id = $wpdb->insert_id;
                $order_no = "M".date('y').$order_id;
                $wpdb->update( $table_name_order, array(
                    "no"=> $order_no,
                    'img' => json_encode(array("front_side"=>$image_front, "back_side"=>$image_back))),
                    array( 'id' => $order_id )
                );

                // $order_statuses_link = get_site_url().'/ar/order-tracker-mail-sy-ar/?order_code='. $order_no;
                // $order_statuses_link = '<a href="'.$order_statuses_link.'" target="_blank">'.$order_statuses_link.'</a>';

                if($request['data']['lang'] == "ar")
                {
                    $data_order['order_id'] = 'رقم الطلب:'.$order_no;
                    $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';
                    $data_order['msg'] .= 'يرجى تحوبل مبلغ';
                    $data_order['msg'] .= ' '.$total_price.' ';
                    $data_order['msg'] .= 'لحساب الشركة وارسال البريد المراد ارساله الى سوريا بالبريد الداخلي الالماني الى عنوان الشركة';
                    $data_order['msg'] .= '<br>'. 'Postlink-Sy: '. $company_address;
                    // $data_order['msg'] .= '<br>'.'يمكنك تتبع حالة الطلب عبر الرابط التالي';
                    // $data_order['msg'] .= '<br>'.$order_statuses_link;
                    $data_order['msg'] .= '<br><br>'.'معلومات البنك';
                    $data_order['msg'] .= '<br>- '. $company_name;
                    $data_order['msg'] .= '<br>- '. $company_account;
                    $data_order['msg'] .= '<br>- '. $company_BIC;
                    $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;
                    
                    $data_order['msg'] .= '<small><br><br>'.' - جميع الأسعار تتضمن ضريبة القيمة المضافة MwSt';
                    $data_order['msg'] .= '<br>'.'- في حال لم يتم تحويل المبلغ لحساب الشركة تحت رقم الطلب او اسم صاحب الطلب لن يتم تنفيذ المعاملة';
                    $data_order['msg'] .= '<br>'.'- مدة البريد تحتسب من تاريخ وصول الحوالة ووصول البريد لشركتنا';
                    $data_order['msg'] .= '<br>'.'- يتم الاستلام في سوريا عن طريق شركة القدموس';
                    
                    $data_order['msg'] .= '<br>- في حال الغائكم للطلب في وقت لاحق يرجى أخذ العلم أنه سيتم خصم 10% أجور التحويل مدة استرجاع المبلغ المدفوع قد تتجاوز ال 3 اسابيع';
                    $data_order['msg'] .= '<br>- في حال وجود أي خطأ في الطلب, يمكنكم ببساطة تجاهل الطلب وعدم تسديده, وانشاء طلب جديد. سيتم مسح الطلب أوتوماتيكيا في حال عدم التسديد بعد 10 أيام من تقديمه.</small>';

                    $body2 .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">رقم الطلب:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">الاسم:</th>
                                <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">الهاتف:</th>
                                <td style="text-align:start;">'. $phone_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">البريد الالكتروني:</th>
                                <td style="text-align:start;">'. $email_order .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">نوع الطلب:</th>
                                <td style="text-align:start;">بريد</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">العناصر المطلوبة:</th>
                                <td style="text-align:start;">'. $item_order[0]->item_name .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">عنوان المرسل:</th>
                                <td style="text-align:start;">'. $address_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">اسم المستلم:</th>
                                <td style="text-align:start;">'. $rec_firstname . ' ' . $rec_parentname .' '.$rec_lastname.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">هاتف المستلم:</th>
                                <td style="text-align:start;">'. $rec_phone .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">عنوان المستلم:</th>
                                <td style="text-align:start;">'. $rec_address .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">المحافظة:</th>
                                <td style="text-align:start;">'. $city .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">مدة العمل:</th>
                                <td style="text-align:start;">'. $total_duration .'</td>
                            </tr>
                        </table>';

                }
                else if($request['data']['lang'] == "en"){
                    $data_order['order_id'] = 'order number:'.$order_no;
                    $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';
                    $data_order['msg'] .= 'Please transfer an amount';
                    $data_order['msg'] .= ' '.$total_price.' ';
                    $data_order['msg'] .= 'For the account of the company and send the mail to be sent to Syria by German internal mail to the company address';
                    $data_order['msg'] .= '<br>'. 'Postlink-Sy: '. $company_address;
                    // $data_order['msg'] .= '<br>You can track the status of your order via the following link: '.$order_statuses_link;
                    $data_order['msg'] .= '<br><br>'.'Info Bank';
                    $data_order['msg'] .= '<br>- '. $company_name;
                    $data_order['msg'] .= '<br>- '. $company_account;
                    $data_order['msg'] .= '<br>- '. $company_BIC;
                    $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;
                    
                    $data_order['msg'] .= '<small><br><br>- all prices include value added tax MwSt.';
                    $data_order['msg'] .= '<br>- if the amount is not transferred to the company’s account under the order number or the name of the applicant, the transaction will not be executed.';
                    $data_order['msg'] .= '<br>- the postage period is calculated from the date of arrival of the transfer and the arrival of the mail to our company.';
                    $data_order['msg'] .= '<br>- receipt is made in Syria via Al-Qadmous Company.';
                    $data_order['msg'] .= '<br>- If you cancel the order later, please note that 10% of the transfer fees will be deducted. The refund period may exceed 3 weeks.';
                    $data_order['msg'] .= '<br>- If there is any error in the order, you can simply ignore the order, not pay it, and create a new order. The application will be automatically deleted if payment is not made 10 days after submission.</small>';

                    $body2 .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">order number:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">fullname:</th>
                                <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">phone:</th>
                                <td style="text-align:start;">'. $phone_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">email:</th>
                                <td style="text-align:start;">'. $email_order .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">order type:</th>
                                <td style="text-align:start;">mail</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">items:</th>
                                <td style="text-align:start;">'. $item_order[0]->item_name .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">sender address:</th>
                                <td style="text-align:start;">'. $address_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">recipient fullname:</th>
                                <td style="text-align:start;">'. $rec_firstname . ' ' . $rec_parentname .' '.$rec_lastname.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">recipient phone:</th>
                                <td style="text-align:start;">'. $rec_phone .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">recipient address:</th>
                                <td style="text-align:start;">'. $rec_address .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">city:</th>
                                <td style="text-align:start;">'. $city .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">working duration:</th>
                                <td style="text-align:start;">'. $total_duration .'</td>
                            </tr>
                        </table>';

                }
                else if($request['data']['lang'] == "de"){
                    $data_order['order_id'] = 'Bestellnummer:'.$order_no;
                    $data_order['msg'] = '<meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no"/>';

                    $data_order['msg'] .= 'Bitte überweisen Sie einen Betrag';
                    $data_order['msg'] .= ' '.$total_price.' ';
                    $data_order['msg'] .= 'Für das Konto des Unternehmens und senden Sie die Post, die nach Syrien gesendet werden soll, per Deutsche interner Post an die Firmenadresses';
                    $data_order['msg'] .= '<br>'. 'Postlink-Sy: '. $company_address;
                    // $data_order['msg'] .= '<br>Den Status Ihrer Bestellung können Sie über den folgenden Link verfolgen: '.$order_statuses_link;
                    $data_order['msg'] .= '<br><br>'.'Infobank';
                    $data_order['msg'] .= '<br>- '. $company_name;
                    $data_order['msg'] .= '<br>- '. $company_account;
                    $data_order['msg'] .= '<br>- '. $company_BIC;
                    $data_order['msg'] .= '<br>- '. $verwendungszweck . $order_no;
                    
                    $data_order['msg'] .= '<small><br><br>- alle Preise die Mehrwertsteuer enthalten MwSt';
                    $data_order['msg'] .= '<br>- wenn der Betrag nicht unter der Bestellnummer oder dem Namen des Antragstellers auf das Konto des Unternehmens überwiesen wird, wird die Transaktion nicht ausgeführt.';
                    $data_order['msg'] .= '<br>- der Portozeitraum berechnet sich ab dem Tag des Eintreffens der Überweisung und dem Eintreffen der Post in unserem Unternehmen.';
                    $data_order['msg'] .= '<br>- der Empfang erfolgt in Syrien über die Al-Qadmous Company.';
                    $data_order['msg'] .= '<br>- Wenn Sie die Bestellung später stornieren, beachten Sie bitte, dass 10 % der Überweisungsgebühren abgezogen werden. Die Rückerstattungsfrist kann 3 Wochen überschreiten.';
                    $data_order['msg'] .= '<br>- Wenn die Bestellung einen Fehler enthält, können Sie die Bestellung einfach ignorieren, nicht bezahlen und eine neue Bestellung erstellen. Die Bewerbung wird automatisch gelöscht, wenn die Zahlung nicht 10 Tage nach Einreichung erfolgt.</small>';

                    $body2 .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">Bestellnummer:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">vollständiger Name:</th>
                                <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Telefon:</th>
                                <td style="text-align:start;">'. $phone_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">email:</th>
                                <td style="text-align:start;">'. $email_order .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">Auftragsart:</th>
                                <td style="text-align:start;">post</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Artikel:</th>
                                <td style="text-align:start;">'. $item_order[0]->item_name .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Absenderadresse:</th>
                                <td style="text-align:start;">'. $address_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Empfänger vollständiger Name:</th>
                                <td style="text-align:start;">'. $rec_firstname . ' ' . $rec_parentname .' '.$rec_lastname.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Empfänger telefon:</th>
                                <td style="text-align:start;">'. $rec_phone .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">Empfängeradresse:</th>
                                <td style="text-align:start;">'. $rec_address .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Stadt:</th>
                                <td style="text-align:start;">'. $city .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">arbeitsdauer:</th>
                                <td style="text-align:start;">'. $total_duration .'</td>
                            </tr>
                        </table>';
                }

                if(get_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION) == 'ar'){
            
                    $body .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">رقم الطلب:</th>
                                <td style="text-align:start;">'.$order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">الاسم:</th>
                                <td style="text-align:start;">'. $firstname_order .' '.$lastname_order.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">الهاتف:</th>
                                <td style="text-align:start;">'. $phone_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">البريد الالكتروني:</th>
                                <td style="text-align:start;">'. $email_order .'</td>
                            </tr>
                        
                            <tr>
                                <th style="text-align:start;width:130px;">نوع الطلب:</th>
                                <td style="text-align:start;">بريد</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">العناصر المطلوبة:</th>
                                <td style="text-align:start;">'. $item_order[0]->item_name .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">عنوان المرسل:</th>
                                <td style="text-align:start;">'. $address_order .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">اسم المستلم:</th>
                                <td style="text-align:start;">'. $rec_firstname . ' ' . $rec_parentname .' '.$rec_lastname.'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">هاتف المستلم:</th>
                                <td style="text-align:start;">'. $rec_phone .'</td>
                            </tr>
                            
                            <tr>
                                <th style="text-align:start;width:130px;">عنوان المستلم:</th>
                                <td style="text-align:start;">'. $rec_address .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;"> المحافظة:</th>
                                <td style="text-align:start;">'. $city .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">التكلفة الكلية:</th>
                                <td style="text-align:start;">'. $total_price .'</td>
                            </tr>
                        </table>         
                    </body> 
                    </html>';
        
                }
            }
            else{
                $response = new WP_REST_Response(array("message"=>"error"));
                $response->set_status(200);

                return $response;
            }
            
            wp_mail(OS24_MAIL_SY_ADMIN_EMAIL_MAIL, 'New Order: No. '.$order_no, $body); //this function to send email but not work without plugin WP Mail SMTP

        }
        
        if(!empty($email_order))
        {   
            if($request['data']['lang'] == "ar"){
                $body2 .= '
                    <h3 style="direction: rtl;">تم ارسال طلبك بنجاح</h3>
                    <p style="direction: rtl;">'.$data_order['msg'].'</p>
                    </body> 
                </html>';
            }
            else if($request['data']['lang'] == "en")
            {
                $body2 .= '
                    <h3>Your order sent successfully</h3>
                    <p>'.$data_order['msg'].'</p>
                    </body> 
                </html>';
            }
            else if($request['data']['lang'] == "de")
            {
                $body2 .= '
                    <h3>Ihre Bestellung wurde erfolgreich versendet</h3>
                    <p>'.$data_order['msg'].'</p>
                    </body> 
                </html>';
            }
            wp_mail($email_order, 'Postlink-SY: Order Confirmation', $body2); //this function to send email but not work without plugin WP Mail SMTP


        }
        

        if($request['data']['lang'] == "ar"){
            $success = 'تم ارسال طلبك بنجاح';
        }
        else if($request['data']['lang'] == "en"){
            $success = 'Your order sent successfully';
        }
        else if($request['data']['lang'] == "de"){
            $success = 'Ihre Bestellung wurde erfolgreich versendet';
        }
        
        $response = new WP_REST_Response(array("message"=>"Your order sent successfully", "order"=>$data_order, "success"=>$success));
        $response->set_status(200);
    
        return $response;
    }


}
function os24_mail_sy_get_orders(){
    global $wpdb; 
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';

    $all_orders = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_order . " ORDER BY id DESC"
        )
    ); 
    $all_orders_array = array();
    foreach ($all_orders as $order) {
        $items = [];
        $categories = [];
        if($order->lang == "ar"){
            $items = $wpdb->get_results(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_item . " where id IN(" . implode(',', json_decode($order->items)->items_order) . ")"
                )
            ); 
            $categories = $wpdb->get_results(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_category . " where id IN(" . implode(',', json_decode($order->items)->category_order) . ")"
                )
            ); 
        }
        else{
            $items = $wpdb->get_results(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_item_translate . " where id IN(" . implode(',', json_decode($order->items)->items_order) . ")"
                )
            ); 
            $categories = $wpdb->get_results(
                os24_mail_sy_q(
                    "SELECT * from ". $table_name_category_translate . " where id IN(" . implode(',', json_decode($order->items)->category_order) . ")"
                )
            ); 
        }
        $items_array = array();
        $categories_array = array();
        foreach ($items as $i) {
            array_push($items_array, $i->item_name);
        }
        foreach ($categories as $i) {
            array_push($categories_array, $i->category_name);
        }
        $order_json = array(
            "id"=> $order->id,
            "no"=> $order->no,
            "type"=> $order->type,
            "email"=> $order->email,
            "fullname"=> $order->firstname . " " . $order->lastname,
            "date"=> $order->date,
            "items"=> implode(", ", $items_array),
            "categories"=> implode(", ", $categories_array),
            "translate_lang"=> json_decode($order->items)->translate_language,
        );
        array_push($all_orders_array, $order_json);
    }

    $response = new WP_REST_Response(array("allOrders"=>$all_orders_array));
    $response->set_status(200);

    return $response;
}
function os24_mail_sy_get_order($request){
    global $wpdb; 
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';
    $table_name_item = $wpdb->prefix . '24os_mail_sy_item';
    $table_name_item_translate = $wpdb->prefix . '24os_mail_sy_item_translate';
    $table_name_category = $wpdb->prefix . '24os_mail_sy_category';
    $table_name_category_translate = $wpdb->prefix . '24os_mail_sy_category_translate';

    $order = $wpdb->get_row(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_order . " where id = " . $request['id']
        )
    ); 
    if(!$order){
        $response = new WP_REST_Response(array("error"=>true));
        $response->set_status(200);
    
        return $response;
    }
    $items = [];
    $categories = [];
    if($order->lang == "ar"){
        $items = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_item . " where id IN(" . implode(',', json_decode($order->items)->items_order) . ")"
            )
        ); 
        $categories = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_category . " where id IN(" . implode(',', json_decode($order->items)->category_order) . ")"
            )
        ); 
    }
    else{
        $items = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_item_translate . " where id IN(" . implode(',', json_decode($order->items)->items_order) . ")"
            )
        ); 
        $categories = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_category_translate . " where id IN(" . implode(',', json_decode($order->items)->category_order) . ")"
            )
        ); 
    }
    $items_array = array();
    $categories_array = array();
    foreach ($items as $i) {
        array_push($items_array, array(
            "item_name"=>$i->item_name,
            "item_desc"=>$i->item_desc,
            "item_price"=>$i->item_price,
            "item_price_total"=>$i->item_price_total,
            "item_price_desc"=>$i->item_price_desc,
            "item_duration"=>$i->item_duration,
        ));
    }
    foreach ($categories as $i) {
        array_push($categories_array, array(
            "category_name"=>$i->category_name,
            "category_price"=>$i->category_price,
            "category_price_total"=>$i->category_price_total,
            "category_price_desc"=>$i->category_price_desc,
            "category_duration"=>$i->category_duration,
        ));
    }
    $order_json = array(
        "id"=> $order->id,
        "no"=> $order->no,
        "email"=> $order->email,
        "fullname"=> $order->firstname . " " . $order->lastname,
        "rec_firstname"=> $order->rec_firstname,
        "rec_lastname"=> $order->rec_lastname,
        "address_from"=> $order->address_from,
        "address_to"=> $order->address_to,
        "city"=> $order->city,
        "phone"=> $order->phone,
        "rec_phone"=> $order->rec_phone,
        "id_number"=> $order->id_number,
        "img"=> json_decode($order->img),
        "items"=>  $items_array,
        "categories"=> $categories_array,
        "translate_lang"=> json_decode($order->items)->translate_language,
        "sheet_number"=> $order->sheet_number,
        "notes"=> $order->notes,
        "cost"=> $order->cost,
        "cost_extra"=> $order->cost_extra,
        "total_duration"=> $order->total_duration,
        "lang"=> $order->lang,
        "type"=> $order->type,
        "date"=> $order->date
    );
    

    $response = new WP_REST_Response(array("order"=>$order_json));
    $response->set_status(200);

    return $response;
}

function os24_mail_sy_get_order_status($request){
    global $wpdb; 
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';
    $table_name_order_status = $wpdb->prefix . '24os_mail_sy_order_status';

    $order_id = intval($request['order_id']);

    $order_statuses = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_order_status . " where order_id = ".$order_id
        )
    ); 

    $order_statuses_array = array();
    foreach ($order_statuses as $order_status) {
        $status = $wpdb->get_row(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_status . " where id = " . $order_status->status_id
            )
        ); 
        
        $order_status_json = array(
            "id"=> $order_status->id,
            "status"=> $status,
            "owner"=> $order_status->owner,
            "notes"=> $order_status->notes,
            "img"=> $order_status->img,
            "date"=> $order_status->date
        );
        array_push($order_statuses_array, $order_status_json);
    }

    $response = new WP_REST_Response(array("order_statuses"=>$order_statuses_array));
    $response->set_status(200);

    return $response;
}

function os24_mail_sy_track_order_status_by_code($request){
    global $wpdb; 
    // max. 30 tracking attempts per IP per 10 minutes (order codes are sequential)
    if(os24_mail_sy_rate_limited('track', 30, 10 * MINUTE_IN_SECONDS)){
        $response = new WP_REST_Response(array("error"=>"too many requests, please try again later"));
        $response->set_status(429);
        return $response;
    }
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';
    $table_name_order_status = $wpdb->prefix . '24os_mail_sy_order_status';
    $table_name_order = $wpdb->prefix . '24os_mail_sy_order';


    $order_code = $request['order_code'];
    $email = trim($request->get_param("email"));
    $isEmail = $request->get_param("controlIsEmail");
    if($isEmail == "true"){
        if(!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,10}$/ix", $email))
        {
            $response = new WP_REST_Response(array("error"=>"your email is invalid"));
            $response->set_status(200);
    
            return $response;
        }
    }
    else{
        if( !preg_match("/^([a-zA-ZäöüßÄÖÜẞ\p{Arabic}' ]+)$/iu",$email) )
        {
            $response = new WP_REST_Response(array("error"=>"your firstname is invalid"));
            $response->set_status(200);

            return $response;
        }
    }

    $order = $wpdb->get_row(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_order . " where no = '".$order_code. "'"
        )
    ); 
    if(!$order){
        $response = new WP_REST_Response(array("error"=>"order code is invalid"));
        $response->set_status(200);
    
        return $response;
    }
    $order_email = $order->email;
    $order_firstname = $order->firstname;
    if($isEmail == "true"){
        if($order_email == ""){
            $response = new WP_REST_Response(array("error"=>"order email is empty, please enter your firstname"));
            $response->set_status(200);
        
            return $response;
        }
        if($order_email != "" && $order_email != $email){
            $response = new WP_REST_Response(array("error"=>"the email does not match the email you entered in your order"));
            $response->set_status(200);
        
            return $response;
        }
    }
    else{
        if($order_firstname != $email)
        {
            $response = new WP_REST_Response(array("error"=>"the firstname does not match the firstname you entered in your order"));
            $response->set_status(200);
        
            return $response;
        }
    }
    
    $order_id = $order->id;
    $order_statuses = $wpdb->get_results(
        os24_mail_sy_q(
            "SELECT * from ". $table_name_order_status . " where order_id = ".$order_id
        )
    ); 

    $order_statuses_array = array();
    foreach ($order_statuses as $order_status) {
        $status = $wpdb->get_row(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_status . " where id = " . $order_status->status_id
            )
        ); 
        
        $order_status_json = array(
            "id"=> $order_status->id,
            "status"=> $status,
            "owner"=> $order_status->owner,
            "notes"=> $order_status->notes,
            "img"=> $order_status->img,
            "date"=> $order_status->date
        );
        array_push($order_statuses_array, $order_status_json);
    }

    $response = new WP_REST_Response(array("statuses"=>$order_statuses_array));
    $response->set_status(200);

    return $response;
}

function os24_mail_sy_get_statuses(){
    global $wpdb; 
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';
    $table_name_status_translate = $wpdb->prefix . '24os_mail_sy_status_translate';

    $admin_lang = os24_mail_sy_valid_lang(get_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION), 'ar');

    $statuses_array = array();
    if($admin_lang == "ar"){
        $statuses = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_status
            )
        ); 
        $statuses_array = $statuses;
    }
    else{
        $statuses = $wpdb->get_results(
            os24_mail_sy_q(
                "SELECT * from ". $table_name_status_translate . " where lang = '" . $admin_lang . "'"
            )
        ); 
        foreach ($statuses as $status) {
            array_push($statuses_array, array(
                "id"=>$status->status_id,
                "status"=>$status->status,
                "type"=>$status->type,
                "lang"=>$status->lang
            ));
        }
    }

    $response = new WP_REST_Response(array("statuses"=>$statuses_array));
    $response->set_status(200);

    return $response;
}

function os24_mail_sy_add_order_status($request){
    global $wpdb; 
    $table_name_order_status = $wpdb->prefix . '24os_mail_sy_order_status';
    $table_name_status_translate = $wpdb->prefix . '24os_mail_sy_status_translate';
    $table_name_status = $wpdb->prefix . '24os_mail_sy_status';

    // the route requires a logged-in staff user (permission_callback)
    $current_user = wp_get_current_user()->user_login;

    $order_id = intval($request['order_id']);
    $order_email = $request['order_email'];
    $order_lang = os24_mail_sy_valid_lang($request['order_lang']);
    $order_no = $request['order_no'];

    $status_id = intval($request['status_form']['status']);
    $notes = sanitize_textarea_field($request['status_form']['notes']);
    $owner = $current_user;

    $image_src = $request['status_form']['image_src'];
    $type_image = check_image( $image_src );
    $send_to_customer = $request['status_form']['send'];

    if($type_image != "error"){

        $sql = $wpdb->insert( $table_name_order_status, array(
            "order_id" => $order_id,
            "status_id" => $status_id, 
            "owner" => $owner, 
            "notes" => $notes
        ));
        $order_status_id = $wpdb->insert_id;
        $image = save_image( $image_src, $type_image, $order_status_id, "", $order_id, "status");
        $wpdb->update( $table_name_order_status, array(
            'img' => $image),
            array( 'id' => $order_status_id )
        );

        if($send_to_customer && !empty($order_email)){

            $body = '';
            if($order_lang == "ar")
            {
                $status = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_status . " where id = " . $status_id
                    )
                ); 
                $body .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">رقم الطلب:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">حالة الطلب:</th>
                                <td style="text-align:start;"><b>'. $status->status .'</b>';
                                if($notes != ""){
                                    $body .= '<br><p style="margin:0;">-'.$notes.'</p>';
                                }
                                $body .= '</td>
                            </tr>';
                            if($image != ""){
                                $body .= '<tr>
                                    <th style="text-align:start;width:130px;">صورة:</th>
                                    <td>
                                        <a href="'.$image.'">
                                            <img src="'.$image.'" style="width:250px;height:160px;">
                                        </a>
                                    </td>
                                </tr>';
                            }
                            
                        $body .= '
                        </table>
                    </body>
                </html>';
                
            }
            else if($order_lang == "en")
            {
                $status = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_status_translate . " where status_id = " . intval($status_id) . " and lang = '" . esc_sql($order_lang) . "'"
                    )
                ); 
                $body .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">order number:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">order status:</th>
                                <td style="text-align:start;"><b>'. $status->status .'</b>';
                                if($notes != ""){
                                    $body .= '<br><p style="margin:0;">-'.$notes.'</p>';
                                }
                                $body .= '</td>
                            </tr>';
                            if($image != ""){
                                $body .= '<tr>
                                <th style="text-align:start;width:130px;">photo:</th>
                                <td>
                                    <a href="'.$image.'">
                                        <img src="'.$image.'" style="width:250px;height:160px;">
                                    </a>
                                </td>
                            </tr>';
                            }
                            
                        $body .= '
                        </table>
                    </body>
                </html>';
            }
            else if($order_lang == "de")
            {
                $status = $wpdb->get_row(
                    os24_mail_sy_q(
                        "SELECT * from ". $table_name_status_translate . " where status_id = " . intval($status_id) . " and lang = '" . esc_sql($order_lang) . "'"
                    )
                ); 
                $body .= '<html> 
                    <body>
                        <table style="width:100%">
                            <tr>
                                <th style="text-align:start;width:130px;">Bestellnummer:</th>
                                <td style="text-align:start;">'. $order_no .'</td>
                            </tr>
                            <tr>
                                <th style="text-align:start;width:130px;">Bestellstatus:</th>
                                <td style="text-align:start;"><b>'. $status->status .'</b>';
                                if($notes != ""){
                                    $body .= '<br><p style="margin:0;">-'.$notes.'</p>';
                                }
                                $body .= '</td>
                            </tr>';
                            if($image != ""){
                                $body .= '<tr>
                                    <th style="text-align:start;width:130px;">Foto:</th>
                                    <td>
                                        <a href="'.$image.'">
                                            <img src="'.$image.'" style="width:250px;height:160px;">
                                        </a>
                                    </td>
                                </tr>';
                            }
                            
                        $body .= '</table>
                    </body>
                </html>';
            }
            wp_mail($order_email, 'Postlink-SY: Order Status No. '.$order_no, $body);

            $response = new WP_REST_Response(array("st"=>true, "message"=>"The Status has been added successfully and sent to the customer via his Email"));
            $response->set_status(200);
        
            return $response;
        }
        else{

            $response = new WP_REST_Response(array("st"=>true, "message"=>"The Status has been added successfully"));
            $response->set_status(200);
        
            return $response;
        }
    }
    else{
        $response = new WP_REST_Response(array("message"=>"image must to be (.jpeg, .png, .jpg)"));
        $response->set_status(200);

        return $response;
    }
}

/**
 *   functions for set language routes
 **/
function os24_mail_sy_get_language($request){
        
    $language = get_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION);
    $response = new WP_REST_Response($language);
    $response->set_status(200);

    return $response;
        
}
function os24_mail_sy_set_language($request){

    $language = os24_mail_sy_valid_lang($request['lang'], '');
    if($language === ''){
        return os24_mail_sy_error_response("invalid language", 400);
    }

    update_option(OS24_MAIL_SY_CUSTOM_LANGUAGE_OPTION, $language);

    $response = new WP_REST_Response(array("lang"=>$language, "message"=>"language switched successfully"));
    $response->set_status(200);

    return $response;
        
}

function os24_mail_sy_calculate_max_duration_days($array_duration_days){
    $max_day = 0;
    $total_duration = "";
    $working = preg_replace("/\-/u", "", preg_replace("/\d/u", "", $array_duration_days[0]));
    foreach ($array_duration_days as $day) {
        $res = trim(str_replace( ["day", "Tag", "يوم"], '', $day));
        if(strpos($res, "-")){
            if(explode("-", $res)[1] > $max_day){
                $max_day = explode("-", $res)[1];
                $total_duration = explode("-", $res)[0]."-".explode("-", $res)[1];
            }
        }
        else{
            if($res > $max_day)
            {
                $max_day = $res;
                $total_duration = $res;

            }
        }
    }

    return $total_duration. " " . $working;
}

function os24_mail_sy_calculate_max_duration_weeks($array_duration_weeks){
    $max_week = 0;
    $total_duration = "";
    $working = preg_replace("/\-/u", "", preg_replace("/\d/u", "", $array_duration_weeks[0]));
    foreach ($array_duration_weeks as $week) {
        $res = trim(str_replace( ["week", "woche", "اسبوع"], '', $week));
        if(strpos($res, "-")){
            if(explode("-", $res)[1] > $max_week){
                $max_week = explode("-", $res)[1];
                $total_duration = explode("-", $res)[0]."-".explode("-", $res)[1];
            }
        }
        else{
            if($res > $max_week)
            {
                $max_week = $res;
                $total_duration = $res;

            }
        }
    }

    return $total_duration . " " . $working;
}

function os24_mail_sy_set_content_type(){
    return "text/html";
}
add_filter( 'wp_mail_content_type','os24_mail_sy_set_content_type' );
