<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



/* This function loads admin section scripts and styles*/
function fbjk_scripts($hook) {
    global $settingspage, $embedcodepage, $addembedcodepage, $sitewidenotificationpage;
    if(($hook!=$settingspage) && ($hook!=$embedcodepage) && ($hook!=$addembedcodepage) && ($hook!=$sitewidenotificationpage))//making sure they do not load on other pages
      return;
    
    wp_enqueue_style( 'fbjk-style', plugins_url('css/style.css', __FILE__) );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'fbjk-jscolor', plugins_url('js/jscolor.js', __FILE__) );
    wp_enqueue_script( 'fbjk-customjs', plugins_url('js/custom.js', __FILE__) );
}

/* This function loads public section scripts and styles*/
function fbjk_publicscripts() {   
    wp_enqueue_style( 'fbjk-style', plugins_url('css/style.css', __FILE__) );
    wp_enqueue_script( 'jquery' );
    if(get_option('fbjk-appid'))
    {
      wp_enqueue_script( 'fbjk-fbinit', plugins_url('js/fbinit.js', __FILE__) );
      $data = array( 
                    'appid' => get_option('fbjk-appid')
                );
    	wp_localize_script( 'fbjk-fbinit', 'fbjk_fbinit_localize', $data );
    }
    if(get_option('fbjk-notificationstatus') && (get_option('fbjk-notificationstatus') == 'enabled'))
    {
      wp_enqueue_script( 'fbjk-notificationbar', plugins_url('js/notificationbar.js', __FILE__) );       
      $data = array( 
                    'msg' => get_option('fbjk-notificationmsg'),
                    'barbg' => get_option('fbjk-barbgcolor'),
                    'textcolor' => get_option('fbjk-msgtextcolor'),
                    'sticky' => get_option('fbjk-notificationsticky'),
                    'animated' => get_option('fbjk-notificationanimated'),
                    'link' => get_option('fbjk-notificationlink'),
                    'ajaxurl' => admin_url('admin-ajax.php') . '?action=fbjk_isLiveForNotification'
                );
    	wp_localize_script( 'fbjk-notificationbar', 'fbjk_notificationbar_localize', $data );
    }
}


/*this function displays relevant admin notice if the user access token is invalid or about to expire*/
function fbjk_admin_notice_useraccesstoken(){
  if(isset($_GET["page"]) && ($_GET["page"]=="fbjk-livevideo-autoembed")) //do not display notices on settings page itself
  {
    return;
  }
  if(get_option('fbjk-useraccesstoken'))
  {
    if(get_option('fbjk-isvalid'))
    {
      if(get_option('fbjk-isvalid') == "no")
      { 
      ?>
        <div class="notice notice-error is-dismissible">
          <p>Facebook Live Video Auto Embed: The saved user access token is no longer valid. 
            Please go to 
            <a href="<?php echo admin_url('admin.php') . '?page=fbjk-livevideo-autoembed'; ?>">Settings Page</a> 
            and login to your Facebook account.</p>
        </div>
      <?php      
      }
      else
      {
        if(get_option('fbjk-expiresindays') <= 7)
        { 
        ?>
          <div class="notice notice-warning is-dismissible">
            <p>Facebook Live Video Auto Embed: The saved user access token is about to expire in <?php echo get_option('fbjk-expiresindays') ?> days. 
              In order to keep things working smoothly, please go to 
              <a href="<?php echo admin_url('admin.php') . '?page=fbjk-livevideo-autoembed'; ?>">Settings Page</a> 
              and click the "Renew Access Token" button.</p>
          </div>
        <?php      
        }
      }
    }
  }
}

/* This function sets up admin section menu*/
function fbjk_setup_menu(){
        global $settingspage, $embedcodepage, $addembedcodepage, $sitewidenotificationpage;
        $settingspage = add_menu_page( 'Settings Facebook Live Video Auto Embed', 'FB Live Videos Auto Embed', 'manage_options', 'fbjk-livevideo-autoembed', 'fbjk_settings', 'dashicons-video-alt3' );
        add_submenu_page('fbjk-livevideo-autoembed', 'Settings Facebook Live Video Auto Embed', 'Settings', 'manage_options', 'fbjk-livevideo-autoembed', 'fbjk_settings');
        $embedcodepage = add_submenu_page('fbjk-livevideo-autoembed', 'Embed Codes List Facebook Live Video Auto Embed', 'Embed Codes List', 'manage_options', 'fbjk-livevideo-autoembed-embedcodes', 'fbjk_embedcodes');
        $addembedcodepage = add_submenu_page('fbjk-livevideo-autoembed', 'Embed Code Facebook Live Video Auto Embed', 'Create New Embed Code', 'manage_options', 'fbjk-livevideo-autoembed-embedcode-form', 'fbjk_embedcode_form');
        $sitewidenotificationpage = add_submenu_page('fbjk-livevideo-autoembed', 'Site Wide Notification Facebook Live Video Auto Embed', 'Site Wide Notification', 'manage_options', 'fbjk-livevideo-autoembed-sitewide-notification', 'fbjk_sitewide_notification');
        
}

/* CRON daily for checking status of the saved access token in case user does not go to settings page in a while */
function fbjk_fbcheckaccesstoken(){
  if(get_option('fbjk-appid') && get_option('fbjk-appsecret') && get_option('fbjk-useraccesstoken'))
  {
    $appid = get_option('fbjk-appid');
    $appsecret = get_option('fbjk-appsecret');
    $useraccesstoken = get_option('fbjk-useraccesstoken');
    
    $fb = new FBJKFacebook\Facebook([
        'app_id' => $appid,
        'app_secret' => $appsecret,
        'default_graph_version' => 'v2.10',
        ]);
    
    
    $tokenvalidityrequest = $fb->request('GET', '/debug_token?input_token='.$useraccesstoken.'&access_token='.$appid.'|'.$appsecret);

    try {
      $tokenvalidityresponse = $fb->getClient()->sendRequest($tokenvalidityrequest);
    } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
      return 'Error loading: ' . $e->getMessage();
    } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
      return 'Error loading: ' . $e->getMessage();
    }
    
    $tokenvalidityresponse = $tokenvalidityresponse->getDecodedBody();
    $tokenvalidityresponse = $tokenvalidityresponse["data"];
    if($tokenvalidityresponse["is_valid"])
    {
      $expires_at = $tokenvalidityresponse["expires_at"];
      $expiresinhrs = floor(($expires_at - time())/3600);
      $expiresindays = floor($expiresinhrs/24);
      update_option('fbjk-expiresindays', $expiresindays);
      update_option('fbjk-isvalid', 'yes');
    }
    else
    {
      update_option('fbjk-isvalid', 'no');
    }    
    
  }
}


/* This function displays settings page and handles settings form submission*/
function fbjk_settings(){
  echo '<div id="fbjk-settings" class="fbjk-page">';
  echo '<h1 class="h1fbjk">Settings - Facebook Live Video Auto Embed</h1>';
  $errors = '';
  echo '<div id="fbjk-fb-settings">';
  echo '<h2 class="h1fbjk">Facebook App Settings</h2>';
  $msg = '<div class="update-nag notice"><p>Please enter App ID and App Secret of your facebook app. '
      . 'If you do not have a facebook app, <a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#installation-guide">'
      . 'please follow installation guide</a> to create it in few easy steps.</p></div>';
  if(get_option('fbjk-appid') && get_option('fbjk-appsecret'))
  {
    $appid = get_option('fbjk-appid');
    $appsecret = get_option('fbjk-appsecret');
  }
  if(isset($_POST["submitted"]))
  {
    $appid = trim($_POST['appid']);
    $appsecret = trim($_POST['appsecret']);
    $errors = '';
    if(!isset($_POST['appid']) || empty($_POST['appid']))
    {
      $errors .= ' - Please enter App ID';
    }
    if(!isset($_POST['appsecret']) || empty($_POST['appsecret']))
    {
      $errors .= '<br /> - Please enter App Secret';
    }
  }
  if(empty($errors) && isset($appid) && isset($appsecret))
  {     
    $fb = new FBJKFacebook\Facebook([
        'app_id' => $appid,
        'app_secret' => $appsecret,
        'default_graph_version' => 'v2.10',
        ]);

    $request = $fb->request('GET', '/'.$appid.'?access_token='.$appid.'|'.$appsecret);

    try {
      $response = $fb->getClient()->sendRequest($request);
    } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
      $errors .= '<br />Unable to connect using provided App ID and App Secret: ' . $e->getMessage();        
    } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
      $errors .= '<br />Unable to connect using provided App ID and App Secret: ' . $e->getMessage();        
    }        
    
    if(empty($errors))
    {        
      update_option( 'fbjk-appid', $appid);
      update_option( 'fbjk-appsecret', $appsecret);
      $msg = '<div class="updated notice">You are connected to Facebook API using the following App ID and App Secret.</div>';            
    }
  }
  if(!empty($errors))
  {
      $errors = '<div class="error notice">'.$errors.'</div>';        
  }
  echo $msg;
  echo $errors;
  echo '<form method="post" action="'
      . admin_url('admin.php') . '?page=fbjk-livevideo-autoembed';  
  echo '">'      
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">App ID:</div> '
      . '<div class="fbjk-right"><input type="text" name="appid" id="field_appid" value="'
      . (isset($appid)?$appid:'')
      . '"/></div><div class="clear"></div></div>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">App Secret:</div> '
      . '<div class="fbjk-right"><input type="password" name="appsecret" id="field_appsecret" value="'
      . (isset($appsecret)?$appsecret:'')
      . '"/></div><div class="clear"></div></div>'
      . '<input type="hidden" name="submitted" value="yes"/>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left"></div> '      
      . '<div class="fbjk-right"><button class="button button-primary" type="submit">Submit</button>'
      . '</div><div class="clear"></div></div>'
      . '</form>'
      . '</div>';//fbjk-fb-settings
  
  
  echo '<div id="fbjk-fb-account">';
  echo '<h2 class="h1fbjk">Facebook Account</h2>';
  $acc_msg = '<div class="update-nag notice"><p>Please connect the plugin with your Facebook account by logging in using the following button. '
      . '</p></div>';
 
  $acc_errors = '';
  $fbloginbtnstatus = '';
  if(!empty($errors) || !isset($appid) || !isset($appsecret))
  {
    $acc_errors = "<br />Please enter App ID and App Secret of your Facebook app in the above form in order to be able to connect the plugin with your Facebook account/page/group.";
    $fbloginbtnstatus = ' disabled="disabled" ';
  }
  else
  {
    if(get_option('fbjk-useraccesstoken'))
    {
      $useraccesstoken = get_option('fbjk-useraccesstoken');
    }
    if(isset($_GET["fbloggedin"]) && $_GET["fbloggedin"])
    {
      
      $accessToken = $_REQUEST["accesstoken"];

      if (! isset($accessToken)) {
        $acc_errors .= '<br />No cookie set or no OAuth data could be obtained from cookie.';        
      }
      else
      {
        // Logged in
        
        $shortlivedaccesstoken = $accessToken;
        $longlivedtokenrequest = $fb->request('GET', '/oauth/access_token?grant_type=fb_exchange_token&client_id='
                                                      .$appid.'&client_secret='.$appsecret
                                                      .'&fb_exchange_token='.$shortlivedaccesstoken);

        try {
          $longlivedtokenresponse = $fb->getClient()->sendRequest($longlivedtokenrequest);
        } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
          $acc_errors .= '<br />Error loading: ' . $e->getMessage();
        } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
          $acc_errors .= '<br />Error loading: ' . $e->getMessage();
        }
        
        
        //die($longlivedtokenresponse->getBody());
        $useraccesstoken = $longlivedtokenresponse->getDecodedBody();
        $useraccesstoken = $useraccesstoken["access_token"];
        
        update_option('fbjk-useraccesstoken', $useraccesstoken);
        //echo '<h3>Access Token</h3>';
        //var_dump($accessToken->getValue());
        // $_SESSION['fb_access_token'] = (string) $accessToken;
      }     
      
    }
    if(isset($useraccesstoken))
    {
      $tokenvalidityrequest = $fb->request('GET', '/debug_token?input_token='.$useraccesstoken.'&access_token='.$appid.'|'.$appsecret);

      try {
        $tokenvalidityresponse = $fb->getClient()->sendRequest($tokenvalidityrequest);
      } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
        $acc_errors .= '<br />Error loading: ' . $e->getMessage();
      } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
        $acc_errors .= '<br />Error loading: ' . $e->getMessage();
      }
      
      if(!$acc_errors)
      {
        $tokenvalidityresponse = $tokenvalidityresponse->getDecodedBody();
        $tokenvalidityresponse = $tokenvalidityresponse["data"];
        if($tokenvalidityresponse["is_valid"])
        {
          $expires_at = $tokenvalidityresponse["expires_at"];
          $expiresinhrs = floor(($expires_at - time())/3600);
          $expiresindays = floor($expiresinhrs/24);
          update_option('fbjk-expiresindays', $expiresindays);
          update_option('fbjk-isvalid', 'yes');
          if($expiresindays <= 7)
          {
            $acc_warning = 'The saved access token is about to expire in '.$expiresindays.' days. In order to keep things working smoothly, please click the "Renew Access Token" button.';
          }
          try {
            // Returns a `Facebook\FacebookResponse` object
            $profileresponse = $fb->get('/me?fields=id,name', $useraccesstoken);
          } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
            $acc_errors .= '<br />Graph returned an error: ' . $e->getMessage();
          } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
            $acc_errors .= '<br />Facebook SDK returned an error: ' . $e->getMessage();
          }

          $fbjkuserprofile = $profileresponse->getGraphUser();
          
          try {
            // Returns a `Facebook\FacebookResponse` object
            $profilepic = $fb->get('/me/picture?type=small&redirect=false', $useraccesstoken);
          } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
            $acc_errors .= '<br />Graph returned an error: ' . $e->getMessage();
          } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
            $acc_errors .= '<br />Facebook SDK returned an error: ' . $e->getMessage();
          }
          
          $fbjkuserpic = $profilepic->getDecodedBody();
          $fbjkuserpic = $fbjkuserpic["data"];
        }
        else
        {
          update_option('fbjk-isvalid', 'no');
          $acc_errors .= '<br />The saved user access token is no longer valid.';          
        }
      }
      
      if($acc_errors)
      {
         /* $acc_errors .= '<br />Please connect the plugin with your Facebook account by logging in using the following button. '
                          . 'This is compulsary for using "My User Account" option in Embed Code form "Source" field and highly '
                          . 'recommended for other options.';*/
      }
    }
  }
        
    
  if(isset($useraccesstoken) && empty($acc_errors))
  {        
    //update_option( 'fbjk-appid', $appid);
    //update_option( 'fbjk-appsecret', $appsecret);
    $acc_msg = '<div class="updated notice">You have successfully connected your Facebook account with the plugin. You can now <a href="'. admin_url('admin.php') .'?page=fbjk-livevideo-autoembed-embedcode-form">create Embed Codes</a> to embed live videos of <b>your Facebook account/page/group</b> anywhere on your website.'
        . ' For assistance regarding usage of the plugin, please follow <a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#user-guide">the user guide</a>.</div>';            
  }
  
  if(!empty($acc_errors))
  {
      $acc_errors = '<div class="error notice">'.$acc_errors.'</div>';        
  }
  echo $acc_msg;
  echo $acc_errors;
  if(!empty($acc_warning))
  {
      echo '<div class="update-nag notice">'.$acc_warning.'</div>';        
  }
?>
  <div class="fbjk-fieldrow">
    <div class="fbjk-left">Facebook Account:</div>
    <div class="fbjk-right">
      <?php 
        if(isset($fbjkuserprofile))
        {
          echo '<div class="fbjk-userprofile"><img src="'.$fbjkuserpic["url"].'" width="50" /><br />'.$fbjkuserprofile['name']."</div>"; 
        }
      ?>
      <button id="fbjk-fblogin" <?php echo $fbloginbtnstatus; ?>>
        <?php 
          if(isset($expiresindays))
          {
            echo "Renew Access Token";
          }
          else
          {
            echo "Log In to Facebook";
          }
        ?> 
      </button>
      <br />
      <span class="fbjk-hint">
        <?php 
          if(isset($expiresindays))
          { 
            echo "The saved access token will expire in <b>".$expiresindays. " days</b>. In order to keep things working smoothly, please make sure to renew the access token before it gets expired.";
        ?>
            
        <?php
          }
        ?>
      </span>
    </div>
    <div class="clear"></div>      
  </div>
  

  <script>
    logInWithFacebook = function() {
      FB.login(function(response) {
        if (response.authResponse) {
          //console.log(response);
          //alert('You are logged in &amp; cookie set!');
            var userId =  response.authResponse.userID;

            // Or use FB.getAccessToken()
            var accessToken = response.authResponse.accessToken;

          
          window.location.replace("<?php echo admin_url('admin.php') . '?page=fbjk-livevideo-autoembed&fbloggedin=true'; ?>&accesstoken="+accessToken);
          // Now you can redirect the user or do an AJAX request to
          // a PHP script that grabs the signed request from the cookie.
        } else {
          //alert('Please login with your Facebook account and authorize.');
        }
      },{scope: 'user_videos,manage_pages'});
      return false;
    };
    window.fbAsyncInit = function() {
      FB.init({
        appId: '<?php echo $appid; ?>',
        cookie: true, // This is important, it's not enabled by default
        version: 'v2.10'
      });
    };

    (function(d, s, id){
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) {return;}
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/sdk.js";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
  </script>
<?php  
  echo '</div>'; //fbjk-fb-account
      
  echo '</div>';//fbjk-settings

}

/* This function displays Site Wide notification settings page and handles form submission*/
function fbjk_sitewide_notification(){
  echo '<div id="fbjk_sitewide_notification" class="fbjk-form fbjk-page">';
  echo '<h1 class="h1fbjk">Site Wide Top Notification Bar - Facebook Live Video Auto Embed</h1>';
  $data = array();
  $errors = '';
  if(get_option('fbjk-appid') && get_option('fbjk-appsecret'))
  {
    $appid = get_option('fbjk-appid');
    $appsecret = get_option('fbjk-appsecret');
    $fb = new FBJKFacebook\Facebook([
          'app_id' => $appid,
          'app_secret' => $appsecret,
          'default_graph_version' => 'v2.10',
          ]);
  }
  else
  {
    echo '<div class="error notice">Sorry. You must enter your Facebook App ID and App Secret at <a href="'.admin_url('admin.php').'?page=fbjk-livevideo-autoembed">the settings page</a> before configuring Site Wide Notification Bar.</div>';
    return;
  }
  
  if(!get_option('fbjk-useraccesstoken') || (get_option('fbjk-isvalid') != 'yes')) //if useraccesstoken is not set or not valid
  {
    echo '<div class="error notice">Sorry. You must login to your Facebook account at <a href="'.admin_url('admin.php').'?page=fbjk-livevideo-autoembed">the settings page</a> before configuring Site Wide Notification Bar.</div>';
    return;
  }
  
  if(!isset($_POST["submitted"]))
  {
    if(get_option('fbjk-notificationsource') && get_option('fbjk-notificationmsg') && get_option('fbjk-barbgcolor') 
        && get_option('fbjk-msgtextcolor') && get_option('fbjk-notificationlink') && get_option('fbjk-notificationstatus')
        && get_option('fbjk-notificationsticky') && get_option('fbjk-notificationanimated'))
    {
      $data['notificationsource'] = get_option('fbjk-notificationsource');
      if(get_option('fbjk-notificationpageusername'))
      {
        $data['notificationpageusername'] = get_option('fbjk-notificationpageusername');
      }
      if(get_option('fbjk-notificationgroupid'))
      {
        $data['notificationgroupid'] = get_option('fbjk-notificationgroupid');
      }
      $data['notificationmsg'] = get_option('fbjk-notificationmsg');
      $data['barbgcolor'] = get_option('fbjk-barbgcolor');
      $data['msgtextcolor'] = get_option('fbjk-msgtextcolor');
      $data['notificationlink'] = get_option('fbjk-notificationlink');
      $data['notificationstatus'] = get_option('fbjk-notificationstatus');
      $data['notificationsticky'] = get_option('fbjk-notificationsticky');
      $data['notificationanimated'] = get_option('fbjk-notificationanimated');
      if($data['notificationstatus'] == 'enabled')
      {
        if($data['notificationsource'] == 'me')
        {
          $source = 'your Facebook User Account';
        }
        elseif($data['notificationsource'] == 'myfbpage')
        {
          $source = 'page: '.$data['notificationpageusername'];
        }
        elseif($data['notificationsource'] == 'myfbgroup')
        {
          $source = 'group: '.$data['notificationgroupid'];
        }
        
        $msg = '<div class="updated notice">Site Wide Top Notification Bar is enabled for "'.$source
              .'".</div>';
      }
      else
      {
        $msg = '<div class="update-nag notice">Site Wide Top Notification Bar is currently disabled</div>';
      }
      
    }
    else
    {
      $msg = '<div class="update-nag notice">Please enter the following information to enable Site Wide Top Notification Bar. '
           . 'This bar will display throughout your website whenever the selected Source (your Facebook Account/Page/Group) goes live.</div>';
    }
  }  
  else
  {
    $errors = '';      
    $useraccesstoken = "";
    if(get_option('fbjk-useraccesstoken') && (get_option('fbjk-isvalid') == 'yes')) //if useraccesstoken is set and is valid
    {
      $useraccesstoken = get_option('fbjk-useraccesstoken');
      //$preferredaccesstoken = $useraccesstoken;
    }
    else
    {
      $errors .= " - No valid user access token. Please login to your Facebook account on the settings page of the plugin.";
    }
    if(!isset($_POST['notificationsource']) || empty($_POST['notificationsource']))
    {
      $errors .= '<br /> - Please select a source';
    }
    else
    {
      $data['notificationsource'] = $_POST['notificationsource'];
      if($_POST['notificationsource'] == 'myfbpage')
      {
        if(!isset($_POST['notificationpageusername']) || empty($_POST['notificationpageusername']))
        {
          $errors .= '<br /> - Please enter Facebook Page Username';
        }
        else
        {      
          $data['notificationpageusername'] = trim($_POST['notificationpageusername']);
          $data['notificationpageusername'] = str_replace('@', '', $data['notificationpageusername']);
          $request = $fb->request('GET', '/'.$data['notificationpageusername'].'?access_token='.$useraccesstoken);
          try {
            $response = $fb->getClient()->sendRequest($request);
          } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $errors .= '<br /> - Unable to get page data: ' . $e->getMessage();        
          } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $errors .= '<br /> - Unable to get page data: ' . $e->getMessage();        
          }  
        }
      }
      elseif($_POST['notificationsource'] == 'myfbgroup')
      {
        if(!isset($_POST['notificationgroupid']) || empty($_POST['notificationgroupid']))
        {
          $errors .= ' - Please enter Facebook Group ID';
        }
        else
        {      
          $data['notificationgroupid'] = trim($_POST['notificationgroupid']);
          $request = $fb->request('GET', '/'.$data['notificationgroupid'].'?access_token='.$useraccesstoken);
          try {
            $response = $fb->getClient()->sendRequest($request);
          } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $errors .= '<br /> - Unable to get group data: ' . $e->getMessage();        
          } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $errors .= '<br /> - Unable to get group data: ' . $e->getMessage();        
          }  
        }
      }
    }
    if(!isset($_POST['notificationmsg']) || empty($_POST['notificationmsg']))
    {
      $errors .= '<br /> - Please enter notification message';
    }
    else
    {
      $data['notificationmsg'] = $_POST['notificationmsg'];      
    }
    if(!isset($_POST['barbgcolor']) || empty($_POST['barbgcolor']))
    {
      $errors .= '<br /> - Please select notification bar background color';
    }
    else
    {
      $data['barbgcolor'] = $_POST['barbgcolor'];      
    }
    if(!isset($_POST['msgtextcolor']) || empty($_POST['msgtextcolor']))
    {
      $errors .= '<br /> - Please select notification message text color';
    }
    else
    {
      $data['msgtextcolor'] = $_POST['msgtextcolor'];      
    } 
    if(!isset($_POST['notificationsticky']) || empty($_POST['notificationsticky']))
    {
      $errors .= '<br /> - Please select if the notification will be sticky or not';
    }
    else
    {
      $data['notificationsticky'] = $_POST['notificationsticky'];
    }  
    if(!isset($_POST['notificationanimated']) || empty($_POST['notificationanimated']))
    {
      $errors .= '<br /> - Please select if the notification should have blinking effect or not';
    }
    else
    {
      $data['notificationanimated'] = $_POST['notificationanimated'];
    }  
    if(!isset($_POST['notificationlink']) || empty($_POST['notificationlink']))
    {
      $errors .= '<br /> - Please enter the URL notification bar should link to';
    }
    else
    {
      $data['notificationlink'] = $_POST['notificationlink'];      
    } 
    if(!isset($_POST['notificationstatus']) || empty($_POST['notificationstatus']))
    {
      $errors .= '<br /> - Please select notification status';
    }
    else
    {
      $data['notificationstatus'] = $_POST['notificationstatus'];
    }   
    

    if(!empty($errors))
    { 
      $data['notificationmsg'] = stripslashes_deep($data['notificationmsg']);
      $data['barbgcolor'] = stripslashes_deep($data['barbgcolor']);
      $data['msgtextcolor'] = stripslashes_deep($data['msgtextcolor']);
      $data['notificationlink'] = stripslashes_deep($data['notificationlink']);
      echo '<div class="fbjk-errors">'.$errors.'</div>';        
    }
    else
    {
      update_option( 'fbjk-notificationsource', $data['notificationsource']);
      if($data['notificationsource'] == 'myfbpage')
        update_option( 'fbjk-notificationpageusername', $data['notificationpageusername']);
      if($data['notificationsource'] == 'myfbgroup')
        update_option( 'fbjk-notificationgroupid', $data['notificationgroupid']);
      update_option( 'fbjk-notificationmsg', $data['notificationmsg']);
      update_option( 'fbjk-barbgcolor', $data['barbgcolor']);
      update_option( 'fbjk-msgtextcolor', $data['msgtextcolor']);
      update_option( 'fbjk-notificationsticky', $data['notificationsticky']);
      update_option( 'fbjk-notificationanimated', $data['notificationanimated']);
      update_option( 'fbjk-notificationlink', $data['notificationlink']);
      update_option( 'fbjk-notificationstatus', $data['notificationstatus']);
      
      if($data['notificationstatus'] == 'enabled')
      {
        if($data['notificationsource'] == 'me')
        {
          $source = 'your Facebook User Account';
        }
        elseif($data['notificationsource'] == 'myfbpage')
        {
          $source = 'page: '.$data['notificationpageusername'];
        }
        elseif($data['notificationsource'] == 'myfbgroup')
        {
          $source = 'group: '.$data['notificationgroupid'];
        }
        $msg = '<div class="updated notice">Site Wide Top Notification Bar is enabled for "'.$source
              .'".</div>';
        if(!wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ))
        {          
          wp_schedule_event( time(), '48seconds', 'fbjk_fbchecklive_cron_hook' );
        }
      }
      else
      {
        $msg = '<div class="update-nag notice">Site Wide Top Notification Bar is currently disabled</div>';
        if(wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ))
        {
          wp_unschedule_event( wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ), 'fbjk_fbchecklive_cron_hook' );
        } 
      }
    }
  }

  $enabledselected = '';
  $disabledselected = '';
  
  $myfbpageselected = '';
  $myfbgroupselected = '';
  $meselected = '';


  $pageusernamehidden = 'fbjk-hidden';
  $groupidhidden = 'fbjk-hidden';
  
  if(isset($data['notificationsource']))
  {
    if($data['notificationsource'] == 'myfbpage')
    {
        $myfbpageselected = ' selected="selected"';
        $myfbgroupselected = '';
        $meselected = '';
        $pageusernamehidden = '';
        $groupidhidden = 'fbjk-hidden';
    }
    elseif($data['notificationsource'] == 'myfbgroup')
    {
        $myfbpageselected = '';
        $myfbgroupselected = ' selected="selected"';
        $meselected = '';
        $pageusernamehidden = 'fbjk-hidden';
        $groupidhidden = '';
    }
    elseif($data['notificationsource'] == 'me')
    {
        $myfbpageselected = '';
        $myfbgroupselected = '';
        $meselected = ' selected="selected"';
        $pageusernamehidden = 'fbjk-hidden';
        $groupidhidden = 'fbjk-hidden';
    }
  }

  if((isset($data['notificationsticky'])) && ($data['notificationsticky'] == 'yes'))
  {
      $stickyselected = ' selected="selected"';
  }
  elseif((isset($data['notificationsticky'])) && ($data['notificationsticky'] == 'no'))
  {
      $stickynotselected = ' selected="selected"';
  }
  if((isset($data['notificationanimated'])) && ($data['notificationanimated'] == 'yes'))
  {
      $animatedselected = ' selected="selected"';
  }
  elseif((isset($data['notificationanimated'])) && ($data['notificationanimated'] == 'no'))
  {
      $animatednotselected = ' selected="selected"';
  }
  if((isset($data['notificationstatus'])) && ($data['notificationstatus'] == 'enabled'))
  {
      $enabledselected = ' selected="selected"';
  }
  elseif((isset($data['notificationstatus'])) && ($data['notificationstatus'] == 'disabled'))
  {
      $disabledselected = ' selected="selected"';
  }
  if(isset($msg))
  {
    echo $msg;
  }
  echo '<form method="post" action="'
      . admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-sitewide-notification">'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Source:<span class="fbjkreq">*</span></div> '       
      . '<div class="fbjk-right"><select name="notificationsource" id="field_notificationsource">'    
      . '<option value="">--- SELECT ---</option>'
      . '<option value="myfbpage"'.$myfbpageselected.'>A Facebook Page I own</option>'
      . '<option value="myfbgroup"'.$myfbgroupselected.'>A Facebook Group I am an admin of</option>'
      . '<option value="me"'.$meselected.'>My Facebook User Account</option>'
      . '</select></div><div class="clear"></div></div>'

      . '<div class="fbjk-fieldrow '.$groupidhidden.'"><div class="fbjk-left">Facebook Group ID:<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><input type="text" name="notificationgroupid" id="field_notificationgroupid" value="'
      . (isset($data['notificationgroupid'])?$data['notificationgroupid']:'')
      . '"/><br /> <span class="fbjk-hint">Enter ID of Facebook Group. '
      . '<a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#find-group-id">How to find ID of a Facebook Group?</a></span></div><div class="clear"></div></div>'
     
      . '<div class="fbjk-fieldrow '.$pageusernamehidden.'"><div class="fbjk-left">Facebook Page Username:<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><input type="text" name="notificationpageusername" id="field_notificationpageusername" value="'
      . (isset($data['notificationpageusername'])?$data['notificationpageusername']:'')
      . '"/><br /> <span class="fbjk-hint">Enter Username of your Facebook page. '
      . '<a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#find-page-username">How to find Facebook Page Username?</a></span></div><div class="clear"></div></div>'
      
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Notification Message:<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><textarea name="notificationmsg" id="field_notificationmsg" cols="44" rows="6">'
      . (isset($data['notificationmsg'])?$data['notificationmsg']:'')
      . '</textarea><br />'
      . '<span class="fbjk-hint">Text to be displayed in the notification bar when the Source is currently live.</span>'      
      . '</div><div class="clear"></div></div>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Notification Bar Background Color<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><input type="text" name="barbgcolor" id="field_barbgcolor" class="fbjkjscolor" value="'
      . (isset($data['barbgcolor'])?$data['barbgcolor']:'000000')
      . '"/><br />'
      . '<span class="fbjk-hint">Background color of the Top Notification Bar.</span>'        
      . '</div><div class="clear"></div></div>' 
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Notification Text Color<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><input type="text" name="msgtextcolor" id="field_msgtextcolor" class="fbjkjscolor" value="'
      . (isset($data['msgtextcolor'])?$data['msgtextcolor']:'FFFFFF')
      . '"/><br />'
      . '<span class="fbjk-hint">Text color of the Top Notification Bar.</span>'        
      . '</div><div class="clear"></div></div>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Sticky:<span class="fbjkreq">*</span></div> '       
      . '<div class="fbjk-right"><select name="notificationsticky" id="field_notificationsticky">'    
      . '<option value="">--- SELECT ---</option>'
      . '<option value="yes"'.$stickyselected.'>Yes</option>'
      . '<option value="no"'.$stickynotselected.'>No</option>'
      . '</select><br />'
      . '<span class="fbjk-hint">Select if the notification bar should stick to the top when user scrolls the page.</span>' 
      . '</div><div class="clear"></div></div>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Blinking Effect:<span class="fbjkreq">*</span></div> '       
      . '<div class="fbjk-right"><select name="notificationanimated" id="field_notificationanimated">'    
      . '<option value="">--- SELECT ---</option>'
      . '<option value="yes"'.$animatedselected.'>Yes</option>'
      . '<option value="no"'.$animatednotselected.'>No</option>'
      . '</select><br />'
      . '<span class="fbjk-hint">Select if the notification bar should have a blinking effect.</span>' 
      . '</div><div class="clear"></div></div>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Notification Link<span class="fbjkreq">*</span></div> '
      . '<div class="fbjk-right"><input type="text" name="notificationlink" id="field_notificationlink" value="'
      . (isset($data['notificationlink'])?$data['notificationlink']:'')
      . '"/><br />'
      . '<span class="fbjk-hint">The URL to which the notification bar should be linked. Usually this should be the URL of the page/post you have put your Embed Code on.</span>'        
      . '</div><div class="clear"></div></div>' 
      . '<div class="fbjk-fieldrow"><div class="fbjk-left">Status:<span class="fbjkreq">*</span></div> '       
      . '<div class="fbjk-right"><select name="notificationstatus" id="field_notificationstatus">'    
      . '<option value="">--- SELECT ---</option>'
      . '<option value="enabled"'.$enabledselected.'>Enabled</option>'
      . '<option value="disabled"'.$disabledselected.'>Disabled</option>'
      . '</select><br />'
      . '<span class="fbjk-hint">Enable/ Disable the Top Notification Bar.</span>'        
      . '</div><div class="clear"></div></div>'            
      . '<input type="hidden" name="submitted" value="yes"/>'
      . '<div class="fbjk-fieldrow"><div class="fbjk-left"></div> '
      . '<div class="fbjk-right"><button id="submit_notification_form" class="button button-primary" type="submit">Submit</button></div>'
      . '<div class="clear"></div></div></form>';
  echo '</div>';
}

/* This function takes a single configuration record as input and returns array of facebook videos*/
function fbjkloadvideos($fbjkconfig)
{
  if(get_option('fbjk-appid') && get_option('fbjk-appsecret'))
  {
    $appid = get_option('fbjk-appid');
    $appsecret = get_option('fbjk-appsecret');
    $fb = new FBJKFacebook\Facebook([
          'app_id' => $appid,
          'app_secret' => $appsecret,
          'default_graph_version' => 'v2.10',
          ]);
    //$appaccesstoken = $appid.'|'.$appsecret;
    //$preferredaccesstoken = $appaccesstoken;
    $useraccesstoken = "";
    if(get_option('fbjk-useraccesstoken') && (get_option('fbjk-isvalid') == 'yes')) //if useraccesstoken is set and is valid
    {
      $useraccesstoken = get_option('fbjk-useraccesstoken');
      //$preferredaccesstoken = $useraccesstoken;
    }
    else
    {
      return "No valid user access token.";
    }
  }
  else
  {
    return 'Unable to load video. Facebook App ID and/or App Secret missing.';
  }
  
  
  $videos = array();
  if(!isset($fbjkconfig->maxrecorded) || is_null($fbjkconfig->maxrecorded) || empty($fbjkconfig->maxrecorded))
    $fbjkconfig->maxrecorded = 1;  
  
  if($fbjkconfig->vidsource == 'me')
  {    
      $request = $fb->request('GET', '/me/videos/uploaded?fields=live_status,permalink_url,embeddable&access_token='.$useraccesstoken);
  }
  elseif($fbjkconfig->vidsource == 'myfbpage')
  {    
      $request = $fb->request('GET', '/'.$fbjkconfig->pageusername.'/videos?fields=live_status,permalink_url,embeddable&access_token='.$useraccesstoken);
  }
  elseif($fbjkconfig->vidsource == 'myfbgroup')
  {
      $request = $fb->request('GET', '/'.$fbjkconfig->groupid.'/videos?fields=live_status,permalink_url,embeddable&access_token='.$useraccesstoken);
  }
    
  
  

  try {
    $response = $fb->getClient()->sendRequest($request);
  } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
    return 'Error loading: ' . $e->getMessage();
  } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
    return 'Error loading: ' . $e->getMessage();
  }
  //echo var_dump($response);
 // $graphNode = $response->getGraphNode();
  $graphEdge = $response->getGraphEdge();   
  if($fbjkconfig->videotype == "live")
  {
    if(!is_null($graphEdge) && !empty($graphEdge))
    {
      foreach($graphEdge as $gnode)
      {       
        if(isset($gnode['live_status']))
        {
          if(strtolower($gnode['live_status']) == 'live')
          {
            $video = array();
            $video['id'] = $gnode['id'];
            $video['permalink_url'] = $gnode['permalink_url'];
            if(isset($gnode['description']))
            $video['description'] = $gnode['description'];
            $videos[] = $video;
          }
        }
      }
    }
  }
  else
  {
    $loopcount = 0;
    while((count($videos) < $fbjkconfig->maxrecorded) && ($loopcount < 3))
    {
      $loopcount++;
      if(!is_null($graphEdge) && !empty($graphEdge))
      {
        foreach($graphEdge as $gnode)
        { 
          if(!$gnode['embeddable']) // if not an embeddable video skip it
            continue;
          
          if(isset($gnode['live_status']))
          {
            if($fbjkconfig->videotype == "recorded")
            {
              if(strtolower($gnode['live_status']) == 'vod')
              {
                if( count($videos) < $fbjkconfig->maxrecorded )
                {
                  $video = array();
                  $video['id'] = $gnode['id'];
                  $video['permalink_url'] = $gnode['permalink_url'];
                  if(isset($gnode['description']))
                  $video['description'] = $gnode['description'];
                  $videos[] = $video;
                }
                else
                {
                  break 2;
                }
              }
            }
          }
          else
          {
            if($fbjkconfig->videotype == "uploaded")
            {
              if( count($videos) < $fbjkconfig->maxrecorded )
              {
                $video = array();
                $video['id'] = $gnode['id'];
                $video['permalink_url'] = $gnode['permalink_url'];
                if(isset($gnode['description']))
                $video['description'] = $gnode['description'];
                $videos[] = $video;    
              }
              else
              {
                break 2;
              }
            }
          }    
        }
        try
        {
        $graphEdge = $fb->next($graphEdge);
        }
        catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
          //return 'Error loading: ' . $e->getMessage();
        } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
         // return 'Error loading: ' . $e->getMessage();
        }
        
      }
      else
      {
        break;
      }
    }
  }
  return $videos;
}

/* This function handles fblivevideoembed shortcode. Gets configuration record by ID and returns HTML containing embedded videos*/
function fblivevideoembed_handler( $atts ) {
    static $firstexec = true;
    $a = shortcode_atts( array(
        'id' => '0'
    ), $atts );

    if($a['id'])
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "fbjkembedcodes";
      $result = $wpdb->get_results ( "SELECT * FROM ".$table_name." where id='".$a['id']."'");
      if(!count($result))
      {
        return "The shortcode ID used here no longer exists.";
      }
      foreach($result as $row)
      {
        $fbjkconfig = $row;
      } 
      
      if($fbjkconfig->usecache && (($fbjkconfig->videotype == "recorded") || ($fbjkconfig->videotype == "uploaded"))
         && !is_null($fbjkconfig->lastrequestresult) && !empty($fbjkconfig->lastrequestresult))
      {
        $currenttime = time();
        $cachetime = $fbjkconfig->lastrequest;
        $cacherefreshtime = $fbjkconfig->cacherefreshtime*60;
        
      }
      
      if($fbjkconfig->videotype == "live")
      {
        $currenttime = time();
        $cachetime = !empty($fbjkconfig->lastrequest) ? $fbjkconfig->lastrequest:0;
        $cacherefreshtime = 40;        
      }
      
      
      if(isset($currenttime) && (($currenttime - $cachetime) < $cacherefreshtime))
      {
        $videos = json_decode($fbjkconfig->lastrequestresult, true);
        $loadedfromcache = true;
      }
      else
      {
        $loadedfromcache = false;
        $response = fbjkloadvideos($fbjkconfig);
        if(is_array($response))
          $videos = $response;
        else
          return $response;
      }
      
      
      if(!$loadedfromcache)
      { 
        $where = array();
        $where['id'] = $fbjkconfig->id;
        $update = array();
        $update['lastrequest'] = time();
        $update['lastrequestresult'] = json_encode($videos);
        $updated = $wpdb->update($table_name, $update, $where);
      }
      
      //print_r($response);
      if(!count($videos))
      {
        if( $fbjkconfig->alternatebehaviour == "showmsg" )
        {
          return '<div class="fbjkalternatemsg">'.do_shortcode(stripslashes_deep($fbjkconfig->alternatemsg)).'</div>';          
        }
        else
        {
          return "";
        }
      }
      
      
      
        
      ob_start();
      if(isset($fbjkconfig->msg) && !empty($fbjkconfig->msg))
      {
        echo '<div class="fbjkmsg">'.do_shortcode(stripslashes_deep($fbjkconfig->msg)).'</div>';        
      }
      if($firstexec)
      {
      ?>
      <div id="fb-root"></div>      
      <?php    
       $firstexec = false;
      }
      foreach($videos as $video)
      {
      ?>  
        <div class="fb-video" data-href="https://www.facebook.com/facebook/videos/<?php echo $video['id'];?>" 
             data-width="<?php echo $fbjkconfig->width;?>" data-show-text="<?php echo ($fbjkconfig->showdesc)?'true':'false';?>" 
             data-allowfullscreen="<?php echo ($fbjkconfig->allowfullscreen)?'true':'false';?>">
          <blockquote cite="https://www.facebook.com<?php echo $video['permalink_url'];?>" class="fb-xfbml-parse-ignore">
            <a href="https://www.facebook.com<?php echo $video['permalink_url'];?>"><?php echo isset($video['description'])?$video['description']:'Video';?></a>            
          </blockquote>
        </div>      
      <?php 
      }
      ?>
      <?php
      return ob_get_clean();
    }
    else
    {
      return "Please enter embed code ID";
    }
}

/*Get refresh button for admin section*/
function fbjk_RefreshAdminButton($text = 'Refresh')
{
  return '<div class="fbjkrefresh"><a href="'.admin_url( "admin.php?page=".$_GET["page"] ).'">'.$text.'</a></div>';
}

/* This function displays Embed Codes list page*/
function fbjk_embedcodes(){
    global $wpdb;
    $table_name = $wpdb->prefix . "fbjkembedcodes";
    if(isset($_GET['embedcodeid']) && !empty($_GET['embedcodeid']) && isset($_GET['delete']))//if delete request
    {
      $wpdb->delete($table_name, array('id'=>addslashes($_GET['embedcodeid'])));
      echo '<div class="updated notice"><p>Embed Code deleted successfully</p></div>'.fbjk_RefreshAdminButton("Back");
      return;
    }
    echo '<div class="fbjk-main fbjk-page">';
    echo '<h1 class="h1fbjk">Embed Codes List - Facebook Live Video Auto Embed</h1><p>List of embed codes.</p>';
   
    $fbjkembedcodesListTable = new fbjkembedcodes_List_Table();
    $fbjkembedcodesListTable->prepare_items(); 
    $fbjkembedcodesListTable->display(); 
    echo '<a class="fbjkcreatenew" href="'.admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcode-form">Create New Embed Code</a>';
    echo '</div>';
}

/* This function takes $data and either creates a new record in wp_fbjkembedcodes table or updates an existing one*/
function fbjk_submittodb($data){
  global $wpdb;
  $table_name = $wpdb->prefix . "fbjkembedcodes"; 
  
  if($data['showdesc'] == 'yes')
    $data['showdesc'] = 1;
  else 
    $data['showdesc'] = 0;
  
  if(isset($data['allowfullscreen']) && ($data['allowfullscreen'] == 'yes'))
    $data['allowfullscreen'] = 1;
  else 
    $data['allowfullscreen'] = 0;
  
  if(isset($data['usecache']) && ($data['usecache'] == 'yes'))
    $data['usecache'] = 1;
  else 
    $data['usecache'] = 0;
  
   
  $msg= "";
  if(isset($_GET['embedcodeid']))
  {
    $where = array();
    $where['id'] = $_GET['embedcodeid'];
    $updated = $wpdb->update($table_name, $data, $where);
    if ( false === $updated ) {
        // There was an error.
      $msg = '<div class="error notice">There was an error updating the embedcode configuration.<br />'.$wpdb->last_error.'</div>'.fbjk_RefreshAdminButton("Back");
    } else {
        // No error. You can check updated to see how many rows were changed.
      $msg = '<div class="updated notice">Embed Code configuration updated successfully</div>'.fbjk_RefreshAdminButton("Back");
    }
  }
  else
  {
    $inserted = $wpdb->insert($table_name, $data);
    if(!$inserted)
    {
      $msg = '<div class="error notice">There was an error creating new Embed Code.<br />'.$wpdb->last_error.'</div>'.fbjk_RefreshAdminButton("Back");
    }
    else
    {
      $msg = '<div class="updated notice">New Embed Code created successfully</div>'.fbjk_RefreshAdminButton("Back");
    }
  }
  return $msg;
  
}

/* This function displays add/edit Embed code form and handles form submission*/
function fbjk_embedcode_form(){
    global $wpdb;   
    $table_name = $wpdb->prefix . "fbjkembedcodes";
    $data = array();
    if(get_option('fbjk-appid') && get_option('fbjk-appsecret'))
    {
      $appid = get_option('fbjk-appid');
      $appsecret = get_option('fbjk-appsecret');
      $fb = new FBJKFacebook\Facebook([
            'app_id' => $appid,
            'app_secret' => $appsecret,
            'default_graph_version' => 'v2.10',
            ]);
    }
    else
    {
      echo '<div class="error notice">Sorry. You must enter your Facebook App ID and App Secret at <a href="'.admin_url('admin.php').'?page=fbjk-livevideo-autoembed">the settings page</a> before configuring Embed Codes.</div>';
      return;
    }
    
    if(!get_option('fbjk-useraccesstoken') || (get_option('fbjk-isvalid') != 'yes')) //if useraccesstoken is not set or not valid
    {
      echo '<div class="error notice">Sorry. You must login to your Facebook account at <a href="'.admin_url('admin.php').'?page=fbjk-livevideo-autoembed">the settings page</a> before configuring Embed Codes.</div>';
      return;
    }
    
    echo '<div class="fbjk-form fbjk-page">';
    if(isset($_GET['embedcodeid']) && !empty($_GET['embedcodeid']))//if edit request
    {
      if(!isset($_POST["submitted"]))
      {        
        $result = $wpdb->get_results ( "SELECT * FROM ".$table_name." where id='".$_GET['embedcodeid']."'", 'ARRAY_A');
        foreach($result as $row)
        {
          $data = $row;
        }
        
        if($data['showdesc'])
        {
          $data['showdesc'] = 'yes';
        }
        else
        {
          $data['showdesc'] = 'no';
        }
        if(isset($data['msg']))
        {
          $data['msg'] = stripslashes_deep($data['msg']);
        }
        if(isset($data['alternatemsg']))
        {
          $data['alternatemsg'] = stripslashes_deep($data['alternatemsg']);
        }
                
        if(!is_null($data['allowfullscreen']))
        {
          if($data['allowfullscreen'])
          {
            $data['allowfullscreen'] = 'yes';
          }
          else
          {
            $data['allowfullscreen'] = 'no';
          }
        }
        else
        {
          unset($data['allowfullscreen']);
        }
        
        if(!is_null($data['usecache']))
        {
          if($data['usecache'])
          {
            $data['usecache'] = 'yes';
          }
          else
          {
            $data['usecache'] = 'no';
          }
        }
        else
        {
          unset($data['usecache']);
        }        
      }
      echo '<h1 class="h1fbjk">Edit Embed Code - Facebook Live Video Auto Embed</h1><p>Edit configuration of Embed Code.</p>';
    }
    else
    {
      echo '<h1 class="h1fbjk">Create New Embed Code - Facebook Live Video Auto Embed</h1><p>Configure a new Embed Code to automatically embed Currently Live/ Recently Recorded/ Recently Uploaded videos of your Facebook Account/Page/Group.</p>';
    }
    if(isset($_POST["submitted"]))
    {
      $errors = '';      
      $useraccesstoken = "";      
      if(get_option('fbjk-useraccesstoken') && (get_option('fbjk-isvalid') == 'yes')) //if useraccesstoken is set and is valid
      {
        $useraccesstoken = get_option('fbjk-useraccesstoken');
        //$preferredaccesstoken = $useraccesstoken;        
      }
      else
      {
        $errors .= " - No valid user access token. Please login to your Facebook account on the settings page of the plugin.";
      }
      if(isset($_POST["vidsource"]))
      {
        $data['vidsource'] = $_POST['vidsource'];
        if($_POST["vidsource"] == "myfbpage")
        {
          if(!isset($_POST['pageusername']) || empty($_POST['pageusername']))
          {
            $errors .= ' - Please enter Facebook Page Username';
          }
          else
          {
            $data['pageusername'] = trim($_POST['pageusername']);
            $data['pageusername'] = str_replace('@', '', $data['pageusername']);
                     
            $request = $fb->request('GET', '/'.$data['pageusername'].'/videos?access_token='.$useraccesstoken);
            try {
              $response = $fb->getClient()->sendRequest($request);
            } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
              // When Graph returns an error
              $errors .= '<br /> - Unable to get page data: ' . $e->getMessage();        
            } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
              // When validation fails or other local issues
              $errors .= '<br /> - Unable to get page data: ' . $e->getMessage();        
            }  
          }
        }
        elseif($_POST["vidsource"] == "myfbgroup")
        {
          if(!isset($_POST['groupid']) || empty($_POST['groupid']))
          {
            $errors .= ' - Please enter Facebook Group ID';
          }
          else
          {      
            $data['groupid'] = trim($_POST['groupid']);
            $grouprequest = $fb->request('GET', '/'.$data['groupid'].'/videos?access_token='.$useraccesstoken);
            try {
              $groupresponse = $fb->getClient()->sendRequest($grouprequest);
            } catch(FBJKFacebook\Exceptions\FacebookResponseException $e) {
              // When Graph returns an error
              $errors .= '<br /> - Unable to get group data: ' . $e->getMessage();        
            } catch(FBJKFacebook\Exceptions\FacebookSDKException $e) {
              // When validation fails or other local issues
              $errors .= '<br /> - Unable to get group data: ' . $e->getMessage();        
            }  
          }
        }
      }
      else
      {
        $errors .= '<br /> - Please select a Source';
      }
      
      if(!isset($_POST['videotype']) || empty($_POST['videotype']))
      {
        $errors .= '<br /> - Please select a Video Type';
      }
      else
      {
        $data['videotype'] = $_POST['videotype'];
        if(($data['videotype'] == "recorded") || ($data['videotype'] == "uploaded"))
        {          
          if(!isset($_POST['maxrecorded']) || empty($_POST['maxrecorded']))
          {
            $errors .= '<br /> - Please enter Maximum Videos';            
          }
          else
          {
            $data['maxrecorded'] = $_POST['maxrecorded'];
          }   
          if(!isset($_POST['usecache']) || empty($_POST['usecache']))
          {
            $errors .= '<br /> - Please select whether to use cache or not';            
          }
          else
          {            
            $data['usecache'] = $_POST["usecache"];
            if($data['usecache'] == "yes")
            {            
              if(!isset($_POST['cacherefreshtime']) || empty($_POST['cacherefreshtime']))
              {
                $errors .= '<br /> - Please enter Cache Refresh Time';
              }
              else
              {
                $data['cacherefreshtime'] = $_POST['cacherefreshtime'];
              } 
            }
          }           
          //$data['allowfullscreen'] = $_POST['allowfullscreen'];
        }
      }      
      if(!isset($_POST['width']) || empty($_POST['width']))
      {
        $errors .= '<br /> - Please enter Width of player';
      }
      else
      {
        $data['width'] = $_POST['width'];
      }   
      if(!isset($_POST['alternatebehaviour']) || empty($_POST['alternatebehaviour']))
      {
        $errors .= '<br /> - Please select an Alternate Behaviour';
      }
      else
      {
        $data['alternatebehaviour'] = $_POST['alternatebehaviour'];
        if($data['alternatebehaviour'] == "showmsg")
        {
          if(!isset($_POST['alternatemsg']) || empty($_POST['alternatemsg']))
          {
            $errors .= '<br /> - Please enter Alternate Message';
          }
          else
          {
            $data['alternatemsg'] = $_POST['alternatemsg'];
          }          
        }
      }
      $data['msg'] = $_POST['msg']; 
      $data['showdesc'] = $_POST['showdesc']; 
      $data['allowfullscreen'] = $_POST['allowfullscreen'];
      
      if(!empty($errors))
      {        
        $data['alternatemsg'] = stripslashes_deep($data['alternatemsg']);
        $data['msg'] = stripslashes_deep($data['msg']);
        echo '<div class="fbjk-errors">'.$errors.'</div>';        
      }
      else
      {         
        $successmsg = fbjk_submittodb($data); 
        echo $successmsg;
        return;        
      }
    }
      
    $liveselected = '';
    $recordedselected = '';
    $uploadedselected = '';
    $myfbpageselected = '';
    $myfbgroupselected = '';
    $meselected = '';
    $showmsgselected = '';
    $shownothingselected = '';
    $showdescyesselected = '';
    $showdescnoselected = '';
    $allowfullscreenyesselected = '';
    $allowfullscreennoselected = '';
    $usecacheyesselected = '';
    $usecachenoselected = '';
    
    
    $pageusernamehidden = 'fbjk-hidden';
    $groupidhidden = 'fbjk-hidden';
    $maxrecordedhidden = 'fbjk-hidden';
    $allowfullscreenhidden = 'fbjk-hidden';
    $alternatemsghidden = 'fbjk-hidden';
    $usecachehidden = 'fbjk-hidden';
    $cacherefreshtimehidden = 'fbjk-hidden';
    $msghintlivehidden = 'fbjk-hidden';
    $msghintrecordedhidden = 'fbjk-hidden';
    $msghintuploadedhidden = 'fbjk-hidden';
    $alternatehintlivehidden = 'fbjk-hidden';
    $alternatehintrecordedhidden = 'fbjk-hidden';
    $alternatehintuploadedhidden = 'fbjk-hidden';
    $alternatemsghintlivehidden = 'fbjk-hidden';
    $alternatemsghintrecordedhidden = 'fbjk-hidden';
    $alternatemsghintuploadedhidden = 'fbjk-hidden';
    
    
    if(isset($data['vidsource']))
    {
      if($data['vidsource'] == 'myfbpage')
      {
          $myfbpageselected = ' selected="selected"';
          $myfbgroupselected = '';
          $meselected = '';
          $pageusernamehidden = '';
          $groupidhidden = 'fbjk-hidden';
      }
      elseif($data['vidsource'] == 'myfbgroup')
      {
          $myfbpageselected = '';
          $myfbgroupselected = ' selected="selected"';
          $meselected = '';
          $pageusernamehidden = 'fbjk-hidden';
          $groupidhidden = '';
      }
      elseif($data['vidsource'] == 'me')
      {
          $myfbpageselected = '';
          $myfbgroupselected = '';
          $meselected = ' selected="selected"';
          $pageusernamehidden = 'fbjk-hidden';
          $groupidhidden = 'fbjk-hidden';
      }
    }
    
    if((isset($data['videotype'])) && ($data['videotype'] == 'live'))
    {
        $liveselected = ' selected="selected"';
        $msghintlivehidden = '';
        $alternatehintlivehidden = '';
        $alternatemsghintlivehidden = '';
        $allowfullscreenhidden = '';
    }
    elseif((isset($data['videotype'])) && ($data['videotype'] == 'recorded'))
    {
        $recordedselected = ' selected="selected"';
        $usecachehidden = '';
        $maxrecordedhidden = '';
        $msghintrecordedhidden = '';
        $alternatehintrecordedhidden = '';
        $alternatemsghintrecordedhidden = '';
        $allowfullscreenhidden = '';
    }
    elseif((isset($data['videotype'])) && ($data['videotype'] == 'uploaded'))
    {
        $uploadedselected = ' selected="selected"';
        $usecachehidden = '';
        $maxrecordedhidden = '';
        $msghintuploadedhidden = '';
        $alternatehintuploadedhidden = '';
        $alternatemsghintuploadedhidden = '';
        $allowfullscreenhidden = '';
    } 
    if((isset($data['alternatebehaviour'])) && ($data['alternatebehaviour'] == 'showmsg'))
    {
        $showmsgselected = ' selected="selected"';
        $alternatemsghidden = '';
    }
    elseif((isset($data['alternatebehaviour'])) && ($data['alternatebehaviour'] == 'shownothing'))
    {
        $shownothingselected = ' selected="selected"';
    }
    if((isset($data['showdesc'])) && ($data['showdesc'] == 'yes'))
    {
        $showdescyesselected = ' selected="selected"';
    }
    elseif((isset($data['showdesc'])) && ($data['showdesc'] == 'no'))
    {
        $showdescnoselected = ' selected="selected"';
    }      
    if((isset($data['allowfullscreen'])) && ($data['allowfullscreen'] == 'yes'))
    {
        $allowfullscreenyesselected = ' selected="selected"';
    }
    elseif((isset($data['allowfullscreen'])) && ($data['allowfullscreen'] == 'no'))
    {
        $allowfullscreennoselected = ' selected="selected"';
    } 
    if((isset($data['usecache'])) && ($data['usecache'] == 'yes'))
    {
        $usecacheyesselected = ' selected="selected"';
        $cacherefreshtimehidden = '';
    }
    elseif((isset($data['usecache'])) && ($data['usecache'] == 'no'))
    {
        $usecachenoselected = ' selected="selected"';
    } 
   
      
    
    echo '<form method="post" action="'
        . admin_url('admin.php') . '?page=fbjk-livevideo-autoembed-embedcode-form';
    echo (isset($_GET['embedcodeid']) && !empty($_GET['embedcodeid']))?'&embedcodeid='.$_GET['embedcodeid']:'';
    echo '">'
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Source:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="vidsource" id="field_vidsource">'    
        . '<option value="">--- SELECT ---</option>'
        . '<option value="myfbpage"'.$myfbpageselected.'>A Facebook Page I own</option>'
        . '<option value="myfbgroup"'.$myfbgroupselected.'>A Facebook Group I am an admin of</option>'
        . '<option value="me"'.$meselected.'>My Facebook User Account</option>'
        . '</select></div><div class="clear"></div></div>'
        
        . '<div class="fbjk-fieldrow '.$groupidhidden.'"><div class="fbjk-left">Facebook Group ID:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right"><input type="text" name="groupid" id="field_groupid" value="'
        . (isset($data['groupid'])?$data['groupid']:'')
        . '"/><br /> <span class="fbjk-hint">Enter ID of Facebook Group whose video(s) you want to embed. '
        . '<a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#find-group-id">How to find ID of a Facebook Group?</a></span></div><div class="clear"></div></div>'
        
        . '<div class="fbjk-fieldrow '.$pageusernamehidden.'"><div class="fbjk-left">Facebook Page Username:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right"><input type="text" name="pageusername" id="field_pageusername" value="'
        . (isset($data['pageusername'])?$data['pageusername']:'')
        . '"/><br /> <span class="fbjk-hint">Enter Username of Facebook Page whose video(s) you want to embed. '
        . '<a target="_blank" href="https://www.sleekalgo.com/facebook-live-video-auto-embed-wordpress-plugin/#find-page-username">How to find Facebook Page Username?</a></span></div><div class="clear"></div></div>'
        
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Video Type:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="videotype" id="field_videotype">'    
        . '<option value="">--- SELECT ---</option>'
        . '<option value="live"'.$liveselected.'>Currently Live Video</option>'
        . '<option value="recorded"'.$recordedselected.'>Recent Live Videos (Recorded)</option>'
        . '<option value="uploaded"'.$uploadedselected.'>Recent Uploaded Videos</option>'
        . '</select></div><div class="clear"></div></div>'
        . '<div class="fbjk-fieldrow forrecordedanduploaded '.$maxrecordedhidden.'"><div class="fbjk-left subfield">Maximum Videos:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right subfield"><input type="text" name="maxrecorded" id="field_maxrecorded" value="'
        . (isset($data['maxrecorded'])?$data['maxrecorded']:'3')
        . '"/><br /><span class="fbjk-hint">Maximum number of videos to be embedded.</span>'
        . '</div><div class="clear"></div></div>' 
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Video Player(s) Width:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right"><input type="text" name="width" id="field_width" value="'
        . (isset($data['width'])?$data['width']:'500')
        . '"/><br /><span class="fbjk-hint">Video Player(s) width in pixels.</span></div><div class="clear"></div></div>'        
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Top Message:</div> '
        . '<div class="fbjk-right"><textarea name="msg" id="field_msg" cols="44" rows="6">'
        . (isset($data['msg'])?$data['msg']:'')
        . '</textarea><br />'
        . '<span class="fbjk-hint forlive '.$msghintlivehidden.'">Custom message to be displayed above live video embed when the Source is going live. Leave empty for none.</span>'
        . '<span class="fbjk-hint forrecorded '.$msghintrecordedhidden.'">Custom message to be displayed above recorded live videos embeds. Leave empty for none.</span>'
        . '<span class="fbjk-hint foruploaded '.$msghintuploadedhidden.'">Custom message to be displayed above uploaded videos embeds. Leave empty for none.</span>'
        . '</div><div class="clear"></div></div>'
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Alternate Behaviour:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="alternatebehaviour" id="field_alternatebehaviour">'    
        . '<option value="">--- SELECT ---</option>'
        . '<option value="showmsg"'.$showmsgselected.'>Show Custom Message</option>'
        . '<option value="shownothing"'.$shownothingselected.'>Show Nothing</option>'
        . '</select><br />'
        . '<span class="fbjk-hint forlive '.$alternatehintlivehidden.'">Please select how the plugin should behave if the Source is not currently live.</span>'
        . '<span class="fbjk-hint forrecorded '.$alternatehintrecordedhidden.'">Please select how the plugin should behave if there is no recently recorded live video of the Source.</span>'
        . '<span class="fbjk-hint foruploaded '.$alternatehintuploadedhidden.'">Please select how the plugin should behave if there is no recently uploaded video of the Source.</span>'
        . '</div><div class="clear"></div></div>' 
        . '<div class="fbjk-fieldrow '.$alternatemsghidden.'"><div class="fbjk-left subfield">Alternate Message:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right subfield"><textarea name="alternatemsg" id="field_alternatemsg" cols="37" rows="6">'
        . (isset($data['alternatemsg'])?$data['alternatemsg']:'')
        . '</textarea><br />'
        . '<span class="fbjk-hint forlive '.$alternatemsghintlivehidden.'">Custom message to be displayed if the Source is not live at the moment.</span>'
        . '<span class="fbjk-hint forrecorded '.$alternatemsghintrecordedhidden.'">Custom message to be displayed if there is no recently recorded live video of the Source.</span>'
        . '<span class="fbjk-hint foruploaded '.$alternatemsghintuploadedhidden.'">Custom message to be displayed if there is no recently uploaded video of the Source.</span>'
        . '</div><div class="clear"></div></div>'  
        . '<div class="fbjk-fieldrow"><div class="fbjk-left">Show Full Post:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="showdesc" id="field_showdesc">'    
        . '<option value="yes"'.$showdescyesselected.'>Yes</option>'
        . '<option value="no"'.$showdescnoselected.'>No</option>'
        . '</select>' 
        . '<span class="fbjk-hint">Select whether to show video description and like/comment/share buttons in the video player or not. (Description which is usually entered at the time of going live on facebook.)</span>' 
        . '</div><div class="clear"></div></div>'         
        . '<div class="fbjk-fieldrow '.$allowfullscreenhidden.'"><div class="fbjk-left">Allow Full Screen:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="allowfullscreen" id="field_allowfullscreen">'    
        . '<option value="yes"'.$allowfullscreenyesselected.'>Yes</option>'
        . '<option value="no"'.$allowfullscreennoselected.'>No</option>'
        . '</select>'         
        . '</div><div class="clear"></div></div>' 
        . '<div class="fbjk-fieldrow forrecordedanduploaded '.$usecachehidden.'"><div class="fbjk-left">Use Cache:<span class="fbjkreq">*</span></div> '       
        . '<div class="fbjk-right"><select name="usecache" id="field_usecache">' 
        . '<option value="">--- SELECT ---</option>'
        . '<option value="yes"'.$usecacheyesselected.'>Yes</option>'
        . '<option value="no"'.$usecachenoselected.'>No</option>'
        . '</select><br />'        
        . '<span class="fbjk-hint">Recommended for recorded live videos and uploaded videos. Especially for users/ pages/ groups with large number of videos and followers.</span>'
        . '</div><div class="clear"></div></div>' 
        . '<div class="fbjk-fieldrow '.$cacherefreshtimehidden.'"><div class="fbjk-left subfield">Cache Refresh Time:<span class="fbjkreq">*</span></div> '
        . '<div class="fbjk-right subfield"><input type="text" name="cacherefreshtime" id="field_cacherefreshtime" value="'
        . (isset($data['cacherefreshtime'])?$data['cacherefreshtime']:'15')
        . '"/><br />'
        . '<span class="fbjk-hint">Cache refresh time in minutes. Leave as is if unsure.</span>'        
        . '</div><div class="clear"></div></div>'        
        . '<input type="hidden" name="submitted" value="yes"/>'
        . '<div class="fbjk-fieldrow"><div class="fbjk-left"></div> '
        . '<div class="fbjk-right"><button id="submit_embedcode_form" class="button button-primary" type="submit">Submit</button></div>'
        . '<div class="clear"></div></div></form>';
    echo '</div>';
}

/* add cron schedules*/
function fbjk_add_cron_schedules($schedules){
  if(!isset($schedules["48seconds"]))
  {
      $schedules["48seconds"] = array( 'interval' => 48,
                                       'display' => __('Once every 48 seconds'));
  }
  return $schedules;
}


/* Executes on deactivation for cleanup*/
function fbjk_deactivate(){
  if(wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ))
  {
    wp_unschedule_event( wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ), 'fbjk_fbchecklive_cron_hook' );
  } 
  if(wp_next_scheduled( 'fbjk_fbcheckaccesstoken_cron_hook' ))
  {
    wp_unschedule_event( wp_next_scheduled( 'fbjk_fbcheckaccesstoken_cron_hook' ), 'fbjk_fbcheckaccesstoken_cron_hook' );
  }  
}

/* cron for checking FB live video*/
function fbjk_fbchecklive(){
  if(get_option('fbjk-notificationsource') && get_option('fbjk-notificationstatus') && (get_option('fbjk-notificationstatus') == 'enabled'))
  {    
    $config = new stdClass();
    $vidsource = get_option('fbjk-notificationsource');
    $config->vidsource = $vidsource;
    if($vidsource == 'myfbpage')
    {
      $config->pageusername = get_option('fbjk-notificationpageusername');
    }
    elseif($vidsource == 'myfbgroup')
    {
      $config->groupid = get_option('fbjk-notificationgroupid');
    }
    $config->videotype = 'live';
    $videos = array();
    $fbreqresponse = fbjkloadvideos($config);
    if(is_array($fbreqresponse))
      $videos = $fbreqresponse;
    else
      return $fbreqresponse;
    update_option( 'fbjk-livechecklastexec', time()); 
    if(count($videos))
    {        
      update_option( 'fbjk-livestatus', 'live');         
      return "FB page is live now!";      
    }
    else
    {
      update_option( 'fbjk-livestatus', 'notlive');         
      return "FB page not live!";      
    }      
  }   
}
function fbjk_isLiveForNotification(){
  $livestatus = new stdClass();
  if(get_option('fbjk-notificationstatus') && (get_option('fbjk-notificationstatus') == 'enabled')
       && get_option('fbjk-livestatus') && (get_option('fbjk-livestatus') == 'live'))
    {
      $livestatus->livestatus = 'live';
    }
    else
    {
      $livestatus->livestatus = 'notlive';
    }
    echo json_encode($livestatus);
    die();
}

/* This function creates wp_fbjkembedcodes table at the time of activation*/
function fbjk_install () {
  
   if( is_plugin_active('fb-live-video-autoembed-free/fb-live-video-autoembed-free.php') ){
    add_action('update_option_active_plugins', 'deactivate_free_version');
    }
   global $wpdb;
   $table_name = $wpdb->prefix . "fbjkembedcodes"; 
   $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      vidsource tinytext,
      groupid tinytext,
      pageusername tinytext,
      videotype tinytext,
      width mediumint(9),
      showdesc tinyint(1),
      allowfullscreen tinyint(1),
      maxrecorded mediumint(9),
      msg text,
      alternatemsg text,
      alternatebehaviour tinytext,
      lastrequest int(15),
      lastrequestresult mediumtext,
      hideiflive tinyint(1),
      usecache tinyint(1),
      cacherefreshtime mediumint(9),
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    if(get_option( "fbjk_db_version") && (floatval(get_option( "fbjk_db_version")) < 2.4))
    {
      $updaterows = $wpdb->query("UPDATE $table_name SET vidsource='myfbpage' WHERE 1");      
      $updatecol = $wpdb->query("ALTER TABLE $table_name MODIFY pageusername tinytext null;");
      if(get_option('fbjk-notificationstatus') && (get_option('fbjk-notificationstatus') == 'enabled'))
      {
        update_option( 'fbjk-notificationsource', 'myfbpage');
      }
    }
    update_option( "fbjk_db_version", "2.5" );   
    if(get_option('fbjk-notificationstatus') && (get_option('fbjk-notificationstatus') == 'enabled'))
    {
      if(!wp_next_scheduled( 'fbjk_fbchecklive_cron_hook' ))
      {          
        wp_schedule_event( time(), '48seconds', 'fbjk_fbchecklive_cron_hook' );
      }
    }    
    if(!wp_next_scheduled( 'fbjk_fbcheckaccesstoken_cron_hook' ))
    {          
      wp_schedule_event( time(), 'daily', 'fbjk_fbcheckaccesstoken_cron_hook' );
    }
}
function deactivate_free_version(){
  deactivate_plugins('fb-live-video-autoembed-free/fb-live-video-autoembed-free.php');
}

//add_action('activated_plugin','fbjk_save_error');
function fbjk_save_error()
{
    file_put_contents(dirname(__file__).'/error_activation.txt', ob_get_contents());
}

