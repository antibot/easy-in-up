<?php
/*
Plugin Name: Easy In/Up
Plugin URI: http://adventurebit.com/
Description: Easy Registration/Authorization widget for wordpress. You can easily log in, register and password recovery with AJAX.
Author: Selikhov Dmitry
Version: 1.0
Author URI: http://adventurebit.com/
*/


/* short tags 

{BLOGNAME} - name of your blog
{BLOGLINK} - link to your blog
{TIME} - current time
{CODE} - special code of special link
{CODELINK} - special link to verification (confirmation registration / restoration password) 
{EMAIL} - your email

------------------------------------------------------------------------------*/
    
include_once 'modules/form.php';    
  
function is_md5($str) {
  return preg_match('#^[\w\d]{32}$#',$str);
}  
    
function files() {

  $styleUrl = plugins_url('css/style.css', __FILE__);
  $styleFile = WP_PLUGIN_DIR . '/EasyInUp/css/style.css';
  if(file_exists($styleFile)) {
    wp_register_style('inout_css', $styleUrl);
    wp_enqueue_style('inout_css');
  }
   
  $scriptUrl = plugins_url('js/script.js', __FILE__);
  $scriptFile = WP_PLUGIN_DIR . '/EasyInUp/js/script.js';
  if(file_exists($scriptFile)) {
    wp_register_script('inout_js', $scriptUrl); 
    wp_enqueue_script('inout_js');
  }
  
  $scriptUrl = plugins_url('js/json.js', __FILE__);
  $scriptFile = WP_PLUGIN_DIR . '/EasyInUp/js/json.js';
  if(file_exists($scriptFile)) {
    wp_register_script('json_js', $scriptUrl); 
    wp_enqueue_script('json_js');
  }
  
  $scriptUrl = plugins_url('js/effects.js', __FILE__);
  $scriptFile = WP_PLUGIN_DIR . '/EasyInUp/js/effects.js';
  if(file_exists($scriptFile)) {
    wp_register_script('effects_js', $scriptUrl); 
    wp_enqueue_script('effects_js');
  }

  ?>
  <script>
    var INOUT_PLUGIN_URL = '<?= plugins_url("EasyInUp/") ?>';
  </script>
  <?php

}
 
add_action('wp_footer', 'files'); 
 
global $confirmation;  
global $restoration; 
global $message; 

function inout()
{

}

function FORM_CONTENT() {  
    
  $data = new stdClass();
  $data->message = '';    
       
  if(isset($_GET['confirmation'])) {     
    $code = $_GET['confirmation'];
    
    if(is_md5($code)) {
    
      $option = get_option($code);
      
      if(!empty($option)) {
      
        delete_option($code);
      
        $data->message = 'Registration completed!';
        
        ob_start();
          AUTHORIZATION_CONTENT(); 
        $data->form = ob_get_clean(); 
          
        return $data;
      }
    }
  } 
      
  if(isset($_GET['restoration'])) {
    $code = $_GET['restoration'];
    
    if(is_md5($code)) {
    
      $option = get_option($code);
      
      if(!empty($option)) {
    
        $data = json_decode($option);
        $time = $data->time;  
    
        if(floor((time()-$time)/60)/60 > 48) {
          delete_option($code);
          $data->message = 'Your link is outdated!';
          
          ob_start();
            AUTHORIZATION_CONTENT(); 
          $data->form = ob_get_clean(); 
          
          return $data;
        }
    
        ob_start();
          RESTORATION_CONTENT(); 
        $data->form = ob_get_clean(); 
          
        return $data;
      }
    }
  }

  if(is_user_logged_in()) {
    ob_start();
      EXIT_CONTENT(); 
    $data->form = ob_get_clean();    
  } else {
    ob_start();
      AUTHORIZATION_CONTENT(); 
    $data->form = ob_get_clean();   
  } 
  
  return $data; 
}

/* Shortcode
------------------------------------------------------------------------------*/ 

function inout_shortcode($args) {
  echo FORM_CONTENT();   
}

add_shortcode('easy_in_up', 'inout_shortcode');

/* Widget
------------------------------------------------------------------------------*/ 
 
function widget_inout_content($args) {

  $options = get_option('inout');
  extract($args);
     
  $title = $options['title'];  
  
  $data = FORM_CONTENT();
     
  echo  $before_widget,
        $before_title,
        $title,
        $after_title,
        '<div class="inout_container">',
        '<div class="inout_screen"></div>',
        '<div class="inout_loading"></div>',
        '<div class="inout_content">',
        $data->form,
        '</div>',
        '<div class="inout_message">'.$data->message.'</div>',
        '</div>',
        $after_widget;
}

function widget_inout_control() {

  $options = get_option('inout', array(
  
    'title' => 'Easy Sign In/Up',
    'email' => get_option('admin_email'),
    'confirm' => 'on',
    'auth-redirect' => home_url(),
    'reg-redirect' => home_url(),
    'exit-redirect' => home_url(),
    'conf-reg-line' => '{BLOGNAME} - registration confirmation',
    'rest-pwd-line' => '{BLOGNAME} - password restoration',
    'conf-reg-text' => '
To confirm the registration on the {BLOGLINK} click on the link below.

{CODELINK}

If you did not ask for registration, please do not pay attention to this letter.
Best wishes, {BLOGLINK} administration.',

  'rest-pwd-text' => '
To change the password on the {BLOGLINK} click on the link below.

{CODELINK}

If you did not request a password change, please do not pay attention to this letter.

Best wishes, {BLOGLINK} administration.'

  ));
  
  if(isset($_POST['nonce'])) {
    $options['title'] = $_POST['title'];
    $options['email'] = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : get_option('admin_email');
    
    $options['confirm'] = $_POST['confirm'];
    
    $options['conf-reg-text'] = $_POST['conf-reg-text'];
    $options['rest-pwd-text'] = $_POST['rest-pwd-text'];
    
    $options['conf-reg-line'] = $_POST['conf-reg-line'];
    $options['rest-pwd-line'] = $_POST['rest-pwd-line'];

    $options['auth-redirect'] = $_POST['auth-redirect'];
    $options['reg-redirect'] = $_POST['reg-redirect'];
    $options['exit-redirect'] = $_POST['exit-redirect'];
  }  

  update_option('inout', $options);
?>

<div>
  <label for="title">Title:</label>
</div>
<input class="widefat" type="text" id="title" name="title" maxlength="30" value="<?= $options['title'] ?>" />

<p>
  <div>
    Email:
  </div>   
  <input class="widefat" type="text" name="email" maxlength="100" value="<?= $options['email'] ?>" /> 
</p>

<p>
  <div>
    Authorization redirect link:
  </div>   
  <input class="widefat" type="text" name="auth-redirect" maxlength="100" value="<?= $options['auth-redirect'] ?>" /> 
</p>

<p>
  <div>
    Registration redirect link:
  </div>   
  <input class="widefat" type="text" name="reg-redirect" maxlength="100" value="<?= $options['reg-redirect'] ?>" /> 
</p>

<p>
  <div>
    Exit redirect link:
  </div>   
  <input class="widefat" type="text" name="exit-redirect" maxlength="100" value="<?= $options['exit-redirect'] ?>" /> 
</p>

<p>       
  <input type="checkbox" name="confirm" <?= $options['confirm'] == 'on' ? 'checked' : '' ?> /> 
  Confirm registration?  
</p>

<p> 
  <div>
    Confirmation registration text message:
  </div>   
  Subject Line: <input type="text" style="width: 150px;" name="conf-reg-line" maxlength="256" value="<?= $options['conf-reg-line'] ?>" /> 
  <textarea class="widefat" name="conf-reg-text" rows="5"><?= $options['conf-reg-text'] ?></textarea>
</p>

<p>
  <div>
    Restoration password text message:
  </div>   
  Subject Line: <input type="text" style="width: 150px;" name="rest-pwd-line" maxlength="256" value="<?= $options['rest-pwd-line'] ?>" />
  <textarea class="widefat" name="rest-pwd-text" rows="5"><?= $options['rest-pwd-text'] ?></textarea>
</p>

<p>
  <div>
    <i>Special tags:</i>
  </div>
  <span title="name of your blog">{BLOGNAME}</span>
  <span title="link to your blog">{BLOGLINK}</span>
  <span title="current time">{TIME}</span>
  <span title="special code">{CODE}</span>
  <span title="special link to verification">{CODELINK}</span>
  <span title="your email">{EMAIL}</span>
</p>

<input type="hidden" name="nonce" value="<?= wp_create_nonce('inout'); ?>" /> 
  
<?php
}
 
register_sidebar_widget(__('Easy Registration/Authorization'), 'widget_inout_content', 'widget_inout');   
register_widget_control(__('Easy Registration/Authorization'), 'widget_inout_control');   

add_action('plugins_loaded', 'inout');

?>