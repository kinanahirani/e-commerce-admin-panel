<?php
include('includes/crud.php');
$db = new Database();
$db->connect();
include_once('includes/custom-functions.php');
$fn = new custom_functions;
include_once('includes/functions.php');
$function = new functions;

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

/* get order items with not credited commission */
$sql = "SELECT oi.id,date(oi.date_added) as order_date,oi.order_id,oi.product_variant_id,oi.seller_id,oi.sub_total,p.return_days FROM `order_items` oi left JOIN product_variant pv ON pv.id=oi.product_variant_id JOIN products p on p.id=pv.product_id where is_credited=0 ORDER BY oi.`id` DESC";
$db->sql($sql);
$result = $db->getResult();
$todays_date =  date('Y-m-d');
foreach ($result as $row) {
    /* get product return policy */
    $number_of_days = ($row['return_days'] == "" || empty($row['return_days'])) ? 0 : $row['return_days'];
    $return_policy_expire_date = date('Y-m-d', strtotime($row['order_date'] . ' + '.$number_of_days.' days')) ;

    /* check whether policy expires or not */
    if($todays_date > $return_policy_expire_date){
        $seller_info = $fn->get_data($columns = ['commission','email','name'],"id=".$row['seller_id'],"seller");
        $final_commission = 100 - $seller_info[0]['commission'];
        $transfer_amt = ($final_commission * $row['sub_total']) / 100 ;

        /* get seller balance */
        $user_wallet_balance = $fn->get_wallet_balance($row['seller_id'], 'seller');
        $amt = ($transfer_amt + $user_wallet_balance);
        
        /* update seller commission */
        if($fn->update_wallet_balance($amt, $row['seller_id'], 'seller')){
           $sql = "UPDATE order_items SET is_credited=1 where id=".$row['id'];
           if($db->sql($sql)){
            $wallet_txn_id = $fn->add_wallet_transaction($row['order_id'], $row['id'], $row['seller_id'], 'credit', $transfer_amt, 'Commission', 'seller_wallet_transactions');
            if(!empty($wallet_txn_id)){
                /* send notification  */
                $message = "Dear, ". ucwords($seller_info[0]['name']) ." Commission for  order item  ID : #" . $row['id'] . " was transfered. Please take note of it.";
                $fn->send_notification_to_seller($row['seller_id'], "Commission Transfered", $message, 'order', $row['id']);
                echo "success  ";
            }else{
                echo "Commission not transfered to ".$seller_info[0]['name'];
            }
           }else{
               echo "not updated";
           }
        }else{
            echo "no";
        }
    }else{
        // echo "  not now  ";
    }

}
