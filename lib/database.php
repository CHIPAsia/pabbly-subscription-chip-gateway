<?php

class PabblyDatabase {

  private static $_instance;

  public static function get_instance() {
    if ( static::$_instance == null ) {
      static::$_instance = new static();
    }

    return static::$_instance;
  }

  public $conn;
  public function __construct() {
    $host = DB_HOST;
    $dbname = DB_NAME;
    
    try {
      $this->conn = new PDO("mysql:host={$host};dbname={$dbname}", DB_USER, DB_PASSWORD);
    
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo "Database connection failed: " . $e->getMessage();
      exit;
    }

    $this->create_table();
  }

  private function create_table() {
    global $table_prefix;
    $sql = "CREATE TABLE IF NOT EXISTS {$table_prefix}purchases (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      chip_slug varchar(200) NOT NULL,
      chip_checkout_url TEXT NOT NULL,
      chip_payment_status TEXT NOT NULL,
      pabbly_invoice_id varchar(100) NOT NULL,
      pabbly_hostedpage_data MEDIUMTEXT NOT NULL,
      pabbly_record_payment_data MEDIUMTEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_chip_slug (chip_slug),
      UNIQUE KEY unique_pabbly (pabbly_invoice_id)  
      )";

    $this->conn->exec($sql);
  }

  public function insert_purchase($chip, $pabbly) {
    global $table_prefix;
    $stmt = $this->conn->prepare("INSERT INTO {$table_prefix}purchases (chip_slug, chip_checkout_url, chip_payment_status, pabbly_invoice_id, pabbly_hostedpage_data) VALUES (:chip_slug, :chip_checkout_url, :chip_payment_status, :pabbly_invoice_id, :pabbly_hostedpage_data)");
  
    $chip_slug = $chip['id'];
    $chip_checkout_url = $chip['checkout_url'];
    $chip_payment_status = $chip['status'];
    $invoice_id = $pabbly->invoice->id;
    $pabbly_hostedpage_data = json_encode($pabbly);

    $stmt->bindParam(':chip_slug', $chip_slug);
    $stmt->bindParam(':chip_checkout_url', $chip_checkout_url);
    $stmt->bindParam(':chip_payment_status', $chip_payment_status);
    $stmt->bindParam(':pabbly_invoice_id', $invoice_id);
    $stmt->bindParam(':pabbly_hostedpage_data', $pabbly_hostedpage_data);

    return $stmt->execute();
  }

  public function get_purchase($invoice_id) {
    global $table_prefix;
    $stmt = $this->conn->prepare("SELECT * FROM {$table_prefix}purchases WHERE pabbly_invoice_id = :pabbly_invoice_id LIMIT 1");
    $stmt->bindParam(':pabbly_invoice_id', $invoice_id);
    $stmt->execute();

    return $stmt->fetch();
  }

  public function get_lock($name) {
    $stmt = $this->conn->prepare("SELECT GET_LOCK(?, 15)");
    $stmt->execute([$name]);
  }
  public function release_lock($name) {
    $stmt = $this->conn->prepare("SELECT RELEASE_LOCK(?)");
    $stmt->execute([$name]);
  }

  public function update_purchase_status($id, $new_status, $pabbly_record_payment_data) {
    global $table_prefix;
    $stmt = $this->conn->prepare("UPDATE {$table_prefix}purchases SET chip_payment_status = :chip_payment_status, pabbly_record_payment_data = :pabbly_record_payment_data WHERE id = :id");
    
    $pabbly_record_payment_data = json_encode($pabbly_record_payment_data);

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':chip_payment_status', $new_status);
    $stmt->bindParam(':pabbly_record_payment_data', $pabbly_record_payment_data);
    $stmt->execute();
  }
}

PabblyDatabase::get_instance();