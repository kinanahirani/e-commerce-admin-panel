<?php
include_once('includes/functions.php');
include_once('includes/custom-functions.php');
$fn = new custom_functions;
?>
<?php

if (isset($_POST['btnAdd'])) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    if ($permissions['locations']['create'] == 1) {
        $pincode = $db->escapeString($fn->xss_clean($_POST['pincode']));
        $city = $db->escapeString($fn->xss_clean($_POST['city']));

        $error = array();

        if (empty($pincode)) {
            $error['pincode'] = " <span class='label label-danger'>Required!</span>";
        }
        if (empty($city)) {
            $error['city'] = " <span class='label label-danger'>Required!</span>";
        }

        if (!empty($pincode) && !empty($city) ) {
            $sql_query = "INSERT INTO pincodes (pincode, city, status)	VALUES('$pincode', '$city',1)";
            $db->sql($sql_query);
            $result = $db->getResult();
            if (!empty($result)) {
                $result = 0;
            } else {
                $result = 1;
            }
            if ($result == 1) {
                $error['add_pincode'] = "<section class='content-header'><span class='label label-success'>Pincode Added Successfully</span><h4><small><a  href='pincodes.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back to Pincodes</a></small></h4></section>";
            } else {
                $error['add_pincode'] = " <span class='label label-danger'>Failed</span>";
            }
        }
    } else {
        $error['add_pincode'] = "<section class='content-header'><span class='label label-danger'>You have no permission to create pincodes</span></section>";
    }
}
?>
<section class="content-header">
    <h1>Add Pincode <small><a href='pincodes.php'><i class='fa fa-angle-double-left'></i>&nbsp;&nbsp;&nbsp;Back</a></small></h1>

    <?php echo isset($error['add_pincode']) ? $error['add_pincode'] : ''; ?>
    <ol class="breadcrumb">
        <li><a href="home.php"><i class="fa fa-home"></i> Home</a></li>
    </ol>
    <hr />
</section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <?php if ($permissions['locations']['create'] == 0) { ?>
                <div class="alert alert-danger">You have no permission to create pincode</div>
            <?php } ?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Pincode</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form method="post" id="area_form" enctype="multipart/form-data">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="pincode">Pincode</label><?php echo isset($error['pincode']) ? $error['pincode'] : ''; ?>
                            <input type="number" class="form-control" name="pincode" id="pincode" required />
                        </div>
                        <div class="form-group">
                            <label for="city">City</label><?php echo isset($error['city']) ? $error['city'] : ''; ?>
                            <input type="text"  class="form-control" name="city" id="city" required />
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer">
                        <input type="submit" class="btn-primary btn" value="Add" name="btnAdd" />&nbsp;
                        <input type="reset" class="btn-danger btn" value="Clear" />
                    </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
</section>
<div class="separator"> </div>

<?php $db->disconnect(); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>
    $('#area_form').validate({
        debug: false,
        rules: {
            pincode: "required",
            city: "required"
        }
    });
</script>