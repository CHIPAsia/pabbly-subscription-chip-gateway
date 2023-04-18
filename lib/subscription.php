<?php

class Subscription {

  var $apiKey = "";
  var $apiSecret = "";
  var $apiUrl = "https://payments.pabbly.com/api/v1/";
  var $thankyouUrl = "https://payments.pabbly.com/thankyou/";
  public function __construct($apiKey, $apiSecret) {
    if (!$apiKey && !$apiSecret) {
      throw new Exception('Error: apikey and api secret are required');
    }

    $this->apiKey = $apiKey;
    $this->apiSecret = $apiSecret;
  }

  public function apiPath($path) {
    return $this->apiUrl . $path;
  }

  public function subscribe($data) {

    $post_data = array(
      'first_name' => $data['first_name'],
      'last_name' => $data['last_name'],
      'email' => $data['email'],
      'gateway_type' => $data['gateway_name'],
      'street' => $data['street'],
      'city' => $data['city'],
      'state' => $data['state'],
      'zip_code' => $data['zip_code'],
      'country' => $data['country'],
      'plan_id' => $data['plan_id'],
    );

    return $this->curl( $this->apiPath('subscription'), $post_data );
  }

  public function recordPayment($invoice_id, $payment_mode, $payment_note, $transaction_data) {
    if (!$invoice_id) {
      throw new Exception('Error: invoice id is required');
    }

    $post_data = array(
      'payment_mode' => $payment_mode,
      'payment_note' => $payment_note,
      'transaction' => $transaction_data
    );

    return $this->curl( $this->apiPath('invoice/recordpayment/'), $post_data );
  }

  public function redirectThankYou($subscriptionId, $customerId, $redirect_url = '') {
    if (isset($_GET['hostedpage'])) {
      $hostedpage = $_GET['hostedpage'];
    } else if (isset($_POST['hostedpage'])) {
      $hostedpage = $_POST['hostedpage'];
    } else {
      $hostedpage = '';
    }

    if ($redirect_url && $redirect_url !== '') {
      $redirect_url = $redirect_url . "?hostedpage=" . $hostedpage;    
    } else {
      $redirect_url = $this->thankyouUrl . $subscriptionId . "/" . $customerId;    
    }

    header('Location:' . $redirect_url); 
    exit;
  }

  public function hostedPage($hostedpage) {
    $post_data = array(
      'hostedpage' => $hostedpage
    );

    return $this->curl( $this->apiPath('hostedpage'), $post_data );
  }

  /**
   * Activate the trial subscription
   */
  public function activateTrialSubscription($subscription_id) {
    if (!$subscription_id) {
      throw new Exception('Error: invoice id is required');
    }

    return $this->curl( $this->apiPath('subscription/activatetrial/' . $subscription_id) );
  }

  public function getCustomer($customerId)
  {
    return $this->curl( $this->apiPath('customer/' . $customerId) );
  }

  private function curl( $api_path, $post_data = array() ) {
    $process = curl_init( $api_path );

    curl_setopt($process, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret );
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);

    if ( !empty($post_data) ) {
      curl_setopt($process, CURLOPT_POSTFIELDS, $post_data );
    }

    $return = curl_exec($process);

    if ($return === false) {
      throw new Exception('Error: ' . curl_error($process));
    }

    curl_close($process);

    return json_decode( $return );
  }
}