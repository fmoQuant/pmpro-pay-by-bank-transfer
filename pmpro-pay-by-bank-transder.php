<?php
/**
 * Plugin Name: Paid Memberships Pro - Pay by Bank Transfer Add On
 * Plugin URI:  https://github.com/fmoQuant/pmpro-pay-per-bank-transfer
 * Description: A collection of customizations useful when allowing users to pay by bank transfer for Paid Memberships Pro levels.
 * Version:     0.1
 * Author:      Farid Moussaoui
 * Author URI:  https://github.com/fmoQuant
 * Text Domain: pmpro-pay-by-bank-transfer
 * Domain Path: /languages
 * License:     GPL2
 */
/*
	Sample use case: You have a paid level that you want to allow people to pay by bank transfer for.

	1. Change your Payment Settings to the "Pay by Bank Transfer" gateway and make sure to set the "Instructions" with instructions for how to pay by bank transfer. Save.
	2. Change the Payment Settings back to use your gateway of choice. Behind the scenes the Pay by Bank Transfer settings are still stored.

	* Users who choose to pay by bank transfer will have their order to "pending" status.
	* Users with a pending order will not have access based on their level.
	* After you recieve the money, you can edit the order to change the status to "success", which will give the user access.
	* An email is sent to the user RE the status change.
*/

/*
	Settings, Globals and Constants
*/
define("PMPRO_PAY_BY_BANK_TRANSFER_DIR", dirname(__FILE__));
define("PMPROPBBT_VER", '.8');

/*
	Load plugin textdomain.
*/
function pmpropbbt_load_textdomain() {
  load_plugin_textdomain( 'pmpro-pay-by-bank-transfer', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'pmpropbbt_load_textdomain' );

/*
	Add settings to the edit levels page
*/
//show the checkbox on the edit level page
function pmpropbbt_pmpro_membership_level_after_other_settings()
{
	$level_id = intval($_REQUEST['edit']);
	$options = pmpropbbt_getOptions($level_id);
?>
<h3 class="topborder"><?php _e('Pay by Bank Transfer Settings', 'pmpro-pay-by-bank-transfer');?></h3>
<p><?php _e('Change this setting to allow or disallow the pay by bank transfer option for this level.', 'pmpro-pay-by-bank-transfer');?></p>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="pbbt_setting"><?php _e('Allow Pay by Bank Transfer:', 'pmpro-pay-by-bank-transfer');?></label></th>
		<td>
			<select id="pbbt_setting" name="pbbt_setting">
				<option value="0" <?php selected($options['setting'], 0);?>><?php _e('No. Use the default gateway only.', 'pmpro-pay-by-bank-transfer');?></option>
				<option value="1" <?php selected($options['setting'], 1);?>><?php _e('Yes. Users choose between default gateway and bank transfer.', 'pmpro-pay-by-bank-transfer');?></option>
				<option value="2" <?php selected($options['setting'], 2);?>><?php _e('Yes. Users can only pay by bank transfer.', 'pmpro-pay-by-bank-transfer');?></option>
			</select>
		</td>
	</tr>
	<tr class="pbbt_recurring_field">
		<th scope="row" valign="top"><label for="pbbt_renewal_days"><?php _e('Send Renewal Emails:', 'pmpro-pay-by-bank-transfer');?></label></th>
		<td>
			<input type="text" id="pbbt_renewal_days" name="pbbt_renewal_days" size="5" value="<?php echo esc_attr($options['renewal_days']);?>" /> <?php _e('days before renewal.', 'pmpro-pay-by-bank-transfer');?>
		</td>
	</tr>
	<tr class="pbbt_recurring_field">
		<th scope="row" valign="top"><label for="pbbt_reminder_days"><?php _e('Send Reminder Emails:', 'pmpro-pay-by-bank-transfer');?></label></th>
		<td>
			<input type="text" id="pbbt_reminder_days" name="pbbt_reminder_days" size="5" value="<?php echo esc_attr($options['reminder_days']);?>" /> <?php _e('days after a missed payment.', 'pmpro-pay-by-bank-transfer');?>
		</td>
	</tr>
	<tr class="pbbt_recurring_field">
		<th scope="row" valign="top"><label for="pbbt_cancel_days"><?php _e('Cancel Membership:', 'pmpro-pay-by-bank-transfer');?></label></th>
		<td>
			<input type="text" id="pbbt_cancel_days" name="pbbt_cancel_days" size="5" value="<?php echo esc_attr($options['cancel_days']);?>" /> <?php _e('days after a missed payment.', 'pmpro-pay-by-bank-transfer');?>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action('pmpro_membership_level_after_other_settings', 'pmpropbbt_pmpro_membership_level_after_other_settings');

//save pay by bank transfer settings when the level is saved/added
function pmpropbbt_pmpro_save_membership_level($level_id)
{
	//get values
	if(isset($_REQUEST['pbbt_setting']))
		$pbbt_setting = intval($_REQUEST['pbbt_setting']);
	else
		$pbbt_setting = 0;

	$renewal_days = intval($_REQUEST['pbbt_renewal_days']);
	$reminder_days = intval($_REQUEST['pbbt_reminder_days']);
	$cancel_days = intval($_REQUEST['pbbt_cancel_days']);

	//build array
	$options = array(
		'setting' => $pbbt_setting,
		'renewal_days' => $renewal_days,
		'reminder_days' => $reminder_days,
		'cancel_days' => $cancel_days,
	);

	//save
	delete_option('pmpro_pay_by_bank_transfer_setting_' . $level_id);
	delete_option('pmpro_pay_by_bank_transfer_options_' . $level_id);
	add_option('pmpro_pay_by_bank_transfer_options_' . intval($level_id), $options, "", "no");
}
add_action("pmpro_save_membership_level", "pmpropbbt_pmpro_save_membership_level");

/*
	Helper function to get options.
*/
function pmpropbbt_getOptions($level_id)
{
	if($level_id > 0)
	{
		//option for level, check the DB
		$options = get_option('pmpro_pay_by_bank_transfer_options_' . $level_id, false);
		if(empty($options))
		{
			//check for old format to convert (_setting_ without an s)
			$options = get_option('pmpro_pay_by_bank_transfer_setting_' . $level_id, false);
			if(!empty($options))
			{
				delete_option('pmpro_pay_by_bank_transfer_setting_' . $level_id);
				$options = array('setting'=>$options, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
				add_option('pmpro_pay_by_bank_transfer_options_' . $level_id, $options, NULL, 'no');
			}
			else
			{
				//default
				$options = array('setting'=>0, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
			}
		}
	}
	else
	{
		//default for new level
		$options = array('setting'=>0, 'renewal_days'=>'', 'reminder_days'=>'', 'cancel_days'=>'');
	}

	return $options;
}

/*
	Add pay by bank transfer as an option
*/
//add option to checkout along with JS
function pmpropbbt_checkout_boxes()
{
	global $gateway, $pmpro_level, $pmpro_review;
	$gateway_setting = pmpro_getOption("gateway");

	$options = pmpropbbt_getOptions($pmpro_level->id);

	//only show if the main gateway is not banktransfer and setting value == 1 (value == 2 means only do bank transfer payments)
	if($gateway_setting != "banktransfer" && $options['setting'] == 1)
	{
	?>
	<table id="pmpro_payment_method" class="pmpro_checkout top1em" width="100%" cellpadding="0" cellspacing="0" border="0" <?php if(!empty($pmpro_review)) { ?>style="display: none;"<?php } ?>>
			<thead>
					<tr>
							<th><?php _e('Choose Your Payment Method', 'pmpro-pay-by-bank-transfer');?></th>
					</tr>
			</thead>
			<tbody>
					<tr>
							<td>
									<div>
											<input type="radio" name="gateway" value="<?php echo $gateway_setting;?>" <?php if(!$gateway || $gateway == $gateway_setting) { ?>checked="checked"<?php } ?> />
													<?php if($gateway_setting == "paypalexpress" || $gateway_setting == "paypalstandard") { ?>
														<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay with PayPal', 'pmpro-pay-by-bank-transfer');?></a> &nbsp;
													<?php } elseif($gateway_setting == 'twocheckout') { ?>
														<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay with 2Checkout', 'pmpro-pay-by-bank-transfer');?></a> &nbsp;
													<?php } else { ?>
														<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay by Credit Card', 'pmpro-pay-by-bank-transfer');?></a> &nbsp;
													<?php } ?>
											<input type="radio" name="gateway" value="banktransfer" <?php if($gateway == "banktransfer") { ?>checked="checked"<?php } ?> />
													<a href="javascript:void(0);" class="pmpro_radio"><?php _e('Pay by Bank Transfer', 'pmpro-pay-by-bank-transfer');?></a> &nbsp;
											<?php
												//support the PayPal Website Payments Pro Gateway which has PayPal Express as a second option natively
												if($gateway_setting == "paypal") {
												?>
												<span class="gateway_paypalexpress">
													<input type="radio" name="gateway" value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>checked="checked"<?php } ?> />
													<a href="javascript:void(0);" class="pmpro_radio">Check Out with PayPal</a>
												</span>
												<?php
												}
											?>
									</div>
							</td>
					</tr>
			</tbody>
	</table>
	<div class="clear"></div>
	<?php
	}
}
add_action("pmpro_checkout_boxes", "pmpropbbt_checkout_boxes", 20);

/**
 * Toggle payment method when discount code is updated
 */
function pmpropbbt_pmpro_applydiscountcode_return_js() {
	?>
	pmpropbbt_togglePaymentMethodBox();
	<?php
}
add_action('pmpro_applydiscountcode_return_js', 'pmpropbbt_pmpro_applydiscountcode_return_js');

/**
 * Enqueue scripts on the frontend.
 */
function pmpropbbt_enqueue_scripts() {

	if(!function_exists('pmpro_getLevelAtCheckout'))
		return;
	
	global $gateway, $pmpro_level, $pmpro_review, $pmpro_pages, $post, $pmpro_msg, $pmpro_msgt;

	// If post not set, bail.
	if( ! isset( $post ) ) {
		return;
	}

	//make sure we're on the checkout page
	if(!is_page($pmpro_pages['checkout']) && !empty($post) && strpos($post->post_content, "[pmpro_checkout") === false)
		return;
	
	wp_register_script('pmpro-pay-by-bank-transfer', plugins_url( 'js/pmpro-pay-by-bank-transfer.js', __FILE__ ), array( 'jquery' ), PMPROPBBT_VER );
	
	//store original msg and msgt values in case these function calls below affect them
	$omsg = $pmpro_msg;
	$omsgt = $pmpro_msgt;

	//get original checkout level and another with discount code applied	
	$pmpro_nocode_level = pmpro_getLevelAtCheckout(false, '^*NOTAREALCODE*^');
	$pmpro_code_level = pmpro_getLevelAtCheckout();			//NOTE: could be same as $pmpro_nocode_level if no code was used
	
	//restore these values
	$pmpro_msg = $omsg;
	$pmpro_msgt = $omsgt;
	
	wp_localize_script('pmpro-pay-by-bank-transfer', 'pmpropbbt', array(
			'gateway' => pmpro_getOption('gateway'),
			'nocode_level' => $pmpro_nocode_level,
			'code_level' => $pmpro_code_level,
			'pmpro_review' => (bool)$pmpro_review,
			'is_admin'  =>  is_admin(),
            'hide_billing_address_fields' => apply_filters('pmpro_hide_billing_address_fields', false ),
		)
	);

	wp_enqueue_script('pmpro-pay-by-bank-transfer');

}
add_action("wp_enqueue_scripts", 'pmpropbbt_enqueue_scripts');

/**
 * Enqueue scripts in the dashboard.
 */
function pmpropbbt_admin_enqueue_scripts() {
	//make sure this is the edit level page
	
	wp_register_script('pmpropbbt-admin', plugins_url( 'js/pmpro-pay-by-bank-transfer-admin.js', __FILE__ ), array( 'jquery' ), PMPROPBBT_VER );
	wp_enqueue_script('pmpropbbt-admin');
}
add_action('admin_enqueue_scripts', 'pmpropbbt_admin_enqueue_scripts' );

//add bank transfer as a valid gateway
function pmpropbbt_pmpro_valid_gateways($gateways)
{
    $gateways[] = "banktransfer";
    return $gateways;
}
add_filter("pmpro_valid_gateways", "pmpropbbt_pmpro_valid_gateways");

/*
	Force banktransfer gateway if pbbt_setting is 2
*/
function pmpropbbt_pmpro_get_gateway($gateway)
{
	global $pmpro_level;

	if(!empty($pmpro_level) || !empty($_REQUEST['level']))
	{
		if(!empty($pmpro_level))
			$level_id = $pmpro_level->id;
		else
			$level_id = intval($_REQUEST['level']);

		$options = pmpropbbt_getOptions($level_id);

    	if($options['setting'] == 2)
    		$gateway = "banktransfer";
	}

	return $gateway;
}
add_filter('pmpro_get_gateway', 'pmpropbbt_pmpro_get_gateway');
add_filter('option_pmpro_gateway', 'pmpropbbt_pmpro_get_gateway');

/*
	Need to remove some filters added by the banktransfer gateway.
	The default gateway will have it's own idea RE this.
*/
function pmpropbbt_init_include_billing_address_fields()
{
	//make sure PMPro is active
	if(!function_exists('pmpro_getGateway'))
		return;

	//billing address and payment info fields
	if(!empty($_REQUEST['level']))
	{
		$level_id = intval($_REQUEST['level']);
		$options = pmpropbbt_getOptions($level_id);
    	if($options['setting'] == 2)
		{
			//hide billing address and payment info fields
			add_filter('pmpro_include_billing_address_fields', '__return_false', 20);
			add_filter('pmpro_include_payment_information_fields', '__return_false', 20);
		} else {
			//keep paypal buttons, billing address fields/etc at checkout
			$default_gateway = pmpro_getOption('gateway');
			if($default_gateway == 'paypalexpress') {
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalexpress', 'pmpro_checkout_default_submit_button'));
				add_action('pmpro_checkout_after_form', array('PMProGateway_paypalexpress', 'pmpro_checkout_after_form'));
			} elseif($default_gateway == 'paypalstandard') {
				add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_paypalstandard', 'pmpro_checkout_default_submit_button'));
			} elseif($default_gateway == 'paypal') {
				add_action('pmpro_checkout_after_form', array('PMProGateway_paypal', 'pmpro_checkout_after_form'));				
				add_filter('pmpro_include_payment_option_for_paypal', '__return_false');
			} elseif($default_gateway == 'twocheckout') {
				//undo the filter to change the checkout button text
				remove_filter('pmpro_checkout_default_submit_button', array('PMProGateway_twocheckout', 'pmpro_checkout_default_submit_button'));			
			} else {
				//onsite checkouts
				if(class_exists('PMProGateway_' . $default_gateway) && method_exists('PMProGateway_' . $default_gateway, 'pmpro_include_billing_address_fields'))
					add_filter('pmpro_include_billing_address_fields', array('PMProGateway_' . $default_gateway, 'pmpro_include_billing_address_fields'));
				else
					add_filter('pmpro_include_billing_address_fields', '__return_true', 20);
			}
		}
	}

	//instructions at checkout
	remove_filter('pmpro_checkout_after_payment_information_fields', array('PMProGateway_check', 'pmpro_checkout_after_payment_information_fields'));
	add_filter('pmpro_checkout_after_payment_information_fields', 'pmpropbbt_pmpro_checkout_after_payment_information_fields');	
	
	//Show a different message for users whose bank transfer are pending
	add_filter( 'pmpro_non_member_text_filter', 'pmpropbbt_banktransfer_pending_lock_text' );
}
add_action('init', 'pmpropbbt_init_include_billing_address_fields', 20);

/*
	Show instructions on the checkout page.
*/
function pmpropbbt_pmpro_checkout_after_payment_information_fields() {
	global $gateway, $pmpro_level;

	$options = pmpropbbt_getOptions($pmpro_level->id);

	if(!empty($options) && $options['setting'] > 0 && !pmpro_isLevelFree($pmpro_level)) {
		$instructions = pmpro_getOption("instructions");
		if($gateway != 'banktransfer')
			$hidden = 'style="display:none;"';
		else
			$hidden = '';
		?>
		<div class="pmpro_bank_transfer_instructions" <?php echo $hidden; ?>><?php echo wp_kses_post( $instructions ); ?></div>
		<?php
	}
}

/*
	Handle pending bank transfer payments
*/
//add pending as a default status when editing orders
function pmpropbbt_pmpro_order_statuses($statuses)
{
	if(!in_array('pending', $statuses))
	{
		$statuses[] = 'pending';
	}

	return $statuses;
}
add_filter('pmpro_order_statuses', 'pmpropbbt_pmpro_order_statuses');

//set check orders to pending until they are paid
function pmpropbbt_pmpro_check_status_after_checkout($status)
{
	return "pending";
}
add_filter("pmpro_check_status_after_checkout", "pmpropbbt_pmpro_check_status_after_checkout");

/*
 * Check if a member's status is still pending, i.e. they haven't made their first bank transfer payment.
 *
 * @return bool If status is pending or not.
 * @param user_id ID of the user to check.
 * @since .5
 */
function pmpropbbt_isMemberPending($user_id, $level_id = 0)
{
	global $pmpropbbt_pending_member_cache;

	//check the cache first
	if(isset($pmpropbbt_pending_member_cache) && 
	   isset($pmpropbbt_pending_member_cache[$user_id]) && 
	   isset($pmpropbbt_pending_member_cache[$user_id][$level_id]))
		return $pmpropbbt_pending_member_cache[$user_id][$level_id];

	//check their last order
	$order = new MemberOrder();
	$order->getLastMemberOrder($user_id, false, $level_id);		//NULL here means any status
	
	//make room for this user's data in the cache
	if(!is_array($pmpropbbt_pending_member_cache)) {
		$pmpropbbt_pending_member_cache = array();
	} elseif(!is_array($pmpropbbt_pending_member_cache[$user_id])) {
		$pmpropbbt_pending_member_cache[$user_id] = array();
	}	
	$pmpropbbt_pending_member_cache[$user_id][$level_id] = false;

	if(!empty($order->status))
	{
		if($order->status == "pending")
		{
			//for recurring levels, we should check if there is an older successful order
			$membership_level = pmpro_getMembershipLevelForUser($user_id);
						
			//unless the previous order has status success and we are still within the grace period
			$paid_order = new MemberOrder();
			$paid_order->getLastMemberOrder($user_id, array('success', 'cancelled'), $order->membership_id);
			
			if(!empty($paid_order) && !empty($paid_order->id))
			{
				//how long ago is too long?
				$options = pmpropbbt_getOptions($membership_level->id);
				
				if(pmpro_isLevelRecurring($membership_level)) {
					$cutoff = strtotime("- " . $membership_level->cycle_number . " " . $membership_level->cycle_period, current_time("timestamp")) - ($options['cancel_days']*3600*24);
				} else {
					$cutoff = strtotime("- " . $membership_level->expiration_number . " " . $membership_level->expiration_period, current_time("timestamp")) - ($options['cancel_days']*3600*24);
				}
				
				//too long ago?
				if($paid_order->timestamp < $cutoff)
					$pmpropbbt_pending_member_cache[$user_id][$level_id] = true;
				else
					$pmpropbbt_pending_member_cache[$user_id][$level_id] = false;
			}
			else
			{
				//no previous order, this must be the first
				$pmpropbbt_pending_member_cache[$user_id][$level_id] = true;
			}			
		}
	}
	
	return $pmpropbbt_pending_member_cache[$user_id][$level_id];
}

/*
	For use with multiple memberships per user
*/
function pmprobpc_memberHasAccessWithAnyLevel($user_id){
	$levels = pmpro_getMembershipLevelsForUser($user_id);
	foreach($levels as $level){
		if(!pmpropbbt_isMemberPending($user_id, $level->id)){
			return true;
		}
	}
	return false;
}


/*
	In case anyone was using the typo'd function name.
*/
function pmprobpc_isMemberPending($user_id) { return pmpropbbt_isMemberPending($user_id); }

//if a user's last order is pending status, don't give them access
function pmpropbbt_pmpro_has_membership_access_filter($hasaccess, $mypost, $myuser, $post_membership_levels)
{
	//if they don't have access, ignore this
	if(!$hasaccess)
		return $hasaccess;

	//if this isn't locked by level, ignore this
	if(empty($post_membership_levels))
		return $hasaccess;

	$hasaccess = pmprobpc_memberHasAccessWithAnyLevel($myuser->ID);

	return $hasaccess;
}
add_filter("pmpro_has_membership_access_filter", "pmpropbbt_pmpro_has_membership_access_filter", 10, 4);

/*
	Some notes RE pending status.
*/
//add note to account page RE waiting for check to clear
function pmpropbbt_pmpro_account_bullets_bottom()
{
	//get invoice from DB
	if(!empty($_REQUEST['invoice']))
	{
	    $invoice_code = $_REQUEST['invoice'];

	    if (!empty($invoice_code))
	    	$pmpro_invoice = new MemberOrder($invoice_code);
	}

	//no specific invoice, check current user's last order
	if(empty($pmpro_invoice) || empty($pmpro_invoice->id))
	{
		$pmpro_invoice = new MemberOrder();
		$pmpro_invoice->getLastMemberOrder(NULL, array('success', 'pending', 'cancelled', ''));
	}

	if(!empty($pmpro_invoice) && !empty($pmpro_invoice->id))
	{
		if($pmpro_invoice->status == "pending" && $pmpro_invoice->gateway == "check")
		{
			if(!empty($_REQUEST['invoice']))
			{
				?>
				<li>
					<?php
						if(pmpropbbt_isMemberPending($pmpro_invoice->user_id))
							printf( __('%sMembership pending.%s We are still waiting for payment of this invoice.', 'pmpro-pay-by-bank-transfer'), '<strong>', '</strong>' );
						else
							printf( __('%sImportant Notice:%s We are still waiting for payment of this invoice.', 'pmpro-pay-by-bank-transfer'), '<strong>', '</strong>' );
					?>
				</li>
				<?php
			}
			else
			{
				?>
				<li><?php
						if(pmpropbbt_isMemberPending($pmpro_invoice->user_id))
							printf(__('%sMembership pending.%s We are still waiting for payment for %syour latest invoice%s.', 'pmpro-pay-by-bank-transfer'), '<strong>', '</strong>', sprintf( '<a href="%s">', pmpro_url('invoice', '?invoice=' . $pmpro_invoice->code) ), '</a>' );
						else
							printf(__('%sImportant Notice:%s We are still waiting for payment for %syour latest invoice%s.', 'pmpro-pay-by-bank-transfer'), '<strong>', '</strong>', sprintf( '<a href="%s">', pmpro_url('invoice', '?invoice=' . $pmpro_invoice->code ) ), '</a>' );
					?>
				</li>
				<?php
			}
		}
	}
}
add_action('pmpro_account_bullets_bottom', 'pmpropbbt_pmpro_account_bullets_bottom');
add_action('pmpro_invoice_bullets_bottom', 'pmpropbbt_pmpro_account_bullets_bottom');

/*
	TODO Add note to non-member text RE waiting for check to clear
*/

/**
 * Send Invoice to user if/when changing order status to "success" for Check based payment.
 *
 * @param MemberOrder $morder - Updated order as it's being saved
 */
function pmpropbbt_send_invoice_email( $morder ) {

    // Only worry about this if the order status was changed to "success"
    if ( 'banktransfer' === strtolower( $morder->payment_type ) && 'success' === $morder->status ) {

        $recipient = get_user_by( 'ID', $morder->user_id );

        $invoice_email = new PMProEmail();
        $invoice_email->sendInvoiceEmail( $recipient, $morder );
    }
}

add_action( 'pmpro_updated_order', 'pmpropbbt_send_invoice_email', 10, 1 );
/*
	Create pending orders for recurring levels.
*/
function pmpropbbt_recurring_orders()
{
	global $wpdb;

	//make sure we only run once a day
	$now = current_time('timestamp');
	$today = date("Y-m-d", $now);

	//have to run for each level, so get levels
	$levels = pmpro_getAllLevels(true, true);

	if(empty($levels))
		return;

	foreach($levels as $level)
	{
		//get options
		$options = pmpropbbt_getOptions($level->id);
		if(!empty($options['renewal_days']))
			$date = date("Y-m-d", strtotime("+ " . $options['renewal_days'] . " days", $now));
		else
			$date = $today;

		//need to get all combos of pay cycle and period
		$sqlQuery = "SELECT DISTINCT(CONCAT(cycle_number, ' ', cycle_period)) FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $level->id . "' AND cycle_number > 0 AND status = 'active'";
		$combos = $wpdb->get_col($sqlQuery);

		if(empty($combos))
			continue;

		foreach($combos as $combo)
		{
			//check if it's been one pay period since the last payment
			/*
				- Check should create an invoice X days before expiration based on a setting on the levels page.
				- Set invoice date based on cycle and the day of the month of the member start date.
				- Send a reminder email Y days after initial invoice is created if it's still pending.
				- Cancel membership after Z days if invoice is not paid. Send email.
			*/
			//get all check orders still pending after X days
			$sqlQuery = "
				SELECT o1.id FROM
				    (SELECT mo.id, mo.user_id, mo.timestamp
				    FROM {$wpdb->pmpro_membership_orders} AS mo
				    WHERE mo.membership_id = $level->id
				        AND mo.gateway = 'banktransfer'
				        AND mo.status IN('pending', 'success')
				    ) as o1

					LEFT OUTER JOIN 
					
					(SELECT mo1.id, mo1.user_id, mo1.timestamp
				    FROM {$wpdb->pmpro_membership_orders} AS mo1
				    WHERE mo1.membership_id = $level->id
				        AND mo1.gateway = 'banktransfer'
				        AND mo1.status IN('pending', 'success')
				    ) as o2

					ON o1.user_id = o2.user_id
					AND o1.timestamp < o2.timestamp
					OR (o1.timestamp = o2.timestamp AND o1.id < o2.id)
				WHERE
					o2.id IS NULL
					AND DATE_ADD(o1.timestamp, INTERVAL $combo) <= '" . $date . "'
			";

			if(defined('PMPRO_CRON_LIMIT'))
				$sqlQuery .= " LIMIT " . PMPRO_CRON_LIMIT;

			$orders = $wpdb->get_col($sqlQuery);

			if(empty($orders))
				continue;

			foreach($orders as $order_id)
			{
				$order = new MemberOrder($order_id);
				$user = get_userdata($order->user_id);
				$user->membership_level = pmpro_getMembershipLevelForUser($order->user_id);

				//check that user still has same level?
				if(empty($user->membership_level) || $order->membership_id != $user->membership_level->id)
					continue;

				//create new pending order
				$morder = new MemberOrder();
				$morder->user_id = $order->user_id;
				$morder->membership_id = $user->membership_level->id;
				$morder->InitialPayment = $user->membership_level->billing_amount;
				$morder->PaymentAmount = $user->membership_level->billing_amount;
				$morder->BillingPeriod = $user->membership_level->cycle_period;
				$morder->BillingFrequency = $user->membership_level->cycle_number;
				$morder->subscription_transaction_id = $order->subscription_transaction_id;
				$morder->gateway = "check";
				$morder->setGateway();
				$morder->payment_type = "Check";
				$morder->status = "pending";

				//get timestamp for new order
				$order_timestamp = strtotime("+" . $combo, $order->timestamp);

				//let's skip if there is already an order for this user/level/timestamp
				$sqlQuery = "SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $order->user_id . "' AND membership_id = '" . $order->membership_id . "' AND timestamp = '" . date('d', $order_timestamp) . "' LIMIT 1";
				$dupe = $wpdb->get_var("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $order->user_id . "' AND membership_id = '" . $order->membership_id . "' AND timestamp = '" . $order_timestamp . "' LIMIT 1");
				if(!empty($dupe))
					continue;

				//save it
				$morder->process();
				$morder->saveOrder();

				//update the timestamp
				$morder->updateTimestamp(date("Y", $order_timestamp), date("m", $order_timestamp), date("d", $order_timestamp));

				//send emails
				$email = new PMProEmail();
				$email->template = "bank_transfer_pending";
				$email->email = $user->user_email;
				$email->subject = sprintf(__("New Invoice for %s at %s", "pmpro-pay-by-bank-transfer"), $user->membership_level->name, get_option("blogname"));
			}
		}
	}
}
add_action('pmpropbbt_recurring_orders', 'pmpropbbt_recurring_orders');

/*
	Send reminder emails for pending invoices.
*/
function pmpropbbt_reminder_emails()
{
	global $wpdb;

	//make sure we only run once a day
	$now = current_time('timestamp');
	$today = date("Y-m-d", $now);

	//have to run for each level, so get levels
	$levels = pmpro_getAllLevels(true, true);

	if(empty($levels))
		return;

	foreach($levels as $level)
	{
		//get options
		$options = pmpropbbt_getOptions($level->id);
		if(!empty($options['reminder_days']))
			$date = date("Y-m-d", strtotime("+ " . $options['reminder_days'] . " days", $now));
		else
			$date = $today;

		//need to get all combos of pay cycle and period
		$sqlQuery = "SELECT DISTINCT(CONCAT(cycle_number, ' ', cycle_period)) FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $level->id . "' AND cycle_number > 0 AND status = 'active'";
		$combos = $wpdb->get_col($sqlQuery);

		if(empty($combos))
			continue;

		foreach($combos as $combo)
		{
			//get all check orders still pending after X days
			$sqlQuery = "
				SELECT id 
				FROM $wpdb->pmpro_membership_orders 
				WHERE membership_id = $level->id 
					AND gateway = 'banktransfer' 
					AND status = 'pending' 
					AND DATE_ADD(timestamp, INTERVAL $combo) <= '" . $date . "'
					AND notes NOT LIKE '%Reminder Sent:%' AND notes NOT LIKE '%Reminder Skipped:%'
				ORDER BY id
			";

			if(defined('PMPRO_CRON_LIMIT'))
				$sqlQuery .= " LIMIT " . PMPRO_CRON_LIMIT;

			$orders = $wpdb->get_col($sqlQuery);

			if(empty($orders))
				continue;

			foreach($orders as $order_id)
			{
				//get some data
				$order = new MemberOrder($order_id);
				$user = get_userdata($order->user_id);
				$user->membership_level = pmpro_getMembershipLevelForUser($order->user_id);

				//if they are no longer a member, let's not send them an email
				if(empty($user->membership_level) || empty($user->membership_level->ID) || $user->membership_level->id != $order->membership_id)
				{
					//note when we send the reminder
					$new_notes = $order->notes . "Reminder Skipped:" . $today . "\n";
					$wpdb->query("UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($new_notes) . "' WHERE id = '" . $order_id . "' LIMIT 1");

					continue;
				}

				//note when we send the reminder
				$new_notes = $order->notes . "Reminder Sent:" . $today . "\n";
				$wpdb->query("UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($new_notes) . "' WHERE id = '" . $order_id . "' LIMIT 1");

				//setup email to send
				$email = new PMProEmail();
				$email->template = "bank_transfer_pending_reminder";
				$email->email = $user->user_email;
				$email->subject = sprintf(__("Reminder: New Invoice for %s at %s", "pmpro-pay-by-bank-transfer"), $user->membership_level->name, get_option("blogname"));
				//get body from template
				$email->body = file_get_contents(PMPRO_PAY_BY_BANK_TRANSFER_DIR . "/email/" . $email->template . ".html");

				//setup more data
				$email->data = array(
					"name" => $user->display_name,
					"user_login" => $user->user_login,
					"sitename" => get_option("blogname"),
					"siteemail" => pmpro_getOption("from_email"),
					"membership_id" => $user->membership_level->id,
					"membership_level_name" => $user->membership_level->name,
					"membership_cost" => pmpro_getLevelCost($user->membership_level),
					"login_link" => wp_login_url(pmpro_url("account")),
					"display_name" => $user->display_name,
					"user_email" => $user->user_email,
				);

				$email->data["instructions"] = wp_unslash(  pmpro_getOption('instructions') );
				$email->data["invoice_id"] = $order->code;
				$email->data["invoice_total"] = pmpro_formatPrice($order->total);
				$email->data["invoice_date"] = date(get_option('date_format'), $order->timestamp);
				$email->data["billing_name"] = $order->billing->name;
				$email->data["billing_street"] = $order->billing->street;
				$email->data["billing_city"] = $order->billing->city;
				$email->data["billing_state"] = $order->billing->state;
				$email->data["billing_zip"] = $order->billing->zip;
				$email->data["billing_country"] = $order->billing->country;
				$email->data["billing_phone"] = $order->billing->phone;
				$email->data["cardtype"] = $order->cardtype;
				$email->data["accountnumber"] = hideCardNumber($order->accountnumber);
				$email->data["expirationmonth"] = $order->expirationmonth;
				$email->data["expirationyear"] = $order->expirationyear;
				$email->data["billing_address"] = pmpro_formatAddress($order->billing->name,
																	 $order->billing->street,
																	 "", //address 2
																	 $order->billing->city,
																	 $order->billing->state,
																	 $order->billing->zip,
																	 $order->billing->country,
																	 $order->billing->phone);

				if($order->getDiscountCode())
					$email->data["discount_code"] = "<p>" . __("Discount Code", "pmpro") . ": " . $order->discount_code->code . "</p>\n";
				else
					$email->data["discount_code"] = "";

				//send the email
				$email->sendEmail();
			}
		}
	}
}
add_action('pmpropbbt_reminder_emails', 'pmpropbbt_reminder_emails');

/*
	Cancel overdue members.
*/
function pmpropbbt_cancel_overdue_orders()
{
	global $wpdb;

	//make sure we only run once a day
	$now = current_time('timestamp');
	$today = date("Y-m-d", $now);

	//have to run for each level, so get levels
	$levels = pmpro_getAllLevels(true, true);

	if(empty($levels))
		return;

	foreach($levels as $level)
	{
		//get options
		$options = pmpropbbt_getOptions($level->id);
		if(!empty($options['cancel_days']))
			$date = date("Y-m-d", strtotime("+ " . $options['cancel_days'] . " days", $now));
		else
			$date = $today;

		//need to get all combos of pay cycle and period
		$sqlQuery = "SELECT DISTINCT(CONCAT(cycle_number, ' ', cycle_period)) FROM $wpdb->pmpro_memberships_users WHERE membership_id = '" . $level->id . "' AND cycle_number > 0 AND status = 'active'";
		$combos = $wpdb->get_col($sqlQuery);

		if(empty($combos))
			continue;

		foreach($combos as $combo)
		{
			//get all check orders still pending after X days
			$sqlQuery = "
				SELECT id 
				FROM $wpdb->pmpro_membership_orders 
				WHERE membership_id = $level->id 
					AND gateway = 'banktransfer' 
					AND status = 'pending' 
					AND DATE_ADD(timestamp, INTERVAL $combo) <= '" . $date . "'
					AND notes NOT LIKE '%Cancelled:%' AND notes NOT LIKE '%Cancellation Skipped:%'
				ORDER BY id
			";

			if(defined('PMPRO_CRON_LIMIT'))
				$sqlQuery .= " LIMIT " . PMPRO_CRON_LIMIT;

			$orders = $wpdb->get_col($sqlQuery);

			if(empty($orders))
				continue;

			foreach($orders as $order_id)
			{
				//get the order and user data
				$order = new MemberOrder($order_id);
				$user = get_userdata($order->user_id);
				$user->membership_level = pmpro_getMembershipLevelForUser($order->user_id);

				//if they are no longer a member, let's not send them an email
				if(empty($user->membership_level) || empty($user->membership_level->ID) || $user->membership_level->id != $order->membership_id)
				{
					//note when we send the reminder
					$new_notes = $order->notes . "Cancellation Skipped:" . $today . "\n";
					$wpdb->query("UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($new_notes) . "' WHERE id = '" . $order_id . "' LIMIT 1");

					continue;
				}

				//cancel the order and subscription
				do_action("pmpro_membership_pre_membership_expiry", $order->user_id, $order->membership_id );

				//remove their membership
				pmpro_changeMembershipLevel(false, $order->user_id, 'expired');
				do_action("pmpro_membership_post_membership_expiry", $order->user_id, $order->membership_id );
				$send_email = apply_filters("pmpro_send_expiration_email", true, $order->user_id);
				if($send_email)
				{
					//send an email
					$pmproemail = new PMProEmail();
					$euser = get_userdata($order->user_id);
					$pmproemail->sendMembershipExpiredEmail($euser);
					if(current_user_can('manage_options'))
						printf(__("Membership expired email sent to %s. ", "pmpro"), $euser->user_email);
					else
						echo ". ";
				}
			}
		}
	}
}
add_action('pmpropbbt_cancel_overdue_orders', 'pmpropbbt_cancel_overdue_orders');

/**
 *  Show a different message for users whose checks are pending
 */
function pmpropbbt_check_pending_lock_text( $text ){
	global $current_user;
	//if a user does not have a membership level, return default text.
	if( !pmpro_hasMembershipLevel() ){
		return $text;
	}
	
	if(pmpropbbt_isMemberPending($current_user->ID)==true && pmpropbbt_wouldHaveMembershipAccessIfNotPending()==true){
		$text = __("Your payment is currently pending. You will gain access to this page once it is approved.", "pmpro-pay-by-bank-transfer");
	}
	return $text;
}

function pmpropbbt_wouldHaveMembershipAccessIfNotPending($user_id = NULL){
	global $current_user;
	if(!$user_id)
		$user_id = $current_user->ID;
	
	remove_filter("pmpro_has_membership_access_filter", "pmpropbbt_pmpro_has_membership_access_filter", 10, 4);
	$toReturn = pmpro_has_membership_access(NULL, NULL, true)[0];
	add_filter("pmpro_has_membership_access_filter", "pmpropbbt_pmpro_has_membership_access_filter", 10, 4);
	return $toReturn;
}


/*
	Activation/Deactivation
*/
function pmpropbbt_activation()
{
	//schedule crons
	wp_schedule_event(current_time('timestamp'), 'daily', 'pmpropbbt_cancel_overdue_orders');
	wp_schedule_event(current_time('timestamp')+1, 'daily', 'pmpropbbt_recurring_orders');
	wp_schedule_event(current_time('timestamp')+2, 'daily', 'pmpropbbt_reminder_emails');

	do_action('pmpropbbt_activation');
}
function pmpropbbt_deactivation()
{
	//remove crons
	wp_clear_scheduled_hook('pmpropbbt_cancel_overdue_orders');
	wp_clear_scheduled_hook('pmpropbbt_recurring_orders');
	wp_clear_scheduled_hook('pmpropbbt_reminder_emails');

	do_action('pmpropbbt_deactivation');
}
register_activation_hook(__FILE__, 'pmpropbbt_activation');
register_deactivation_hook(__FILE__, 'pmpropbbt_deactivation');

/*
Function to add links to the plugin row meta
*/
function pmpropbbt_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-pay-by-bank-transfer.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-pay-by-bank-transfer-add-on/')  . '" title="' . esc_attr( __( 'View Documentation', 'paid-memberships-pro' ) ) . '">' . __( 'Docs', 'paid-memberships-pro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'paid-memberships-pro' ) ) . '">' . __( 'Support', 'paid-memberships-pro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpropbbt_plugin_row_meta', 10, 2);
