<?php
/**
 * Created by IntelliJ IDEA.
 * User: kevin
 * Date: 21.07.2017
 * Time: 13:37
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


class NeosFaktura_Notices
{


    /**
     * Constructor.
     */
    public function __construct() {

        if(!get_option('neosconnectorforfakturama-hide-review-notice'))
        {
            add_action( 'admin_notices', array( $this, 'ncff_review_notice' ) );
            add_action('admin_init', array($this, 'ncff_disable_review_notice'));
        }

        add_action( 'upgrader_process_complete', array($this, 'ncff_reset_notice_after_update'),10, 2);

    }



    public function ncff_review_notice(){
        $dismiss_url = add_query_arg( 'notice', 'neosconnectorforfakturama-hide-review-notice', add_query_arg( 'nonce', wp_create_nonce( 'neosconnectorforfakturama-hide-review-notice' ) ) );
        $plugin_data = get_plugin_data(__DIR__.'/neosconnectorforfakturama.php');
        ?>
        <div class="updated fade">
            <h3><?php _e( 'Do you like Neos Connector for Fakturama?', NCFF_TEXTDOMAIN ); ?></h3>
            <p>
                <?php _e( 'If you like Neos Connector for Fakturama and our Plugin does a good job it would be great if you would write a review about Neos Connector for Fakturama on WordPress.org. Thank you for your support!', NCFF_TEXTDOMAIN ); ?>
            </p>
            <p class="alignleft wc-gzd-button-wrapper">
                <a class="button button-primary" href="https://wordpress.org/support/plugin/neos-connector-for-fakturama/reviews/?filter=5#new-post" target="_blank"><?php _e( 'Write review now', NCFF_TEXTDOMAIN );?></a>
                <a class="button button-secondary" href="https://wordpress.org/plugins/neos-connector-for-fakturama" target="_blank"><?php _e( 'Found Bugs?', NCFF_TEXTDOMAIN );?></a>
            </p>
            <p class="alignright">
                <a href="<?php echo esc_url( $dismiss_url );?>"><?php _e( 'Hide this notice', NCFF_TEXTDOMAIN ); ?></a>
            </p>
            <div class="clear"></div>
        </div>
        <?php
    }

    public function ncff_reset_notice_after_update( $upgrader_object, $options ) {


        if ($options['action'] == 'update' && $options['type'] == 'plugin' ){

            foreach($options['plugins'] as $each_plugin){
                if (strcmp($each_plugin, NCFF_PLUGINBASENAME) === 0 ){
                    // .......................... YOUR CODES .............

                    update_option('neosconnectorforfakturama-hide-review-notice', false);
                }
            }
        }
    }

    public function ncff_disable_review_notice(){

        if ( isset( $_GET[ 'notice' ] )  && isset( $_GET['nonce'] ) && check_admin_referer( 'neosconnectorforfakturama-hide-review-notice', 'nonce' ) ) {
            update_option('neosconnectorforfakturama-hide-review-notice', true);
            wp_redirect(NCFF_PLUGIN_PAGE_URL);
        }

    }
}



