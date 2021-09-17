<?php

include 'config/config.php';
if (empty($basedomain)) die ('config.php is needed');

//
// Functions
//

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

//
// Global variables
//

$updated = false;
$newsubdomain = true;
$errormsg = array();

//
// Sanitize INPUT
//

// $_REQUEST['action']
$action = 0;
if (isset($_REQUEST['action'])) {
  $action = intval($_REQUEST['action']);
}

// $_REQUEST['ip']
$ip = getIP();
if (isset($_REQUEST['ip'])) {
  $ip=filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP);
  if (!$ip) {
    $errormsg['ip'] = 'IP not valid.';
    $ip = getIP();
  }
}

// $_REQUEST['subdomain']
$subdomain = '';
if (isset($_REQUEST['subdomain'])) {
  $subdomain=filter_var($_REQUEST['subdomain'], FILTER_VALIDATE_REGEXP,
    array("options"=>array("regexp"=>"/^[0-9a-z-_]{4,}$/")));
  if (!$subdomain) {
    $errormsg['subdomain'] = 'Subdomain not valid.';
    $subdomain = '';
  }
}

$domain = $subdomain.'.'.$basedomain;

// $_REQUEST['code']
$code = '';
if (isset($_REQUEST['code'])) {
  $code=filter_var($_REQUEST['code'], FILTER_SANITIZE_STRING);
  if (!empty($password) && ($password != $code)) {
    $errormsg['code'] = 'Access-code not valid.';
  }
}

//
// Create or update
//
if ($action == 1 && count($errormsg) == 0) {
  $lines  = file('data/hosts');

  $result = '';
  foreach($lines as $line) {
    $line = trim($line);
    if (empty($line)) {
      continue;
    }
    if(stripos($line, "\t".$domain) !== false) {
      // $domain found
      $newsubdomain = false;
      if (stripos($line, $ip) !== false) {
        // $ip do not change
        $errormsg['not-changed'] = 'Your IP is the same of last request';
        break;
      } else {
        $result .= "$ip\t$domain\n";
        $updated = true;
      }
    } else {
      $result .= $line."\n";
    }
  }
  if ($updated || $newsubdomain) {
    if ($newsubdomain) {
      $result .= "$ip\t$domain\n";
    }
    file_put_contents('data/hosts', $result);
    $updated = true;
  }
}

if ($_REQUEST['format'] == "json") {
  header('Content-Type: application/json; charset=utf-8');
  $json = '{"domain":"'.$domain.'","ip":"'.$ip.'","code":"'.$code.'",';
  if (count($errormsg) == 0) {
    $json .= '"error":false';
  } else {
    $json .= '"error":true,"errormsg":';
    $json .= json_encode($errormsg);
  }
  $json .= "}";
  echo "$json";
} else {
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots"  content="noindex,nofollow">
    <title>MyDDNS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
  </head>
  <body>
  <section class="section">

<?php if ($updated) { ?>
<div class="notification is-success">
  <button class="delete"></button>
  The IP for <strong><?php echo $domain; ?></strong> was updated to <em><?php echo $ip; ?></em>
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
        Simple Dynamic DNS Web management self-hosting. Run over <strong>dnsmasq</strong>.
      </p>
    </div>
  </section>
  <section class="section is-medium">
    <div class="container">

<form action="/" method="post">
<input type="hidden" name="action" value="1">
<div class="columns">
<div class="column is-two-fifths">
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
    <input class="input is-medium" id="ip" name="ip" type="text" placeholder="IP" value="<?php echo $ip; ?>">
  </div>
</div>
</div>
<?php if (!empty($password)) { ?>
<div class="column">
<div class="field">
  <div class="control">
    <input class="input is-medium" id="code" name="code" type="password" placeholder="Access CODE" value="<?php echo $code; ?>">
  </div>
</div>
</div>
<?php } ?>
<div class="column">
<div class="field">
  <div class="buttons">
    <input class="button is-medium is-primary" type="submit" value="Update">
    <a id="checkdns" href="https://dnschecker.org/#A/<?php echo $domain; ?>/<?php echo $ip; ?>" class="button is-primary is-medium is-info" target="_blank">Check</a>
  </div>
</div>
</div>
</form>
    </div>
  </section>

<footer class="footer">
  <div class="content has-text-centered">
    <p>
      <strong>MyDDNS</strong> by <a href="https://github.com/joker-x">joker-x</a>. The source code is licensed
      <a href="https://github.com/joker-x/myddns/blob/main/LICENSE">GNU Affero General Public License v3.0</a>.
      Available in <a href="https://github.com/joker-x/myddns">Github</a>.
    </p>
  </div>
</footer>

<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous">
</script>

<script>
$('.notification .delete').on('click', function(e) {
  $(this).parent().fadeOut();
});
$('#checkdns').on ('click', function (e) {
  $(this).attr('href','https://dnschecker.org/#A/'+$('#subdomain').val()+'.<?php echo $basedomain; ?>/'+$('#ip').val());
});
</script>
  </body>
</html>
<?php
}
