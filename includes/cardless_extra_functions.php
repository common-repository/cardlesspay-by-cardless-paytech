<?php

/**
 * This filter ads the CardlessPay Status Update All button to the top of WooCommerce Orders page
 */
add_filter('views_edit-shop_order', function ($args) {
  echo '<button class="button wc-action-button wc-action-button-view status_update_all view status_update_all" href="#" aria-label="Status Update">CardlessPay Status Update All</button><hr class="wp-header-end">';
  return $args;
});

/**
 * Enqueue the js
 *
 * @param  string $hook
 */
function cardless_money_js_enqueue($hook)
{
  wp_enqueue_script('status-update-script', plugins_url('../js/cardless_scripts.js', __FILE__), array('jquery'));

  wp_localize_script('status-update-script', 'ajax_object_name', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce("status_update_nonce"),
    'thing1_value' => 'more data I want'
  ));
}
add_action('admin_enqueue_scripts', 'cardless_money_js_enqueue');

/**
 * Script to run status updates on ALL orders under current user
 */
function cardless_status_update_all()
{
  check_ajax_referer('status_update_nonce', 'security');
  require_once('gateway.php');

  //Get options and make them usable
  $options = get_option('cardless_settings');
  $endpoint = $options['cardless_api_endpoint'];
  $cardless_token = $options['cardless_token'];
  $verification_mode = $options['cardless_override_risky_option'];

  $gateway = new WC_Gateway_Cardless_Pay();
  $delim  = FALSE;
  $delim_char = ',';

  //Get user id
  $current_account = wp_get_current_user();
  $current_customer = $current_account->ID;

  $args = array(
    'customer' => $current_customer,
    'limit' => -1,
  );

  $orders = wc_get_orders($args);

  foreach ($orders as $order) {
    //Get $order and $results
    $order_id = $order->get_id();
    $check_id = get_post_meta($order_id, '_cardlessmoney_payment_check_id', TRUE);

    $data = array(
      'Cardless_Token' => $cardless_token,
      'Check_ID'  => $check_id,
      'x_delim_data'  => FALSE,
      'x_delim_char'  => ',',
    );

    $results = $gateway->api_call('CheckStatus', $data);

    if ($order->get_status() == 'completed' || $order->get_payment_method() != 'cardlessmoney' || $order->get_status() == 'failed' || $order->get_status() == 'refunded' || $order->get_status() == 'cancelled') {
      continue;
    }

    if ($results) { //The call succeeded, time to parse
      if ($results['Result'] == '0') { //Check was found in the system

        if ($results['VerifyResult'] == '0') { //Success

          if ($results['Rejected'] != 'True') {

            if ($results['Deleted'] != 'True') {
              $order->update_status('processing');
              $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Processing.');
            } else { //Deleted
              $order->update_status('failed');
              $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Deleted by Cardless Paytech LLC or by merchant.');
            }
          } else { //Rejected
            $order->update_status('failed');
            $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Rejected by Cardless Paytech LLC.');
          }
        } else if ($results['VerifyResult'] == '1') { //This case should probably never happen
          $order->update_status('processing');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Will be verified on next batch.');
        } else if ($results['VerifyResult'] == '2') {
          if ($verification_mode == 'permissive') { //Permissive mode
            $args = array(
              'post_id' => $order->get_id(),
              'orderby' => 'comment_ID',
              'order'   => 'DESC',
              'approve' => 'approve',
              'type'    => 'order_note',
              'number'  => 1
            );

            remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
            $notes = get_comments($args);
            add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
            //Check order notes to see if last note was a manual override
            if ($notes[0]->comment_content == 'Risky/Bad check found in Cardless Paytech LLC system and overridden. Check will be processed by Cardless Paytech LLC.') {
              $order->add_order_note('Order marked Risky/Bad and previously overridden.');
            } else if ($notes[0]->comment_content == 'Order marked Risky/Bad and previously overridden.') {
              $order->add_order_note('Order marked Risky/Bad and previously overridden.');
            } else {
              $order->update_status('on-hold');
              $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Can be manually overridden by selecting the \'CardlessPay Override Risky/Bad\' order action from the Order actions dropdown.');
            }
          } else { //Legacy mode
            $order->update_status('failed');
            $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Will require manual override in Cardless Paytech LLC portal if need processed. Must be in permissive mode (this can be changed from the CardlessPay options menu). Once in permissive mode, click the CardlessPay status update all button, or do an individual status update, and then do the \'CardlessPay Override Risky/Bad\' order action to override.');
          }
        } else if ($results['VerifyResult'] == '3') {
          $order->update_status('failed');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Cannot be overridden.');
        } else if ($results['VerifyResult'] == '4') {
          $order->update_status('failed');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Verification system offline. Please try again later.');
        } else {
          $order->update_status('failed');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Unknown failure.');
          echo "Verification not completed.<br/>Error Code: {$results['VerifyResult']}<br/>Error: {$results['ResultDescription']}<br/>";
        }
      } else if ($results['Result'] == '1' || $results['Result'] == '2') { //client id not in database
        $order->add_order_note("WooCommerce ran a CardlessPay Status Update on this check and it appears that it was created in a different API than your current verification mode: " . cardless_get_mode($endpoint) . ". Please ensure you're using the correct API mode before running another status update or delete this order!");
      } else if ($results['Result'] == '24') {
        $order->add_order_note(__('CardlessPay check is not accepted (Error code: 24, Description: Routing number not found).', 'woocommerce-gateway-cardless-money'));
      } else if ($results['Result'] == '51') {
        $order->add_order_note('Error: ' . $results['ResultDescription'] . ' This may be caused by incorrect API credentials, this order was created in a different API mode than ' . cardless_get_mode($endpoint) . ', or there was some unknown error.');
      } else { //Check not found 
        $order->update_status('failed');
        $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: check not found.');
        echo "Verification not completed.<br/>Error Code: {$results['Result']}<br/>Error: {$results['ResultDescription']}<br/>";
      }
    } else { //The call failed!
      echo "GATEWAY ERROR: " . $gateway->cardless_money_getLastError();
    }
  } //END foreach($orders as $order)
} //END cardless_status_update_all()
add_action("wp_ajax_status_update_all_hook", "cardless_status_update_all");

/**
 * Add the custom order action 'CardlessPay Status Update' in the WooCommerce Orders menu
 * The 'CardlessPay Status Update' action is in an 'Order actions' dropdown
 * Select the 'CardlessPay Status Update' option and click the execute button to run the status update on the individual order
 *
 * @param array $actions
 */
function cardless_single_status_update_order_action($actions)
{
  $actions['cardless_single_status_update_order_action'] = __('CardlessPay Status Update', 'woocommerce-gateway-cardless-money');
  return $actions;
}
add_action('woocommerce_order_actions', 'cardless_single_status_update_order_action');

/**
 * Script to run status updates on ONE order
 *
 * @param WC_Order $order
 */
function cardless_single_status_update($order)
{
  require_once('gateway.php');

  if ($order->get_status() == 'completed' || $order->get_payment_method() != 'cardlessmoney' || $order->get_status() == 'failed' || $order->get_status() == 'refunded' || $order->get_status() == 'cancelled') {
    return;
  }

  $options = get_option('cardless_settings');
  $cardless_token = $options['cardless_token'];
  $endpoint = $options['cardless_api_endpoint'];
  $verification_mode = $options['cardless_override_risky_option'];

  $gateway = new WC_Gateway_Cardless_Pay();
  $delim  = FALSE;
  $delim_char = ',';

  $check_id = get_post_meta($order->get_id(), '_cardlessmoney_payment_check_id', TRUE);

  $data = array(
    'Cardless_Token' => $cardless_token,
    'Check_ID'  => $check_id,
    'x_delim_data'  => FALSE,
    'x_delim_char'  => ',',
  );

  $results = $gateway->api_call('CheckStatus', $data);

  if ($results) { //The call succeeded, time to parse

    if ($results['Result'] == '0') { //Check was found in the system

      if ($results['VerifyResult'] == '0') { //Success

        if ($results['Rejected'] != 'True') {

          if ($results['Deleted'] != 'True') {
            $order->update_status('processing');
            $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Processing.');
          } else { //Deleted
            $order->update_status('failed');
            $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Deleted by Cardless Paytech LLC or by merchant.');
          }
        } else { //Rejected
          $order->update_status('failed');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Rejected by Cardless Paytech LLC.');
        }
      } else if ($results['VerifyResult'] == '1') { //This case should never happen
        $order->update_status('processing');
        $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Will be verified on next batch.');
      } else if ($results['VerifyResult'] == '2') {
        if ($verification_mode == 'permissive') { //Permissive mode
          $args = array(
            'post_id' => $order->get_id(),
            'orderby' => 'comment_ID',
            'order'   => 'DESC',
            'approve' => 'approve',
            'type'    => 'order_note',
            'number'  => 1
          );

          remove_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
          $notes = get_comments($args);
          add_filter('comments_clauses', array('WC_Comments', 'exclude_order_comments'), 10, 1);
          //Check order notes to see if last note was a manual override
          if ($notes[0]->comment_content == 'Risky/Bad check found in Cardless Paytech LLC system and overridden. Check will be processed by Cardless Paytech LLC.') {
            $order->add_order_note('Order marked Risky/Bad and previously overridden.');
          } else if ($notes[0]->comment_content == 'Order marked Risky/Bad and previously overridden.') {
            $order->add_order_note('Order marked Risky/Bad and previously overridden.');
          } else {
            $order->update_status('on-hold');
            $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Can be manually overridden by selecting the \'CardlessPay Override Risky/Bad\' order action from the Order actions dropdown.');
          }
        } else { //Legacy mode
          $order->update_status('failed');
          $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Will require manual override in Cardless Paytech LLC portal if need processed. Must be in permissive mode (this can be changed from the CardlessPay options menu). Once in permissive mode, click the CardlessPay status update all button, or do an individual status update, and then do the \'CardlessPay Override Risky/Bad\' order action to override.');
        }
      } else if ($results['VerifyResult'] == '3') {
        $order->update_status('failed');
        $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Risky or Bad. Cannot be overridden.');
      } else if ($results['VerifyResult'] == '4') {
        $order->update_status('failed');
        $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Verification system offline. Please try again later.');
      } else {
        $order->update_status('failed');
        $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: Unknown failure.');
        echo "Verification not completed.<br/>Error Code: {$results['VerifyResult']}<br/>Error: {$results['ResultDescription']}<br/>";
      }
    } else if ($results['Result'] == '1' || $results['Result'] == '2') { //wrong api
      $order->add_order_note("WooCommerce ran a CardlessPay Status Update on this check and it appears that it was created in a different API than your current verification mode: " . cardless_get_mode($endpoint) . ". Please ensure you're using the correct API mode before running another status update or delete this order!");
    } else if ($results['Result'] == '24') {
      $order->add_order_note(__('CardlessPay check is not accepted (Error code: 24, Description: Routing number not found).', 'woocommerce-gateway-cardless-money'));
    } else if ($results['Result'] == '51') {
      $order->add_order_note('Error: ' . $results['ResultDescription'] . ' This may be caused by incorrect API credentials, this order was created in a different API mode than ' . cardless_get_mode($endpoint) . ', or there was some unknown error.');
    } else { //Check not found 
      $order->update_status('failed');
      $order->add_order_note('Verification process completed by Cardless Paytech LLC and verification status returned is: check not found.');
      echo "Verification not completed.<br/>Error Code: {$results['Result']}<br/>Error: {$results['ResultDescription']}<br/>";
    }
  } else { //The call failed!
    echo "GATEWAY ERROR: " . $gateway->cardless_money_getLastError();
  }
} //END cardless_single_status_update()
add_action('woocommerce_order_action_cardless_single_status_update_order_action', 'cardless_single_status_update');

/**
 * Add the custom order action 'CardlessPay Override Risky/Bad' in the WooCommerce Orders menu
 * The 'CardlessPay Override Risky/Bad' action is in an 'Order actions' dropdown
 * Select the 'CardlessPay Override Risky/Bad' option and click the execute button to run the override
 * on the selected check
 *
 * @param array $actions
 * @return array $actions
 */
function cardless_override_risky_order_action($actions)
{
  global $theorder;
  $order = wc_get_order($theorder->get_id());
  $options = get_option('cardless_settings');
  $verification_mode = $options['cardless_override_risky_option'];

  if ($order->get_status() != 'on-hold') { //bail if order not on-hold or not in permissive mode
    return $actions;
  }
  if ($verification_mode != 'permissive') {
    return $actions;
  }
  $actions['cardless_override_risky_order_action'] = __('CardlessPay Override Risky/Bad', 'woocommerce-gateway-cardless-money');
  return $actions;
}
add_action('woocommerce_order_actions', 'cardless_override_risky_order_action');

/**
 * Script to run override Risky/Bad checks
 *
 * @param WC_Order $order
 */
function cardless_override_risky($order)
{
  require_once('gateway.php');

  //Get options and make them usable
  $options = get_option('cardless_settings');
  // $client_id = $options['cardless_client_id'];
  // $api_pass = $options['cardless_api_password'];
  $cardless_token = $options['cardless_token'];
  $endpoint = $options['cardless_api_endpoint'];

  $gateway = new WC_Gateway_Cardless_Pay();
  $delim  = FALSE;
  $delim_char = ',';

  $check_id = get_post_meta($order->get_id(), '_cardlessmoney_payment_check_id', TRUE);

  $data = array(
    'Client_ID'  => $client_id,
    'ApiPassword'  => $api_pass,
    'Cardless_Token' => $cardless_token,
    'Check_ID'  => $check_id,
    'x_delim_data'  => FALSE,
    'x_delim_char'  => ',',
  );

  $results = $gateway->api_call('VerificationOverride', $data);

  if ($results['Result'] == '0') { //check found and overridden
    $order->update_status('processing');
    $order->add_order_note('Risky/Bad check found in Cardless Paytech LLC system and overridden. Check will be processed by Cardless Paytech LLC.');
  } else if ($results['Result'] == '55') {
    $order->update_status('processing');
    $order->add_order_note('Verification passed, override not necessary. Check will be processed by Cardless Paytech LLC.');
  } else if ($results['Result'] == '56') {
    $order->update_status('failed');
    $order->add_order_note('Verification failed and cannot be overridden. Check will not be processed by Cardless Paytech LLC.');
  } else { //check not found in system. Mark as Failed
    $order->update_status('failed');
    $order->add_order_note('Check not found in Cardless Paytech LLC system.');
  }
} //END cardless_override_risky()
add_action('woocommerce_order_action_cardless_override_risky_order_action', 'cardless_override_risky');

/**
 * Will run a check to see if the API credentials are correct for the chosen API endpoint (Test or Live) and display an error message if incorrect
 */
function cardless_test_authentication()
{
  require_once('gateway.php');

  $options = get_option('cardless_settings');
  // $client_id = $options['cardless_client_id'];
  // $api_pass = $options['cardless_api_password'];
  $cardless_token = $option['cardless_token'];
  $endpoint = $options['cardless_api_endpoint'];

  if ($endpoint) { //endpoint found in settings and set

    $data = array(
      'Client_ID'  => $client_id,
      'ApiPassword'  => $api_pass,
      'Cardless_Token' => $cardless_token,
      'x_delim_data'  => FALSE,
      'x_delim_char'  => ',',
    );

    $gateway = new WC_Gateway_Cardless_Pay();

    $results = $gateway->api_call('TestAuthentication', $data);

    if ($results) { //call success

      if ($results['Result'] != '0') { //incorrect credentials so display error message
        ?>
<div class="notice notice-error">
    <p><?php
                      echo 'The Cardless Paytech  Access Token for use with the chosen API Live. Please check  for accuracy and keep in mind that the credentials for the Test server are different from the credentials for the Live server.';
                      ?></p>
</div>
<?php
      }
    }
  }

  // if ($client_id) { //id found in settings and set

  //   if ($api_pass) { //api pass found in settings and set


  //   }
  // }
}
add_action('admin_notices', 'cardless_test_authentication');

/**
 * Simply return what verification mode we're in
 * @param string $endpoint  string with API endpoint pulled from options
 */
function cardless_get_mode($endpoint)
{
  if ($endpoint == 'https://www.cardlesspaytech.com/api/v1/create_check') {
    $mode = 'Live';
  } else {
    $mode = 'Test';
  }
  return $mode;
}

?>
