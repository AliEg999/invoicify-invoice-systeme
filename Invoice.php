<?php
session_start();

class Invoice{
	private $host  = 'localhost';
    private $user  = 'root';
    private $password   = "";
    private $database  = "simpleinvoicephp";   
	private $invoiceUserTable = 'invoice_user';	
    private $invoiceOrderTable = 'invoice_order';
	private $invoiceOrderItemTable = 'invoice_order_item';
	private $dbConnect = false;
    public function __construct(){
        if(!$this->dbConnect){ 
            $conn = new mysqli($this->host, $this->user, $this->password, $this->database);
            if($conn->connect_error){
                die("Error failed to connect to MySQL: " . $conn->connect_error);
            }else{
                $this->dbConnect = $conn;
            }
        }
    }
	private function getData($sqlQuery) {
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		if(!$result){
			die('Error in query: '. mysqli_error());
		}
		$data= array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$data[]=$row;            
		}
		return $data;
	}
	private function getNumRows($sqlQuery) {
		$result = mysqli_query($this->dbConnect, $sqlQuery);
		if(!$result){
			die('Error in query: '. mysqli_error());
		}
		$numRows = mysqli_num_rows($result);
		return $numRows;
	}
	public function loginUsers($email, $password){
		$sqlQuery = "
			SELECT id, email, first_name, last_name, address, mobile 
			FROM ".$this->invoiceUserTable." 
			WHERE email='".$email."' AND password='".$password."'";
        return  $this->getData($sqlQuery);
	}	
	public function checkLoggedIn(){
		if(!$_SESSION['userid']) {
			header("Location:index.php");
		}
	}		
	public function saveInvoice($POST) {
		// Insert the invoice header information into the database, including the currency
		$sqlInsert = "INSERT INTO " . $this->invoiceOrderTable . "(user_id, order_receiver_name, order_receiver_address, order_total_before_tax, order_total_tax, order_tax_per, order_total_after_tax, order_amount_paid, order_total_amount_due, note, currency_n) VALUES ('" . $POST['userId'] . "', '" . $POST['companyName'] . "', '" . $POST['address'] . "', '" . $POST['subTotal'] . "', '" . $POST['taxAmount'] . "', '" . $POST['taxRate'] . "', '" . $POST['totalAftertax'] . "', '" . $POST['amountPaid'] . "', '" . $POST['amountDue'] . "', '" . $POST['notes'] . "', '" . $POST['currency'] . "')";
		mysqli_query($this->dbConnect, $sqlInsert);
		$lastInsertId = mysqli_insert_id($this->dbConnect);
	
		// Insert the invoice line items into the database
		for ($i = 0; $i < count($POST['productCode']); $i++) {
			// Insert the line item with the currency
			$sqlInsertItem = "INSERT INTO " . $this->invoiceOrderItemTable . "(order_id, item_code, item_name, order_item_quantity, order_item_price, order_item_final_amount) VALUES ('" . $lastInsertId . "', '" . $POST['productCode'][$i] . "', '" . $POST['productName'][$i] . "', '" . $POST['quantity'][$i] . "', '" . $POST['price'][$i] . "', '" . $POST['total'][$i] . "')";
			mysqli_query($this->dbConnect, $sqlInsertItem);
		}
	}
	
	
	public function updateInvoice($POST) {
		if($POST['invoiceId']) {	
			$sqlInsert = "UPDATE ".$this->invoiceOrderTable." 
				SET order_receiver_name = '".$POST['companyName']."', order_receiver_address= '".$POST['address']."', order_total_before_tax = '".$POST['subTotal']."', order_total_tax = '".$POST['taxAmount']."', order_tax_per = '".$POST['taxRate']."', order_total_after_tax = '".$POST['totalAftertax']."', order_amount_paid = '".$POST['amountPaid']."', order_total_amount_due = '".$POST['amountDue']."', note = '".$POST['notes']."',status = '".$POST['status']."' 
				WHERE user_id = '".$POST['userId']."' AND order_id = '".$POST['invoiceId']."'";		
			mysqli_query($this->dbConnect, $sqlInsert);	
		}		
		$this->deleteInvoiceItems($POST['invoiceId']);
		
// Check if productCode exists in $_POST and is an array
if(isset($_POST['productCode']) && is_array($_POST['productCode'])) {
    // Loop through the productCode array
    for ($i = 0; $i < count($_POST['productCode']); $i++) {
        // Sanitize and validate input data before using it in the SQL query
        $itemCode = mysqli_real_escape_string($this->dbConnect, $_POST['productCode'][$i]);
        $itemName = mysqli_real_escape_string($this->dbConnect, $_POST['productName'][$i]);
        $quantity = intval($_POST['quantity'][$i]); // Convert to integer
        $price = floatval($_POST['price'][$i]); // Convert to float
        $totalAmount = floatval($_POST['total'][$i]); // Convert to float
        
        // Prepare and execute the SQL insert statement
        $sqlInsertItem = "INSERT INTO " . $this->invoiceOrderItemTable . "(order_id, item_code, item_name, order_item_quantity, order_item_price, order_item_final_amount) 
            VALUES ('" . $_POST['invoiceId'] . "', '$itemCode', '$itemName', $quantity, $price, $totalAmount)";
        
        mysqli_query($this->dbConnect, $sqlInsertItem);
    }
}



	}	
	public function getInvoiceList(){
		$sqlQuery = "SELECT * FROM ".$this->invoiceOrderTable." 
			WHERE user_id = '".$_SESSION['userid']."'";
		return  $this->getData($sqlQuery);
	}	
	public function getInvoice($invoiceId){
		$sqlQuery = "SELECT * FROM ".$this->invoiceOrderTable." 
			WHERE user_id = '".$_SESSION['userid']."' AND order_id = '$invoiceId'";
		$result = mysqli_query($this->dbConnect, $sqlQuery);	
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row;
	}	
	public function getInvoiceItems($invoiceId){
		$sqlQuery = "SELECT * FROM ".$this->invoiceOrderItemTable." 
			WHERE order_id = '$invoiceId'";
		return  $this->getData($sqlQuery);	
	}
	public function deleteInvoiceItems($invoiceId){
		$sqlQuery = "DELETE FROM ".$this->invoiceOrderItemTable." 
			WHERE order_id = '".$invoiceId."'";
		mysqli_query($this->dbConnect, $sqlQuery);				
	}
	public function deleteInvoice($invoiceId){
		$sqlQuery = "DELETE FROM ".$this->invoiceOrderTable." 
			WHERE order_id = '".$invoiceId."'";
		mysqli_query($this->dbConnect, $sqlQuery);	
		$this->deleteInvoiceItems($invoiceId);	
		return 1;
	}



}
?>