<?php

require 'auth.php';
require 'lib/database.php';
require 'lib/subscription.php';
require 'lib/chip_api.php';

//Do your payment processor task here
//After complete the payment process you have to record the payment for the invoice due. Use the following example for that:

if ( !isset( $_GET['invoice'] ) ) {
  exit;
}

$invoice_id = htmlspecialchars($_GET['invoice']);

$pabbly = PabblyDatabase::get_instance();

$pabbly->get_lock($invoice_id);

$record = $pabbly->get_purchase($invoice_id);

$purchase_id = $record['chip_slug'];

$subscription = new Subscription(PABBLY_API_KEY, PABBLY_API_SECRET);

if ($record['chip_payment_status'] == 'created') {
  $chip = ChipAPI::get_instance(CHIP_SECRET_KEY, CHIP_BRAND_ID);
  $payment = $chip->get_payment($purchase_id);

  if ($payment['status'] == 'paid') {
    $api_data = json_decode($record['pabbly_hostedpage_data']);
    $user     = $api_data->user;
    $customer = $api_data->customer;
    $product  = $api_data->product;
    // $plan  = $api_data->plan;
    $invoice  = $api_data->invoice;
    $currency = $user->currency;

    try {
      $invoice_id = $invoice->id;
      $payment_mode = $payment['transaction_data']['payment_method'];
      $transaction_data = $payment;
      $payment_note = $purchase_id;

      $api = $subscription->recordPayment($invoice_id, $payment_mode, $payment_note, $transaction_data);
      $api_data = $api->data;

      $pabbly->update_purchase_status($record['id'], $payment['status'], $api_data);

      $pabbly->release_lock($invoice_id);
  
      $subscription->redirectThankYou($api_data->subscription->id, $api_data->subscription->customer_id, $api_data->product->redirect_url);
    } catch (Exception $e) {
        die($e->getMessage());
    }
  }
}

$pabbly->release_lock($invoice_id);

if ($record['chip_payment_status'] != 'paid') {
  exit;
}

$api_data = json_decode( $record['pabbly_record_payment_data'] );
$subscription->redirectThankYou($api_data->subscription->id, $api_data->subscription->customer_id, $api_data->product->redirect_url);
