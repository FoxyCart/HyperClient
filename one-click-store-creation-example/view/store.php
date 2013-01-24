<?php
include 'includes/header.php';

// uncomment to show an example of a refresh token being used:
//$_SESSION['store_token_expires'] = 0;

// pull up the store
refreshTokenAsNeeded($_SESSION['store']->token);
$resp = $client->get($_SESSION['store']->{"_links"}->self->href, null, getHeaders($_SESSION['store']->token->access_token));
?>

<p>Welcome to your store!</p>
<div class="alert alert-info">
    <pre>
    <?php print_r($resp['data']); ?>
    </pre>
</div>

<?php
include 'includes/footer.php';
?>
