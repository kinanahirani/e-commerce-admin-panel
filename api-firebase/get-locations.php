<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
include '../includes/crud.php';
require_once '../includes/functions.php';
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
// date_default_timezone_set('Asia/Kolkata');
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. get_areas
2. get_pincodes
-------------------------------------------
-------------------------------------------
*/

if (!verify_token()) {
    return false;
}


if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_areas']) && $_POST['get_areas'] == 1) {
    /*  
    1. get_areas
        accesskey:90336
        get_areas:1
        id:229              // {optional}
        pincode_id:1        // {optional}
        offset:0            // {optional}
        limit:10            // {optional}
        sort:id             // {optional}
        order:DESC / ASC    // {optional}
    */

    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;

    $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    $id = (isset($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";
    $pincode_id = (isset($_POST['pincode_id'])) ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "";

    $where = "";
    if (isset($_POST['id']) && !empty($_POST['id']) && is_numeric($_POST['id'])) {
        $where .=  !empty($where) ? " AND `id`=" . $id : " WHERE `id`=" . $id;
    }
    if (isset($_POST['pincode_id']) && !empty($_POST['pincode_id']) && is_numeric($_POST['pincode_id'])) {
        $where .=  !empty($where) ? " AND `pincode_id`=" . $pincode_id : " WHERE `pincode_id`=" . $pincode_id;
    }

    $sql = "SELECT count(id) as total FROM area $where";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT * FROM area $where ORDER BY $sort $order LIMIT $offset,$limit ";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Areas retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
        $response['total'] = $total[0]['total'];
        $response['data'] = array();
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_pincodes']) && $_POST['get_pincodes'] == 1) {
    /*  
    2. get_pincodes
        accesskey:90336
        get_pincodes:1
        id:1                // {optional}
        offset:0            // {optional}
        limit:10            // {optional}
        sort:id             // {optional}
        order:DESC / ASC    // {optional}
    */

    $offset = (isset($_POST['offset']) && !empty($fn->xss_clean($_POST['offset'])) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    $limit = (isset($_POST['limit']) && !empty($fn->xss_clean($_POST['limit'])) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;

    $sort = (isset($_POST['sort']) && !empty($fn->xss_clean($_POST['sort']))) ? $db->escapeString($fn->xss_clean($_POST['sort'])) : 'id';
    $order = (isset($_POST['order']) && !empty($fn->xss_clean($_POST['order']))) ? $db->escapeString($fn->xss_clean($_POST['order'])) : 'DESC';

    $id = (isset($_POST['id'])) ? $db->escapeString($fn->xss_clean($_POST['id'])) : "";

    $where = "";
    if (isset($_POST['id']) && !empty($_POST['id']) && is_numeric($_POST['id'])) {
        $where .=  !empty($where) ? " AND `id`=" . $id : " WHERE `id`=" . $id;
    }

    $sql = "SELECT count(id) as total FROM pincodes WHERE status = 1 $where";
    $db->sql($sql);
    $total = $db->getResult();

    $sql = "SELECT * FROM pincodes where status = 1 $where ORDER BY $sort $order LIMIT $offset,$limit ";
    $db->sql($sql);
    $res = $db->getResult();

    if (!empty($res)) {
        $response['error'] = false;
        $response['message'] = "Pincodes retrieved successfully";
        $response['total'] = $total[0]['total'];
        $response['data'] = $res;
    } else {
        $response['error'] = true;
        $response['message'] = "No data found!";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Pass All Field!";
}
print_r(json_encode($response));
return false;
