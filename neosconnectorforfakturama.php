<?php
/*
Plugin Name: Neos Connector for Fakturama
Plugin URI: https://www.neosuniverse.de
Description: Neos Connector for Fakturama importiert Produkte und Bestellungen von Woocommerce zu Fakturama
Version: 0.0.14
Author: Kevin Bonner
Author URI: https://www.neosuniverse.de
Min WP Version: 4.0
Max WP Version: 4.8
Text Domain: neosconnectorforfakturama
*/




if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


#error_reporting(E_ALL);
#ini_set('display_errors', 1);




class NeosFaktura_FakturamaC {

    private $xml;


    protected static $instance = NULL;


    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @since   2017.07.23
     * @return  object of this class
     */
    public static function get_instance()
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }
    /**
     * Constructor. Intentionally left empty and public.
     *
     * */
    public function __construct(){}

    private function ncff_includes(){

        include_once('neosconnectorforfakturama-XMLHelper.php');
        include_once ('neosconnectorforfakturama-XML.php');

        if(is_admin())
        {
            include_once('neosconnectorforfakturama-admin.php');
            include_once ('neosconnectorforfakturama-notices.php');
        }




    }

    public function ncff_missing_woocommerce_error_notice()
    {
        $class = 'notice notice-error';
        $message = __( 'Woocommerce Shop Plugin is missing! Please install first Woocommerce!', NCFF_TEXTDOMAIN );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

    public function ncff_check_woocommerce_installation(){
        if(is_admin()){
            if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                add_action( 'admin_notices', array( $this, 'ncff_missing_woocommerce_error_notice' ) );

            }
        }
    }

    public function ncff_activation(){
        update_option('neosconnectorforfakturama-hide-review-notice', false);
    }

    private function ncff_defines(){
        defined('NCFF_TEXTDOMAIN') or define('NCFF_TEXTDOMAIN', 'neosconnectorforfakturama');
        defined('NCFF_PLUGIN_PATH') or define('NCFF_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
        defined('NCFF_PLUGIN_URL') or define('NCFF_PLUGIN_URL', plugins_url( '/', __FILE__ ));
        defined('NCFF_PLUGINBASENAME') or define('NCFF_PLUGINBASENAME', plugin_basename( __FILE__ ));
        defined('NCFF_PLUGINDATA') or define('NCFF_PLUGINDATA', array('Version' => '0.0.13', 'Name' => 'Neos Connector for Fakturama', 'PluginURI'=> 'https://www.neosuniverse.de/'));
        defined('NCFF_PLUGIN_PAGE_URL') or define('NCFF_PLUGIN_PAGE_URL', get_bloginfo('url')."/wp-admin/options-general.php?page=".NCFF_TEXTDOMAIN);
        defined('NCFF_DEBUG') or define('NCFF_DEBUG', get_option(NCFF_TEXTDOMAIN.'_debug') === 'true'? true: false);
    }

    private function ncff_init(){
        $this->ncff_defines();
        $this->ncff_includes();

        load_plugin_textdomain( NCFF_TEXTDOMAIN, FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );

    }

    public function ncff_admin_init(){

        $this->ncff_init();


        /**
         * Load Class
         */
        if (class_exists('NeosFaktura_Notices')) {
            new NeosFaktura_Notices();
        }

        add_action('admin_init', array($this, 'ncff_check_woocommerce_installation'));




    }

    public function ncff_ajax_init(){

        $this->ncff_init();


        /**
         * Load Class
         */

        if (class_exists('NeosFaktura_fakturamaXML')) {
            $this->xml = new NeosFaktura_fakturamaXML();
        }

        add_action( 'init', array($this, 'ncff_core_add_ajax_hook'));

        add_action('wp_ajax_fakturama', array($this, 'ncff_request'));

    }

    public function ncff_core_add_ajax_hook() {
        if ( isset( $_REQUEST['do'] ) ) {
          if (strpos($_REQUEST['do'], 'fakturama') !== false) {
            do_action( 'wp_ajax_' . sanitize_text_field($_REQUEST['do']) );
          }
        }
    }



    public function ncff_request(){


        $params = "";
        if(isset($_POST['action']))
        {
            $params = $this->ncff_ValidatingSantizingParams($_POST);
        }
        else{
            $params = $this->ncff_ValidatingSantizingParams($_GET);
        }

        if(!isset($params['username']) && !isset($params['password']))
        {
            wp_die("Please Login!");
        }

        $creds = array();
        $creds['user_login'] = $params['username'];
        $creds['user_password'] = $params['password'];
        $creds['remember'] = false;
        $user = wp_signon( $creds, false );
        if ( is_wp_error($user) ){
            die($user->get_error_message());
        }



        if(NCFF_DEBUG == False) {


            header("Content-type: text/xml");

        }



        if(isset($params['action'])){
            switch ($params['action']) {
                case "get_products":

                    echo $this->xml->ncff_get_products();

                    break;
                case "get_orders":

                    echo $this->xml->ncff_get_orders();

                    break;
                case "get_products_orders":

                    echo $this->xml->ncff_get_products_orders();

                    break;

            }
        }
        else{
            wp_die("Missing action!");
        }




        if(isset($params['setstate'])){
            $this->xml->ncff_setstate($params['setstate']);
        }


        wp_die();
        //}

    }
  
  private function ncff_ValidatingSantizingParams($params){
    $paramsreturn = array();
    if(isset($params['username']) && validate_username($params['username']))
    {
      $paramsreturn['username'] = sanitize_user($params['username']);
    }
    if(isset($params['password']))
    {
      $paramsreturn['password'] = sanitize_text_field($params['password']);
    }
    if(isset($params['action']) && !preg_match('/[^a-z_]+/', $params['action']))
    {
      $paramsreturn['action'] = sanitize_text_field($params['action']); //input like this action=get_products_orders or action=get_orders or action=get_products
    }
    if(isset($params['setstate']))
    {
      $paramsreturn['setstate'] = sanitize_text_field($params['setstate']); //input like this setstate={0=3*Hallo%0ATest}
    }
    
    return $paramsreturn;
  }

   

}

add_action( 'init', array( NeosFaktura_FakturamaC::get_instance(), 'ncff_admin_init' ));
add_action( 'plugins_loaded', array( NeosFaktura_FakturamaC::get_instance(), 'ncff_ajax_init' ));
register_activation_hook( __FILE__, array( NeosFaktura_FakturamaC::get_instance(), 'ncff_activation' ));