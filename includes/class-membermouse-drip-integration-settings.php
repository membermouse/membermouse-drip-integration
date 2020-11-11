<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */
if (! defined('ABSPATH')) {
    exit();
}

/**
 * Settings class.
 */
class MemberMouse_Drip_Integration_Settings
{

    /**
     * The single instance of MemberMouse_Drip_Integration_Settings.
     *
     * @var object
     * @access private
     * @since 1.0.0
     */
    private static $_instance = null;

    // phpcs:ignore

    /**
     * The main plugin object.
     *
     * @var object
     * @access public
     * @since 1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     *
     * @var array
     * @access public
     * @since 1.0.0
     */
    public $settings = array();

    /**
     * Constructor function.
     *
     * @param object $parent
     *            Parent object.
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'wpt_';

        // Add settings page to menu.
        add_action('admin_menu', array(
            $this,
            'add_menu_item'
        ));

        add_action('admin_init', array(
            $this,
            'register_settings'
        ));

        // Add settings link to plugins page.
        add_filter('plugin_action_links_' . plugin_basename($this->parent->file), array(
            $this,
            'add_settings_link'
        ));
    }

    /**
     * Add settings page to admin menu
     *
     * @return void
     */
    public function add_menu_item()
    {
        $args = $this->menu_settings();

        // Do nothing if wrong location key is set.
        if (is_array($args) && isset($args['location']) && function_exists('add_' . $args['location'] . '_page')) {
            switch ($args['location']) {
                case 'options':
                case 'submenu':
                    $page = add_submenu_page($args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);
                    break;
                case 'menu':
                    $page = add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position']);
                    break;
                default:
                    return;
            }
        }
    }

    /**
     * Setup listeners for Drip integration
     */
    public function register_settings()
    {
        // Setup push notifications if Drip integration is active
        $dripAccountId = get_option("mm-drip-account-id", false);

        if (function_exists("is_plugin_active") && is_plugin_active("membermouse/index.php") && ! empty($dripAccountId) && is_numeric($dripAccountId)) {
            add_action("mm_member_add", array(
                $this,
                'memberAdded'
            ), 100, 2);
            add_action("mm_member_status_change", array(
                $this,
                'memberStatusChanged'
            ), 100, 2);
            add_action("mm_member_membership_change", array(
                $this,
                'membershipChanged'
            ), 100, 2);
            add_action("mm_member_account_update", array(
                $this,
                'memberAccountUpdated'
            ), 100, 2);
            add_action("mm_member_delete", array(
                $this,
                'memberDeleted'
            ), 100, 2);
            add_action("mm_bundles_add", array(
                $this,
                'bundleAdded'
            ), 100, 2);
            add_action("mm_bundles_status_change", array(
                $this,
                'bundleStatusChanged'
            ), 100, 2);
            add_action("mm_payment_received", array(
                $this,
                'paymentReceived'
            ), 100, 2);
            add_action("mm_payment_rebill", array(
                $this,
                'rebillPaymentReceived'
            ), 100, 2);
            add_action("mm_payment_rebill_declined", array(
                $this,
                'rebillPaymentDeclined'
            ), 100, 2);
            add_action("mm_refund_issued", array(
                $this,
                'refundIssued'
            ), 100, 2);
            add_action("mm_product_purchase", array(
                $this,
                'productPurchased'
            ), 100, 2);
        }
    }

    /**
     * Prepare default settings page arguments
     *
     * @return mixed|void
     */
    private function menu_settings()
    {
        return apply_filters($this->base . 'menu_settings', array(
            'location' => 'menu', // Possible settings: options, menu, submenu.
            'parent_slug' => 'admin.php',
            'page_title' => __('MemberMouse Drip Integration', 'membermouse-drip-integration'),
            'menu_title' => __('MM <> Drip', 'membermouse-drip-integration'),
            'capability' => 'manage_options',
            'menu_slug' => $this->parent->_token . '_settings',
            'function' => array(
                $this,
                'settings_page'
            ),
            'icon_url' => '',
            'position' => null
        ));
    }

    /**
     * Add settings link to plugin list table
     *
     * @param array $links
     *            Existing links.
     * @return array Modified links.
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=' . $this->parent->_token . '_settings">' . __('Configure', 'membermouse-drip-integration') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Load settings page content.
     *
     * @return void
     */
    public function settings_page()
    {
        $dripAccountId = "";

        if (isset($_POST["mm_drip_account_id"])) {
            if (isset($_POST["mm_deactivate_drip"]) && $_POST["mm_deactivate_drip"] == "true") {
                update_option("mm-drip-account-id", "");
            } else {
                if (is_numeric($_POST["mm_drip_account_id"])) {
                    $dripAccountId = intval($_POST["mm_drip_account_id"]);
                    update_option("mm-drip-account-id", $dripAccountId);
                } else {
                    update_option("mm-drip-account-id", "");
                    $error = "Invalid ID. The Drip account ID must be a number.";
                }
            }
        }

        $dripAccountId = get_option("mm-drip-account-id", false);
        $isActive = (! empty($dripAccountId)) ? true : false;
        $submitLabel = ($isActive == true) ? "Update" : "Activate";
        ?>
<!-- override WordPress 3.8 styles -->
<style>
#wpwrap {
	background-color: #fff;
}

.ui-widget {
	font-size: 1em;
}

textarea, input, select {
	font-size: 11px;
}
</style>
<div class="mm-wrap"
	id="<?php echo $this->parent->_token.'_settings' ?>">
	<h2><?php echo __('MemberMouse Drip Integration', 'membermouse-drip-integration'); ?></h2>
    <?php
        if (is_plugin_active("membermouse/index.php")) {
            ?>
	<form method='post'>

		<div style="margin-bottom: 10px;">
			<img
				src="<?php echo plugins_url("../assets/images/", __FILE__)."/drip-logo.png"; ?>"
				style="vertical-align: middle; margin-right: 10px; width: 200px;" />
		</div>
		<div style="margin-top: 10px;">
			<div style="margin-left: 10px; width: 700px;">

				<div style="margin-left: 15px;">
					<p>
						Enter your Drip accout ID below. Your Account ID is located in
						your <a target="_blank" href="https://www.getdrip.com/settings">Drip
							account settings</a>.
					</p>
					<p>
						<input id="mm-drip-account-id" name="mm_drip_account_id"
							type="text" size="15" value="<?php echo $dripAccountId; ?>" /> <input
							id="mm-deactivate-drip" name="mm_deactivate_drip" type="hidden"
							value="" /> <input type='submit'
							value='<?php echo $submitLabel ?>'
							class="mm-ui-button <?php echo ($isActive) ? 'blue' : 'green'; ?>" />
					</p>
				</div>
		
		<?php if($isActive) { ?>
		<div class="updated" style="padding: 10px; border-left-color: #f23df1">
					<h3>
						<i class="fa fa-check" style="color: #690"></i> Drip Integration
						Active
					</h3>

					<strong>What Happens Now?</strong>
					<ol>
						<li>A customer profile will automatically be created in Drip when
							a new member is added to MemberMouse.</li>
						<li>Notifications will be sent to Drip when any of the following
							events occur in MemberMouse:
							<div style="margin-left: 15px; margin-top: 10px;">
								<i class="fa fa-user"></i> member added, member account updated,
								membership level changed, member deleted<br /> <i
									class="fa fa-cube"></i> bundle added, bundle status changed<br />
								<i class="fa fa-shopping-cart"></i> product purchased<br /> <i
									class="fa fa-money"></i> initial or rebill payment received,
								rebill payment failed, refund issued
							</div>
						</li>
						<li><strong>Setup Automation</strong><br />If you want something
							to happen in Drip as result of these events you'll need to create
							a <a
							href="https://www.getdrip.com/<?php echo $dripAccountId; ?>/workflows"
							target="_blank">workflow</a> or a basic <a
							href="https://www.getdrip.com/<?php echo $dripAccountId; ?>/rules"
							target="_blank">rule</a> and choose MemberMouse as the provider.
							Then, select the specific event that you would like to trigger an
							action in Drip. Check out our <a href="https://support.membermouse.com/a/solutions/articles/9000195532" target="_blank">step-by-step guide on creating automations</a>.</li>
					</ol>
				</div>

				<div style="margin-left: 15px; margin-top: 10px;">
					<input type='submit' value='Deactivate' class="mm-ui-button red"
						onclick="jQuery('#mm-deactivate-drip').val('true');" />
				</div>
		<?php } else { ?>
		<div class="updated" style="padding: 10px; border-left-color: #999">
					<h3>
						<span style="color: #888"><i class="fa fa-close"></i> Drip
							Integration Inactive</span>
					</h3>
				</div>
		<?php } ?>
		</div>
	
	</form>

	<script type='text/javascript'>
    <?php if(!empty($error)){ ?>
    alert('<?php echo $error; ?>');
    <?php  } ?>
    </script>
<?php
        } else {
            ?>
	        <div class="error" style="padding: 10px; width: 600px;">The
		MemberMouse plugin must be active in order to use this plugin.</div>
	        <?php
        }

        ?>
	</div>
<?php
    }

    public function memberAdded($params)
    {
        $this->sendDataToDrip("mm_member_add", $params);
    }

    public function memberStatusChanged($params)
    {
        $this->sendDataToDrip("mm_member_status_change", $params);
    }

    public function membershipChanged($params)
    {
        $this->sendDataToDrip("mm_member_membership_change", $params);
    }

    public function memberAccountUpdated($params)
    {
        $this->sendDataToDrip("mm_member_account_update", $params);
    }

    public function memberDeleted($params)
    {
        $this->sendDataToDrip("mm_member_delete", $params);
    }

    public function bundleAdded($params)
    {
        $this->sendDataToDrip("mm_bundles_add", $params);
    }

    public function bundleStatusChanged($params)
    {
        $this->sendDataToDrip("mm_bundles_status_change", $params);
    }

    public function paymentReceived($params)
    {
        $this->sendDataToDrip("mm_payment_received", $params);
    }

    public function rebillPaymentReceived($params)
    {
        $this->sendDataToDrip("mm_payment_rebill", $params);
    }

    public function rebillPaymentDeclined($params)
    {
        $this->sendDataToDrip("mm_payment_rebill_declined", $params);
    }

    public function refundIssued($params)
    {
        $this->sendDataToDrip("mm_refund_issued", $params);
    }

    public function productPurchased($params)
    {
        $this->sendDataToDrip("mm_product_purchase", $params);
    }

    protected function sendDataToDrip($eventType, $params)
    {
        $dripAccountId = get_option("mm-drip-account-id", false);
        $url = "https://api.getdrip.com/" . $dripAccountId . "/push/membermouse";

        if (! empty($url)) {
            $postvars = "event_type={$eventType}&";
            $postvars .= $this->arrayToQuerystring($params);

            if (preg_match("/(\?)/", $url)) {
                $url = $url . "&" . $postvars;
            } else {
                $url = $url . "?" . $postvars;
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $contents = curl_exec($ch);
            curl_close($ch);
        }
    }

    protected function arrayToQuerystring($params)
    {
        $querystring = "";
        foreach ($params as $key => $value) {
            if (! is_array($value)) {
                $querystring .= $key . "=" . urlencode($value) . "&";
            }
        }
        return $querystring;
    }

    /**
     * Main MemberMouse_Drip_Integration_Settings Instance
     *
     * Ensures only one instance of MemberMouse_Drip_Integration_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see MemberMouse_Drip_Integration()
     * @param object $parent
     *            Object instance.
     * @return object MemberMouse_Drip_Integration_Settings instance
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

    // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of MemberMouse_Drip_Integration_API is forbidden.')), esc_attr($this->parent->_version));
    } // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of MemberMouse_Drip_Integration_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	} // End __wakeup()

}
