<?php
include 'includes/header.php';

$first_name = isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '';
$last_name = isset($_REQUEST['last_name']) ? $_REQUEST['last_name'] : '';
$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
$hostname = isset($_REQUEST['hostname']) ? $_REQUEST['hostname'] : '';

if ($error_message != '') {
    ?>
<div class="alert alert-error alert-block">
    Error:
    <?php print $error_message;?>
</div>
<?php
}
?>
<form class="form-horizontal" method="POST" action="">
    <h2>User Information (pulled from your session / db / etc)</h2>
    <div class="control-group">
        <div class="controls">
            <label for="">First Name</label> <input type="text"
                name="first_name" placeholder="First Name"
                class="input-xlarge" value="<?php print $first_name; ?>" />
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <label for="">Last Name</label> <input type="text"
                name="last_name" placeholder="Last Name"
                class="input-xlarge" value="<?php print $last_name; ?>" />
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <label for="">Email</label> <input type="text" name="email"
                placeholder="Email" class="input-xlarge"
                value="<?php print $email; ?>" />
        </div>
    </div>

    <h2>Store Information (pulled from installed domain / db /
        auto-generated / etc)</h2>
    <div class="control-group">
        <div class="controls">
            <label for="">Installed Hostname</label> <input
                type="text" name="hostname"
                placeholder="http://example.com" class="input-xlarge"
                value="<?php print $hostname; ?>" />
        </div>
    </div>

    <input type="hidden" name="action" value="create_store" />

    <button type="submit" class="btn btn-large btn-primary">
        Create your store on FoxyCart
    </button>

</form>
<?php
include 'includes/footer.php';
?>
