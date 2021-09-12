<?php

include 'config/config.php';

if (empty($basedomain)) die ('config.php is needed');

function getIP(){
  if(!empty($_SERVER['HTTP_CLIENT_IP'])){
    //ip from share internet
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    //ip pass from proxy
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }else{
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}


$updated = false;
$errormsg = array();
$action = intval($_REQUEST['action']);

if (isset($_REQUEST['ip'])) {
  $ip=filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP);
  if (!$ip) {
    $errormsg['ip'] = 'IP not valid.';
    $ip = getIP();
  }
} else {
  $ip = getIP();
}

$subdomain = '';
if (isset($_REQUEST['subdomain'])) {
  $subdomain=filter_var($_REQUEST['subdomain'], FILTER_VALIDATE_REGEXP,
    array("options"=>array("regexp"=>"/^[0-9A-Za-z-_]{4,}$/")));
  if (!$subdomain) {
    $errormsg['subdomain'] = 'Subdomain not valid.';
    $subdomain = '';
  }
}

$domain = $subdomain.'.'.$basedomain;

//if (isset($_REQUEST['subdomain']) && !empty($_REQUEST['subdomain'])) {
//  $subdomain = $_REQUEST['subdomain'];
if ($action == 1 && count($errormsg) == 0) {
  $lines  = file('data/hosts');

  // delete subdomain in file
  $result = '';
  $deleted = false;
  foreach($lines as $line) {
    if(!empty(trim($line)) && (stripos($line, $domain) === false)) {
      $result .= $line;
    } else {
      $deleted = true;
    }
  }
  $result .= "$ip \t $domain\n";
  file_put_contents('data/hosts', $result);
  $updated = true;
}

//echo "$ip";
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyDDNS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
  </head>
  <body>
  <section class="section">

<?php if ($updated) { ?>
<div class="notification is-success">
  <button class="delete"></button>
  The IP for <?php echo $subdomain; ?> was updated to <?php echo $ip; ?>
</div>
<?php } ?>

<?php foreach ($errormsg as $field => $msg) { ?>
<div class="notification is-danger">
  <button class="delete"></button>
  <?php echo $msg; ?>
</div>
<?php } ?>

  </section>
  <section class="section">
    <div class="container">
      <h1 class="title">
        MyDDNS
      </h1>
      <p class="subtitle">
        Un servidor de DDNS para uso personal basado en <strong>dnsmasq</strong>
      </p>
    </div>
  </section>
  <section class="section">

<form action="/" method="post">
<input type="hidden" name="action" value="1">
<div class="columns">
<div class="column">
<div class="field has-addons">
  <p class="control">
    <input class="input is-medium" id="subdomain" name="subdomain" type="text" placeholder="subdomain" value="<?php echo $subdomain; ?>">
  </p>
  <p class="control">
    <a class="button is-static is-medium">
      .<?php echo $basedomain; ?>
    </a>
  </p>
</div>
</div>
<div class="column">
<div class="field">
  <div class="control">
    <input class="input is-medium" name="ip" type="text" value="<?php echo $ip; ?>">
  </div>
</div>
</div>
<div class="column">
<div class="field">
  <div class="buttons">
    <input class="button is-medium is-primary" type="submit" value="Update">
    <a id="checkdns" href="https://dnschecker.org/#A/<?php echo $domain; ?>/<?php echo $ip; ?>" class="button is-primary is-medium is-info" target="_blank">Check DNS</a>
  </div>
</div>
</div>
</form>

  </section>
<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous">
</script>

<script>
/*
document.addEventListener('DOMContentLoaded', () => {
  (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
    const $notification = $delete.parentNode;

    $delete.addEventListener('click', () => {
      $notification.parentNode.removeChild($notification);
    });
  });
});
*/
$('.notification .delete').on('click', function(e) {
  $(this).parent().fadeOut();
});
$('#checkdns').on ('click', function (e) {
  $(this).attr('href','https://dnschecker.org/#A/'+$('#subdomain').val()+'.<?php echo $basedomain; ?>/'+$('#ip').val());
});
</script>
  </body>
</html>
