<?php

// CLIENT INFO

if(!isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
  $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
  $_SERVER['HTTPS']='on';
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
  $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}
function get_ip_address(){
    $old = $_SERVER['REMOTE_ADDR'];
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe
                if( filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false ){
                    if( strlen($ip) < 7 ){  // for extra check
                      return $old;
                    }else{
                      return $ip;
                    }
                }
            }
        }
    }
    return $old; // return old anyway then
}
$_SERVER['REMOTE_ADDR'] = get_ip_address(); 


// SECURITY

$realm = 'Restricted area';

//user => password
$users = array('admin' => 'admin');


// filter out all requests from blocked ips
if( strpos(file_get_contents('blockips.log'), $_SERVER['REMOTE_ADDR']) > -1 )
    die('Blocked! <br />Your IP: '.$_SERVER['REMOTE_ADDR']);

// check if user is still wanting to connect
if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
    die('The authentication stopped by you!');
}

// analyze the PHP_AUTH_DIGEST variable
if( !($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) || !isset($users[$data['username']]) ){
    register_bad_login($_SERVER['REMOTE_ADDR']);
    die('Wrong Credentials! <br />Your IP: '.$_SERVER['REMOTE_ADDR'].'<br />flush your cookies and or browser cache to try again..');
}

// generate the valid response
$A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

// Do the check for login
if( $data['response'] != $valid_response ){
    register_bad_login($_SERVER['REMOTE_ADDR']);
    die('Wrong Credentials! <br />Your IP: '.$_SERVER['REMOTE_ADDR'].'<br />flush your cookies and or browser cache to try again...');
}
// Go on freely

// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

function register_bad_login($current_ip){
   // register ip for bad login
    $ips = unserialize(file_get_contents('ip-basic-auth.log'));
    if( array_key_exists($current_ip, $ips) ){
      $ips[$current_ip] = $ips[$current_ip] + 1; // increase it
      if( $ips[$current_ip] >= 5 )
      {
        $blockedips = file_get_contents('blockips.log');
        $fb = fopen('blockips.log', 'w');
        fwrite($fb, $blockedips."\n".$current_ip);
        fclose($fb);
      }
    }else{
      $ips[$current_ip] = 1; // set it
    }
    $fh = fopen('ip-basic-auth.log', 'w');
    fwrite($fh, serialize($ips));
    fclose($fh);
}


/// APP ITSELF

  use Sabre\DAV;

  // The autoloader
  require 'vendor/autoload.php';

  // Now we're creating a whole bunch of objects
  $rootDirectory = new DAV\FS\Directory('files');

  // The server object is responsible for making sense out of the WebDAV protocol
  $server = new DAV\Server($rootDirectory);

  // If your server is not on your webroot, make sure the following line has the
  // correct information
  $server->setBaseUri('/');

  // The lock manager is reponsible for making sure users don't overwrite
  // each others changes.
  $lockBackend = new DAV\Locks\Backend\File('data/locks');
  $lockPlugin = new DAV\Locks\Plugin($lockBackend);
  $server->addPlugin($lockPlugin);

  // This ensures that we get a pretty index in the browser, but it is
  // optional.
  $server->addPlugin(new DAV\Browser\Plugin());

  // All we need to do now, is to fire up the server
  $server->exec();
