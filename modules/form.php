<?php 

  function inout_redirect($name) {
    $options = get_option('inout');
      
    $redirect = $options[$name];
    
    if(empty($redirect) || !is_string($redirect)) {
      $redirect = home_url();
    } 
    
    return $redirect;  
  } 
                                               
  function AUTHORIZATION_CONTENT() {
    ?>
      <div><big>Authorization</big></div>
      <form id="inout_auth" method="post">
        <label class="inout_login">
          <div>Login:</div>
          <input type="text" name="login" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_password">
          <div>Password:</div>
          <input type="password" name="password" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_rememberme">
          <input type="checkbox" name="rememberme" value="forever" />
          Remember Me
          <div class="inout_error"></div>
        </label>
        <input type="hidden" name="type" value="auth" />
        <div>
          <button class="inout_send">Send</button>
        </div>
        <a class="inout_reg_link">Registration</a>
        <a class="inout_forgot_link">Forgot password?</a>
      </form>
    <?php
  }
  
  function FORGOT_CONTENT() {
    ?>    
      <div><big>Forgot</big></div>
      <form id="inout_forgot" action="" method="post" >
        <label class="inout_email">
          <div>Email:</div>
          <input type="text" name="email" maxlength="64" />
          <div class="inout_error"></div>
        </label>  
        <input type="hidden" name="type" value="forgot" />
        <div>
          <button class="inout_send">Send</button>
        </div>
        <a class="inout_auth_link">Authorization</a>
      </form>
    <?php
  }
  
  function REGISTRATION_CONTENT() {
    ?>
      <div><big>Registration</big></div>
      <form id="inout_reg" method="post">
        <label class="inout_login">
          <div>Login:</div>
          <input type="text" name="login" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_email">
          <div>Email:</div>
          <input type="text" name="email" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_password">
          <div>Password:</div>
          <input type="password" name="password" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_repeat">
          <div>Repeat:</div>
          <input type="password" name="repeat" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <input type="hidden" name="type" value="reg" />
        <div>
          <button class="inout_send">Send</button>
        </div>
        <a class="inout_auth_link">Authorization</a>
      </form>
    <?php
  }

  function EXIT_CONTENT() {
    global $current_user;
    get_currentuserinfo()
    ?>               
      <div id="inout_exit">
        <div class="user">
          Hi, 
          <span>
            <?= $current_user->user_login ?>
          </span> 
        </div>
        <a class="inout_send" href="<?= wp_logout_url( inout_redirect('exit-redirect') ); ?>" >Exit</a>
      </div>
    <?php
  }
  
  function RESTORATION_CONTENT() {
    global $current_user;
    get_currentuserinfo()
    ?>           
      <div><big>Restoration password</big></div>    
      <form id="inout_restore" method="post">
        <label class="inout_password">
          <div>Password:</div>
          <input type="password" name="password" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <label class="inout_repeat">
          <div>Repeat:</div>
          <input type="password" name="repeat" maxlength="64" />
          <div class="inout_error"></div>
        </label>
        <input type="hidden" name="type" value="restore" />
        <input type="hidden" name="code" value="<?= $_GET['restoration'] ?>" />
        <div>
          <button class="inout_send">Send</button>
        </div>
        <a class="inout_auth_link">Authorization</a>
      </form>
    <?php
  }
  
  try {
  
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
      switch($_POST['what']) {
      
        case 'reg':
          echo REGISTRATION_CONTENT();
        break;
        
        case 'auth':
          echo AUTHORIZATION_CONTENT();
        break; 
        
        case 'forgot':
          echo FORGOT_CONTENT();
        break; 
        
        default: 
          
      }
      return;
    } 
  
  } catch(Exception $e) {
    echo 'error';
  }

?>