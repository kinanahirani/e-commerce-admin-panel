<?php
include_once('../../includes/crud.php');
$db = new Database();
$db->connect();
$db->sql("SET NAMES 'utf8'");
session_start();
$auth_username = $db->escapeString($_SESSION["seller_name"]);
$session_seller_id = $db->escapeString($_SESSION["seller_id"]);

include('../../includes/variables.php');
include_once('../../includes/custom-functions.php');

include('send-email.php');
include_once('../../includes/functions.php');
$function = new functions;
$fn = new custom_functions;
$config = $fn->get_configurations();

if (!isset($_SESSION['seller_id']) && !isset($_SESSION['seller_name']) && !isset($_POST['add_seller'])) {
    header("location:../index.php");
}

if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

function checkadmin($auth_username)
{
    $db = new Database();
    $db->connect();
    $db->sql("SELECT `name` FROM `seller` WHERE `name`='$auth_username' LIMIT 1");
    $res = $db->getResult();
    if (!empty($res)) {
        return true;
    } else {
        return false;
    }
}

$settings = $fn->get_settings('system_timezone', true);
$app_name = $settings['app_name'];
$support_email = $settings['support_email'];

if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
    echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
    return false;
}

if (isset($_POST['add_seller']) && $_POST['add_seller'] == 1) {
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));

    $store_name = $db->escapeString($fn->xss_clean($_POST['store_name']));
    $tax_name = $db->escapeString($fn->xss_clean($_POST['tax_name']));
    $tax_number = $db->escapeString($fn->xss_clean($_POST['tax_number']));
    $pan_number = $db->escapeString($fn->xss_clean($_POST['pan_number']));
    $commission = 0;
    $status = '2';

    $password = $db->escapeString($fn->xss_clean($_POST['password']));

    $password = md5($password);

    $sql = 'SELECT id FROM seller WHERE mobile=' . $mobile;
    $db->sql($sql);
    $res = $db->getResult();
    $count = $db->numRows($res);
    if ($count > 0) {
        echo '<label class="alert alert-danger">Mobile Number Already Exists!</label>';
        return false;
    }
    $target_path = '../../upload/seller/';
    if (!is_dir($target_path)) {
        mkdir($target_path, 0777, true);
    }
    if ($_FILES['store_logo']['error'] == 0 && $_FILES['store_logo']['size'] > 0) {

        $extension = pathinfo($_FILES["store_logo"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["store_logo"]);
        if ($result) {
            echo " <span class='label label-danger'>Logo image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["store_logo"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    // address_proof national_id_card
    if ($_FILES['national_id_card']['error'] == 0 && $_FILES['national_id_card']['size'] > 0) {

        $extension = pathinfo($_FILES["national_id_card"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["national_id_card"]);
        if ($result) {
            echo " <span class='label label-danger'>National id card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $national_id_card = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $national_id_card;
        if (!move_uploaded_file($_FILES["national_id_card"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    if ($_FILES['address_proof']['error'] == 0 && $_FILES['address_proof']['size'] > 0) {

        $extension = pathinfo($_FILES["address_proof"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["address_proof"]);
        if ($result) {
            echo " <span class='label label-danger'>Address Proof card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $address_proof = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $address_proof;
        if (!move_uploaded_file($_FILES["address_proof"]["tmp_name"], $full_path)) {
            echo "<p class='alert alert-danger'>Invalid directory to load image!</p>";
            return false;
        }
    }
    $sql = "INSERT INTO `seller`(`name`, `store_name`, `email`, `mobile`, `password`, `logo`,`commission`,`status`,`national_identity_card`,`address_proof`,`pan_number`,`tax_name`,`tax_number`) VALUES ('$name','$store_name','$email', '$mobile', '$password','$filename', '$commission','$status','$national_id_card','$address_proof','$pan_number','$tax_name','$tax_number')";

    if ($db->sql($sql)) {
        echo '<label class="alert alert-success">Seller Added Successfully!</label>';

        $sql = "SELECT * FROM seller WHERE mobile=" . $mobile;
        $db->sql($sql);
        $res = $db->getResult();

        $user_data = $fn->get_data($columns = ['name', 'email', 'mobile'], 'id=' . $res[0]['id'], 'seller');

        $sql = "SELECT * FROM admin WHERE role = 'super admin' ";
        $db->sql($sql);
        $result = $db->getResult();

        if ($db->numRows($res)) {
            $to = $result[0]['email'];
            $subject = "Seller";
            $message = "Hello, Dear " . ucwords($user_data[0]['name']);
            $message .= "Thank you for using our services!";

            if (!send_email($to, $subject, $message)) {
                echo '<label class="alert alert-danger">Email not sent! please try again.</label>';
            } else {
                echo '<label class="alert alert-success">Email Send Successfully! Please check the mail!</label>';
            }
        }
    }
}

if (isset($_POST['update_seller'])  && !empty($_POST['update_seller'])) {

    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    
    $id = $db->escapeString($fn->xss_clean($_POST['update_id']));
    $name = $db->escapeString($fn->xss_clean($_POST['name']));
    $store_name = $db->escapeString($fn->xss_clean($_POST['store_name']));
    $mobile = $db->escapeString($fn->xss_clean($_POST['mobile']));
    $email = $db->escapeString($fn->xss_clean($_POST['email']));
    $tax_name = $db->escapeString($fn->xss_clean($_POST['tax_name']));
    $tax_number = $db->escapeString($fn->xss_clean($_POST['tax_number']));
    $pan_number = $db->escapeString($fn->xss_clean($_POST['pan_number']));

    $status = (isset($_POST['status']) && $_POST['status'] != "") ? $db->escapeString($fn->xss_clean($_POST['status'])) : "2";
    $store_url = (isset($_POST['store_url']) && $_POST['store_url'] != "") ? $db->escapeString($fn->xss_clean($_POST['store_url'])) : "";
     $store_description = (isset($_POST['hide_description']) && $_POST['hide_description'] != "") ? $db->escapeString($fn->xss_clean($_POST['hide_description'])) : "";
    if (strpos($name, "'") !== false) {
        $name = str_replace("'", "''", "$name");
        if (strpos($store_description, "'") !== false)
            $store_description = str_replace("'", "''", "$store_description");
    }
    $street = (isset($_POST['street']) && $_POST['street'] != "") ? $db->escapeString($fn->xss_clean($_POST['street'])) : "";
    $pincode_id = (isset($_POST['pincode_id']) && $_POST['pincode_id'] != "") ? $db->escapeString($fn->xss_clean($_POST['pincode_id'])) : "0";
    $state = (isset($_POST['state']) && $_POST['state'] != "") ? $db->escapeString($fn->xss_clean($_POST['state'])) : "";
    $account_number = (isset($_POST['account_number']) && $_POST['account_number'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_number'])) : "";
    $bank_ifsc_code = (isset($_POST['ifsc_code']) && $_POST['ifsc_code'] != "") ? $db->escapeString($fn->xss_clean($_POST['ifsc_code'])) : "";
    $account_name = (isset($_POST['account_name']) && $_POST['account_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['account_name'])) : "";
    $bank_name = (isset($_POST['bank_name']) && $_POST['bank_name'] != "") ? $db->escapeString($fn->xss_clean($_POST['bank_name'])) : "";
    $latitude = (isset($_POST['latitude']) && $_POST['latitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['latitude'])) : "0";
    $longitude = (isset($_POST['longitude']) && $_POST['longitude'] != "") ? $db->escapeString($fn->xss_clean($_POST['longitude'])) : "0";

  
    $sql = "SELECT * from seller where id='$id'";
    $db->sql($sql);
    $res_id = $db->getResult();
    if(!empty($res_id) && ($res_id[0]['status'] == 2 || $res_id[0]['status'] == 7 )){
        $response['error'] = true;
        $response['message'] = "Seller can not update becasue you have not-approoved or removed.";
        print_r(json_encode($response));
        return false;
        exit();
    }
    if (isset($_POST['old_password']) && $_POST['old_password'] != '') {
        $old_password = $db->escapeString($fn->xss_clean($_POST['old_password']));
        $old_password = md5($old_password);
        $res = $fn->get_data($column = ['password'], "id=" . $id, 'seller');
        if ($res[0]['password'] != $old_password) {
            echo "<label class='alert alert-danger'>Old password does't match.</label>";
            return false;
        }
    }

    if ($_POST['password'] != '' && $_POST['old_password'] == '') {
        echo "<label class='alert alert-danger'>Please enter old password.</label>";
        return false;
    }
    $password = !empty($_POST['password']) ? $db->escapeString($fn->xss_clean($_POST['password'])) : '';
    $password = !empty($password) ? md5($password) : '';

    if ($_FILES['store_logo']['size'] != 0 && $_FILES['store_logo']['error'] == 0 && !empty($_FILES['store_logo'])) {
        //image isn't empty and update the image
        $old_logo = $db->escapeString($fn->xss_clean($_POST['old_logo']));
        $extension = pathinfo($_FILES["store_logo"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["store_logo"]);
        if ($result) {
            echo " <span class='label label-danger'>Logo image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../../upload/seller/';
        $filename = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $filename;
        if (!move_uploaded_file($_FILES["store_logo"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_logo)) {
            unlink($target_path . $old_logo);
        }
        $sql = "UPDATE seller SET `logo`='" . $filename . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if ($_FILES['national_id_card']['size'] != 0 && $_FILES['national_id_card']['error'] == 0 && !empty($_FILES['national_id_card'])) {
        //image isn't empty and update the image
        $old_national_identity_card = $db->escapeString($fn->xss_clean($_POST['old_national_identity_card']));
        $extension = pathinfo($_FILES["national_id_card"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["national_id_card"]);
        if ($result) {
            echo " <span class='label label-danger'>National id card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../../upload/seller/';
        $national_id_card = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $national_id_card;
        if (!move_uploaded_file($_FILES["national_id_card"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_national_identity_card)) {
            unlink($target_path . $old_national_identity_card);
        }
        $sql = "UPDATE seller SET `national_identity_card`='" . $national_id_card . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
    if ($_FILES['address_proof']['size'] != 0 && $_FILES['address_proof']['error'] == 0 && !empty($_FILES['address_proof'])) {
        //image isn't empty and update the image
        $old_address_proof = $db->escapeString($fn->xss_clean($_POST['old_address_proof']));
        $extension = pathinfo($_FILES["address_proof"]["name"])['extension'];

        $result = $fn->validate_image($_FILES["address_proof"]);
        if ($result) {
            echo " <span class='label label-danger'>Address Proof card image type must jpg, jpeg, gif, or png!</span>";
            return false;
            exit();
        }
        $target_path = '../../upload/seller/';
        $address_proof = microtime(true) . '.' . strtolower($extension);
        $full_path = $target_path . "" . $address_proof;
        if (!move_uploaded_file($_FILES["address_proof"]["tmp_name"], $full_path)) {
            echo '<p class="alert alert-danger">Can not upload image.</p>';
            return false;
            exit();
        }
        if (!empty($old_address_proof)) {
            unlink($target_path . $old_address_proof);
        }
        $sql = "UPDATE seller SET `address_proof`='" . $address_proof . "' WHERE `id`=" . $id;
        $db->sql($sql);
    }
   
    if (!empty($password)) {
        $sql = "UPDATE `seller` SET `name`='$name',`latitude`='$latitude',`longitude`='$longitude',`store_name`='$store_name',`email`='$email',`mobile`='$mobile',`password`='$password',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`status`=$status,`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    } else {
        $sql = "UPDATE `seller` SET `name`='$name',`latitude`='$latitude',`longitude`='$longitude',`store_name`='$store_name',`email`='$email',`mobile`='$mobile',`store_url`='$store_url',`store_description`='$store_description',`street`='$street',`pincode_id`='$pincode_id',`state`='$state',`account_number`='$account_number',`bank_ifsc_code`='$bank_ifsc_code',`account_name`='$account_name',`bank_name`='$bank_name',`status`=$status,`pan_number`='$pan_number',`tax_name`='$tax_name',`tax_number`='$tax_number' WHERE id=" . $id;
    }
    if ($db->sql($sql)) {
        echo "<label class='alert alert-success'>Information Updated Successfully.</label>";
    } else {
        echo "<label class='alert alert-danger'>Some Error Occurred! Please Try Again.</label>";
    }
}

if (isset($_POST['change_category'])) {
    if ($_POST['category_id'] == '') {
        $sql = "SELECT * FROM subcategory";
    } else {
        $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
        echo $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
    }

    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['category'])) {
    if ($_POST['category_id'] == '') {
        $sql = "SELECT * FROM subcategory";
    } else {
        $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
        $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
    }

    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        echo "<option value=''>All</option>";
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['find_subcategory'])) {
    $category_id = $db->escapeString($fn->xss_clean($_POST['category_id']));
    $sql = "SELECT * FROM subcategory WHERE category_id=" . $category_id;
    $db->sql($sql);
    $res = $db->getResult();
    if (!empty($res)) {
        foreach ($res as $row) {
            echo "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
        }
    } else {
        echo "<option value=''>--No Sub Category is added--</option>";
    }
}

if (isset($_POST['delete_media']) && !empty($_POST['id']) && $_POST['delete_media'] == 1) {
    $id     = $db->escapeString($fn->xss_clean($_POST['id']));
    $image  = $db->escapeString($fn->xss_clean($_POST['image']));
    if (!empty($image))
        unlink('../' . $image);

    $sql = "DELETE FROM `media` WHERE `id`='" . $id . "'";

    if ($db->sql($sql)) {
        echo 1;
        echo "<p class='alert alert-success'>Media Deleted successfully!</p><br>";
    } else {
        echo 0;
        echo "<p class='alert alert-success'>Media is not Deleted!</p><br>";
    }
}

if (isset($_POST['delete_variant']) && !empty($_POST['delete_variant'])) {
    $v_id = $db->escapeString($fn->xss_clean($_POST['id']));
    $result = $fn->delete_variant($v_id);
    if($result){
        echo 1;
    }else{
        echo 0;
    }
    
}

// upload bulk product - upload products in bulk using  a CSV file
if (isset($_POST['bulk_upload']) && $_POST['bulk_upload'] == 1 && (isset($_POST['type']) && $_POST['type'] == 'products')) {
    if (!checkadmin($auth_username)) {
        echo "<label class='alert alert-danger'>Access denied - You are not authorized to access this page.</label>";
        return false;
    }
    $count = 0;
    $count1 = 0;
    $error = false;
    $filename = $_FILES["upload_file"]["tmp_name"];

    $result = $fn->validate_image($_FILES["upload_file"], false);
    if ($result) {
        $error = true;
    }
    $allowed_status = array("received", "processed", "shipped");
    if ($_FILES["upload_file"]["size"] > 0  && $error == false) {
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            // print_r($emapData);
            if ($count != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id
                $emapData[2] = trim($db->escapeString($emapData[2])); // subcategory id
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = trim($db->escapeString($emapData[6])); // return status
                $emapData[7] = trim($db->escapeString($emapData[7])); // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                $emapData[10] = trim($db->escapeString($emapData[10])); // image
                $emapData[11] = trim($db->escapeString($emapData[11])); // deliverable_type
                $emapData[12] = trim($db->escapeString($emapData[12])); // pincodes
                $emapData[13] = trim($db->escapeString($emapData[13])); // return_days

                $emapData[14] = trim($db->escapeString($emapData[14])); // type
                $emapData[15] = trim($db->escapeString($emapData[15])); // Measurement
                $emapData[16] = trim($db->escapeString($emapData[16])); // Measurement Unit ID
                $emapData[17] = trim($db->escapeString($emapData[17])); // Price
                $emapData[18] = trim($db->escapeString($emapData[18])); // Discounted Price
                $emapData[19] = trim($db->escapeString($emapData[19])); // Serve For
                $emapData[20] = trim($db->escapeString($emapData[20])); // Stock
                $emapData[21] = trim($db->escapeString($emapData[21])); // Stock Unit ID

                if (empty($emapData[0])) {
                    echo '<p class="alert alert-danger">Product Name  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[1])) {
                    echo '<p class="alert alert-danger">Category ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[1])) {
                    $sql = "SELECT categories FROM seller WHERE id = " . $session_seller_id;
                    $db->sql($sql);
                    $res = $db->getResult();
                    if (strpos($res[0]['categories'], $emapData[1]) == false) {
                        echo '<p class="alert alert-danger">Category ID  is not assign to you at row - ' . $count . '</div>';
                        return false;
                    }
                }
                if (empty($emapData[2])) {
                    echo '<p class="alert alert-danger">Subcategory ID  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[6]) && $emapData[6] != 1) {
                    echo '<p class="alert alert-danger">Is Returnable is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] != 1) {
                    echo '<p class="alert alert-danger">Is cancel-able is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (!empty($emapData[7]) && $emapData[7] == 1 && (empty($emapData[8]) || !in_array($emapData[8], $allowed_status))) {
                    echo '<p class="alert alert-danger">Till status is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[7]) && !(empty($emapData[8]))) {
                    echo '<p class="alert alert-danger">Till status is invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[9])) {
                    echo '<p class="alert alert-danger">Description  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[10])) {
                    echo '<p class="alert alert-danger">Image  is empty at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[14]) || ($emapData[14] != 'packet' && $emapData[14] != 'loose')) {
                    echo '<p class="alert alert-danger">Type  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[15]) || !is_numeric($emapData[15])) {
                    echo '<p class="alert alert-danger">Measurement  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $sql = "SELECT id FROM unit";
                $db->sql($sql);
                $ids = $db->getResult();
                $invalid_measurement_unit = 1;
                foreach ($ids as $id) {
                    if ($emapData[16] == $id['id']) {
                        $invalid_measurement_unit = 0;
                    }
                }
                if (empty($emapData[16]) || $invalid_measurement_unit == 1) {
                    echo '<p class="alert alert-danger">Measurement Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[17]) || $emapData[17] <= $emapData[18]) {
                    echo '<p class="alert alert-danger">Price is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                if (empty($emapData[19]) || ($emapData[19] != 'Available' && $emapData[19] != 'Sold Out')) {
                    echo '<p class="alert alert-danger">Serve For  is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
                $invalid_stock_unit = 1;
                foreach ($ids as $id) {
                    if ($emapData[21] == $id['id']) {
                        $invalid_stock_unit = 0;
                    }
                }
                if (empty($emapData[21]) || $invalid_stock_unit == 1) {
                    echo '<p class="alert alert-danger">Stock Unit ID is empty or invalid at row - ' . $count . '</div>';
                    return false;
                }
            }
            $count++;
        }
        fclose($file);
        $file = fopen($filename, "r");
        while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE) {
            // print_r($emapData);
            if ($count1 != 0) {
                $emapData[0] = trim($db->escapeString($emapData[0])); // product name
                $emapData[1] = trim($db->escapeString($emapData[1])); // category id
                $emapData[2] = trim($db->escapeString($emapData[2])); // subcategory id
                $emapData[3] = trim($db->escapeString($emapData[3])); // indicator
                $emapData[4] = trim($db->escapeString($emapData[4])); // manufacturer
                $emapData[5] = trim($db->escapeString($emapData[5])); // made in
                $emapData[6] = !empty($emapData[6]) ? trim($db->escapeString($emapData[6])) : 0; // return status
                $emapData[7] = !empty($emapData[7]) ? trim($db->escapeString($emapData[7])) : 0; // cancel status
                $emapData[8] = trim($db->escapeString($emapData[8])); // till status
                $emapData[9] = trim($db->escapeString($emapData[9])); // description
                $emapData[10] = trim($db->escapeString($emapData[10])); // image
                // $emapData[11] = $session_seller_id; // seller_id
                // $emapData[12] = 2; // is_approved
                $emapData[11] = (!empty($emapData[11]) && $emapData[11] != "") ? trim($db->escapeString($emapData[11])) : ""; // deliverable_type
                $emapData[12] =  (!empty($emapData[12]) && $emapData[12] != "") ? trim($db->escapeString($emapData[12])) : ""; // pincodes
                $emapData[13] =  (!empty($emapData[13]) && $emapData[13] != "") ? trim($db->escapeString($emapData[13])) : "0"; // return_days
                $emapData[14] = trim($db->escapeString($emapData[14])); // type
                $emapData[15] = trim($db->escapeString($emapData[15])); // Measurement
                $emapData[16] = trim($db->escapeString($emapData[16])); // Measurement Unit ID
                $emapData[17] = trim($db->escapeString($emapData[17])); // Price
                $emapData[18] = trim($db->escapeString($emapData[18])); // Discounted Price
                $emapData[19] = trim($db->escapeString($emapData[19])); // Serve For
                $emapData[20] = trim($db->escapeString($emapData[20])); // Stock
                $emapData[21] = trim($db->escapeString($emapData[21])); // Stock Unit ID
                $slug = $function->slugify($emapData[0]);
                $data = array(
                    'name' => $emapData[0],
                    'slug' => $slug,
                    'category_id' => $emapData[1],
                    'subcategory_id' => $emapData[2],
                    'indicator' => $emapData[3],
                    'manufacturer' => $emapData[4],
                    'made_in' => $emapData[5],
                    'return_status' => $emapData[6],
                    'cancelable_status' => $emapData[7],
                    'till_status' => $emapData[8],
                    'description' => $emapData[9],
                    'image' => $emapData[10],
                    'seller_id' => $session_seller_id,
                    'is_approved' => "2",
                    'type' => $emapData[11],
                    'pincodes' => $emapData[12],
                    'return_days' => $emapData[13]
                );
                $db->insert('products', $data);
                $res = $db->getResult();
                $total = (count($emapData) / 8);
                if ($total > 3) {
                    $index = 13;
                    for ($i = 0; $i < ($total - 2); $i++) {
                        $data = array(
                            'product_id' => $res[0],
                            'type' => $emapData[++$index],
                            'measurement' => $emapData[++$index],
                            'measurement_unit_id' => $emapData[++$index],
                            'price' => $emapData[++$index],
                            'discounted_price' => $emapData[++$index],
                            'serve_for' => $emapData[++$index],
                            'stock' => $emapData[++$index],
                            'stock_unit_id' => $emapData[++$index]
                        );
                        $db->insert('product_variant', $data);
                        $res1 = $db->getResult();
                    }
                } else {
                    $data = array(
                        'product_id' => $res[0],
                        'type' => $emapData[14],
                        'measurement' => $emapData[15],
                        'measurement_unit_id' => $emapData[16],
                        'price' => $emapData[17],
                        'discounted_price' => $emapData[18],
                        'serve_for' => $emapData[19],
                        'stock' => $emapData[20],
                        'stock_unit_id' => $emapData[21]
                    );
                    $db->insert('product_variant', $data);
                    $res1 = $db->getResult();
                }
            }

            $count1++;
        }
        fclose($file);
        echo "<p class='alert alert-success'>CSV file is successfully imported!</p><br>";
    } else {
        echo "<p class='alert alert-danger'>Invalid file format! Please upload data in CSV file!</p><br>";
    }
}
