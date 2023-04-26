<?php

require 'auth.php';
require 'lib/database.php';
require 'lib/subscription.php';
require 'lib/chip_api.php';

if ( !isset( $_GET['hostedpage'] ) ) {
  throw new Exception( 'Hosted page data is required' );
}

$hostedpage   = $_GET['hostedpage'];
$subscription = new Subscription( PABBLY_API_KEY, PABBLY_API_SECRET );

try {
  $hostedPage = $subscription->hostedPage( $hostedpage );
  $api_data   = $hostedPage->data;
} catch ( Exception $e ) {
  die( $e->getMessage() );
}

$_subscription = $api_data->subscription;

if ( !property_exists( $api_data, "invoice" ) ) {
	//If the subscription is trial, activate it and redirect to thank you page
	if ( $_subscription->trial_days > 0 ) {
		//Do here your additional things if require.
		//Activate the trial subscription
		$subscription->activateTrialSubscription( $_subscription->id );
		//Redirect to the thank you page
		$subscription->redirectThankYou( $api_data->subscription->id, $api_data->subscription->customer_id, $api_data->product->redirect_url );
	}
}

$user     = $api_data->user;
$customer = $api_data->customer;
$product  = $api_data->product;
$invoice  = $api_data->invoice;
$currency = $user->currency;

$chip = ChipAPI::get_instance( CHIP_SECRET_KEY, CHIP_BRAND_ID );

$send_params = array(
  'success_callback' => INSTALLATION_URL . '/callback.php?invoice=' . $invoice->id,
  'success_redirect' => INSTALLATION_URL . '/callback.php?invoice=' . $invoice->id,
  'reference'        => $invoice->id,
  'creator_agent'    => 'Pabbly: 1.0.0',
  'send_receipt'     => true,
  'due'              => time() + ( abs( 60 ) * 60 ),
  'brand_id'         => CHIP_BRAND_ID,
  'client'           => [],
  'purchase'         => array(
    'timezone'   => 'Asia/Kuala_Lumpur',
    'currency'   => $currency,
    'due_strict' => true,
    'products'   => array( [
      'name'  => substr( $product->product_name, 0, 256 ),
      'price' => round( $invoice->due_amount * 100 ),
    ]),
  ),
);

$full_name = $customer->first_name . ' ' . $customer->last_name;
$email     = $customer->email_id;
$phone     = $user->phone;
$country   = $customer->billing_address->country;
$state     = $customer->billing_address->state;
$city      = $customer->billing_address->city;
$zip_code  = $customer->billing_address->zip_code;
$street    = $customer->billing_address->street1;

if ( !empty( $full_name ) ) {
  $send_params['client']['full_name'] = substr( $full_name, 0, 30 );
}

if ( !empty( $email ) ) {
  $send_params['client']['email'] = $email;
}

if ( !empty( $phone ) ) {
  $send_params['client']['phone'] = $phone;
}

if ( !empty( $country ) ) {
  $send_params['client']['country'] = substr( $country, 0, 2 );
}

if ( !empty( $state ) ) {
  $send_params['client']['state'] = substr( $state, 0, 128 );
}

if ( !empty( $city ) ) {
  $send_params['client']['city'] = substr( $city, 0, 128 );
}

if ( !empty( $zip_code ) ) {
  $send_params['client']['zip_code'] = substr( $zip_code, 0, 32 );
}

if ( !empty( $street ) ) {
  $send_params['client']['street_address'] = substr( $street, 0, 128 );
}

$payment = $chip->create_payment( $send_params );

$pabbly_db = PabblyDatabase::get_instance();

try {
  $pabbly_db->insert_purchase( $payment, $api_data );
} catch ( PDOException $e ) {
  if ( $e->errorInfo[1] == 1062 ) {
     // duplicate entry
  } else {
    throw new Exception( $e->getMessage() );
  }
}

header( 'Location: ' . $payment['checkout_url'] );
exit;
