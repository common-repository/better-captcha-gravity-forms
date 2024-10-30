<?php

/*
Plugin Name: GravityCaptcha Premium
Author: Frog Eat Fly
Author URI: https://gformsdemo.com/
Version: 0.5.2
Description: GravityCaptcha is an easy, encrypted alternative to the frustrating Google Captcha. It adds a random math problem or word question to the end of your form. When a user enters the answer, it is compared to the answer on our external database. A wrong answer will prevent them from preventing the form. If a captcha question is compromised by spam bots and an entry gets through, site owners can mark the entry as spam and that question will be removed or changed in our secure database.
Tested up to: 5.5.3
*/
define( "ZZD_GC_DIR", plugin_dir_path( __FILE__ ) );
define( "ZZD_GC_URL", plugin_dir_url( __FILE__ ) );
define( "ZZD_GC_VER", "0.5.2" );
define( "ZZD_GC_INNER_SLUG", "gravitycaptcha" );
define( "ZZD_GC_CAP", "administrator" );
define( "ZZD_GC_ADMIN_PAGE", "gcaptcha_spams" );
define( "ZZD_GC_BOOTSTRAP", "gcboot" );
add_action( "gform_loaded", array( "GravityCaptcha_Loader", "load" ), 25 );
class GravityCaptcha_Loader
{
    public static function load()
    {
        if ( !method_exists( "GFForms", "include_addon_framework" ) ) {
            return;
        }
        
        if ( file_exists( ZZD_GC_DIR . "class-gravitycaptcha-addon.php" ) ) {
            require_once ZZD_GC_DIR . "class-gravitycaptcha-addon.php";
            GFAddOn::register( "GravityCaptchaAddon" );
        }
    
    }

}
add_action( 'plugins_loaded', 'load_gcaptcha_languages', 0 );
function load_gcaptcha_languages()
{
    load_plugin_textdomain( 'gravitycaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


if ( !function_exists( 'bcgf_fs' ) ) {
    // Create a helper function for easy SDK access.
    function bcgf_fs()
    {
        global  $bcgf_fs ;
        
        if ( !isset( $bcgf_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $bcgf_fs = fs_dynamic_init( array(
                'id'             => '7365',
                'slug'           => 'better-captcha-gravity-forms',
                'type'           => 'plugin',
                'public_key'     => 'pk_9292cf54bf5d746e1c219f163816d',
                'is_premium'     => false,
                'premium_suffix' => 'Premium',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'       => 'gcaptcha_spams',
                'first-path' => 'admin.php?page=gcaptcha_spams',
                'contact'    => false,
                'support'    => false,
                'parent'     => array(
                'slug' => 'gf_edit_forms',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $bcgf_fs;
    }
    
    // Init Freemius.
    bcgf_fs();
    // Signal that SDK was initiated.
    do_action( 'bcgf_fs_loaded' );
}
