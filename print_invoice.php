<?php
$currencySymbols = array(
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    "JPY" =>  '¥',
    "AUD" =>  'A$',
    "CAD" =>  'C$',
    "CHF" =>  'Fr',
    "MAD" =>  'dh',
  
  );
include 'Invoice.php';
$invoice = new Invoice();
$invoice->checkLoggedIn();
if(!empty($_GET['invoice_id']) && $_GET['invoice_id']) {
	echo $_GET['invoice_id'];
	$invoiceValues = $invoice->getInvoice($_GET['invoice_id']);		
	$invoiceItems = $invoice->getInvoiceItems($_GET['invoice_id']);		
}
$invoiceDate = date("d/M/Y, H:i:s", strtotime($invoiceValues['order_date']));
$output = '<table width="100%" border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td colspan="2" align="center" style="font-size:18px"><b>Invoice</b></td>
    </tr>
    <tr>
        <td colspan="2">
            <table width="100%" cellpadding="5">
                <tr>
                    <td width="60%" align="left">
                        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                            <h3>From,</h3>
                            ' . $_SESSION['user'] . '<br>
                            ' . $_SESSION['address'] . '<br>
                            ' . $_SESSION['mobile'] . '<br>
                            ' . $_SESSION['email'] . '<br>
                        </div>
                    </td>
                    <td width="50%" align="left">
					<br>
                        <b>RECEIVER (INVOICE TO)</b><br>
						<br>
                        Name : ' . $invoiceValues['order_receiver_name'] . '<br>
                        Billing Address : ' . $invoiceValues['order_receiver_address'] . '<br>
						<br>
						<br>
                        Invoice No. : ' . $invoiceValues['order_id'] . '<br>
                        Invoice Date : ' . $invoiceDate . '<br>
                    </td>
                </tr>
            </table>
            <br>
            <table width="100%" border="1" cellpadding="5" cellspacing="0">
                <tr>
                    <th align="left">Sr No.</th>
                    <th align="left">Item Code</th>
                    <th align="left">Item Name</th>
                    <th align="left">Quantity</th>
                    <th align="left">Price</th>
                    <th align="left">Actual Amt.</th>
                </tr>';
$count = 0;
foreach ($invoiceItems as $invoiceItem) {
    $count++;
    $output .= '
        <tr>
            <td align="left">' . $count . '</td>
            <td align="left">' . $invoiceItem["item_code"] . '</td>
            <td align="left">' . $invoiceItem["item_name"] . '</td>
            <td align="left">' . $invoiceItem["order_item_quantity"] . '</td>
            <td align="left">' .$currencySymbols[$invoiceValues["currency_n"]] . $invoiceItem["order_item_price"] . '</td>
            <td align="left">' .$currencySymbols[$invoiceValues["currency_n"]] . $invoiceItem["order_item_final_amount"] . '</td>
        </tr>';
}
$output .= '
	<tr>
	<td align="right" colspan="5"><b>Sub Total</b></td>
	<td align="left"><b>'.$currencySymbols[$invoiceValues["currency_n"]] .$invoiceValues['order_total_before_tax'].'</b></td>
	</tr>
	<tr>
	<td align="right" colspan="5"><b>Tax Rate :</b></td>
	<td align="left">%'.$invoiceValues['order_tax_per'].'</td>
	</tr>
	<tr>
	<td align="right" colspan="5">Tax Amount: </td>
	<td align="left">'.$currencySymbols[$invoiceValues["currency_n"]] .$invoiceValues['order_total_tax'].'</td>
	</tr>
	<tr>
	<td align="right" colspan="5">Total: </td>
	<td align="left">'.$currencySymbols[$invoiceValues["currency_n"]] .$invoiceValues['order_total_after_tax'].'</td>
	</tr>
	<tr>
	<td align="right" colspan="5">Amount Paid:</td>
	<td align="left">'.$currencySymbols[$invoiceValues["currency_n"]] .$invoiceValues['order_amount_paid'].'</td>
	</tr>
	<tr>
	<td align="right" colspan="5"><b>Amount Due:</b></td>
	<td align="left">'.$currencySymbols[$invoiceValues["currency_n"]] .$invoiceValues['order_total_amount_due'].'</td>
	</tr>
	<tr>
	<td colspan="6"><b>Notes:</b><br />' . $invoiceValues['note'] . '</td>
	
    </tr>';
	
$output .= '
	</table>
	</td>
	</tr>
	</table>';
// create pdf of invoice	
$invoiceFileName = 'AstroInvoice_'.$invoiceValues['order_id'].'.pdf';
require_once 'dompdf/src/Autoloader.php';
Dompdf\Autoloader::register();
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml(html_entity_decode($output));
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream($invoiceFileName, array("Attachment" => false));
?>   
   