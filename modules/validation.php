<?php

  require_once $_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php';  
  require_once ABSPATH . WPINC . '/ms-functions.php'; 
  
/* Form validator class 
------------------------------------------------------------------------------*/     
            
  class FormValidator {
    
    private $fields;
    private $messages;
    
    public $id;
  
    function __construct() {
      $this->messages = array();
      $this->fields = array();
    }
  
    public function messages() {
      return json_encode($this->messages);
    }
  
    public function success($message, $redirect = '') {
      return json_encode(array(
        'success' => 'success',
        'message' => $message,
        'redirect' => $redirect
      ));
    }
  
    private function rules($type, $val) {
      $status = new stdClass();
      switch($type) {
      
        case 'min': 
          $min = 3;
          $status->type = mb_strlen(trim($val))>=$min;
          $status->message = 'At least '.$min.' characters';
          return $status; 
        break;
        
        case 'max': 
          $max = 64;
          $status->type = mb_strlen(trim($val))<=$max;
          $status->message = 'Maximum of '.$max.' characters';
          return $status; 
        break;
      
        case 'email': 
          $status->type = filter_var($val, FILTER_VALIDATE_EMAIL);
          $status->message = 'Invalid email address';
          return $status; 
        break;
        
        case 'auth': 
          $login = sanitize_user(trim($val['login']));
          $password = trim($val['password']);
          
          $status->type = wp_login($login, $password);
          $status->message = 'Invalid login or password';
          return $status; 
        break;
        
        case 'equality': 
          $repeat = $val['repeat'];
          $password = $val['password'];
          
          $status->type = $repeat === $password;
          $status->message = 'Password fields are not equal!';
          return $status; 
        break;
        
        case 'unique': 
          $login = $val['login'];
          $email = $val['email'];
          
          $status->type = get_user_by('login', $login) === false && get_user_by('email', $email) === false;
          $status->message = 'User with this username or email already exists!';
          return $status; 
        break;
        
        case 'exists':
          $user = get_user_by('email', $val);
          
          if($user !== false) {
            $this->id = $user->ID; 
          }
          
          $status->type = $user !== false;
          $status->message = 'User does not exist';
          return $status; 
        break;
           
        default:
      }  
    }
  
    public function addValidation($type, $name, $val, $message = null) {
      
      $field = new stdClass();
      $field->type = $type; 
      $field->name = $name; 
      $field->val = $val; 
      $field->message = $message;  
      
      array_push($this->fields, $field);
    }
    
    public function validateForm() {
      foreach($this->fields as $field) {
        $status = $this->rules($field->type, $field->val);
        
        if($status->type != true) {
        
          if($field->message === null) {
            $message = $status->message;   
          } else {
            $message = $field->message; 
          }
          
          array_push($this->messages, array(
            'type' => $field->type,
            'name' => $field->name,
            'message' => $message
          )); 
          
        }
      }  
      
      return count($this->messages) === 0;
    }

  }

/* Validator actions control 
------------------------------------------------------------------------------*/   

  $validator = new FormValidator();

  try {
  
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
      switch($_POST['type']) {
      
        case 'restore':             
          $password = $_POST['password'];
          $repeat = $_POST['repeat'];
          
          $code = $_POST['code'];
          
          $validator->addValidation('min', 'password', $password);
          $validator->addValidation('max', 'password', $password);
          
          $validator->addValidation('equality', 'repeat', array(
            'password' => $password,
            'repeat' => $repeat
          ));
          
          $option = get_option($code);  
          $data = json_decode($option);
          
          $email = $data->email;
          
          $validator->addValidation('exists', 'password', $email);
          
          if($validator->validateForm() !== true) {
            echo $validator->messages();
          } else {
    
            if(is_md5($code)) {        
                    
              if(!empty($option)) { 
              
                delete_option($code);
                
                wp_set_password($password, $validator->id); 
                
                echo $validator->success('Password successfully changed!'); 
              }
            }
          }
          
        break;
        
        case 'reg':
          $login = $_POST['login'];
          $email = $_POST['email'];
          $password = $_POST['password'];
          $repeat = $_POST['repeat'];
          
          $validator->addValidation('min', 'login', $login);
          $validator->addValidation('max', 'login', $login);
          
          $validator->addValidation('min', 'password', $password);
          $validator->addValidation('max', 'password', $password);
          
          $validator->addValidation('email', 'email', $email);   
          $validator->addValidation('unique', 'login', array(
            'login' => $login,
            'email' => $email
          ));

          $validator->addValidation('equality', 'repeat', array(
            'password' => $password,
            'repeat' => $repeat
          ));
          
          if($validator->validateForm() !== true) {
            echo $validator->messages();
          } else {
          
            $creds = array();
            $creds['user_login'] = $login;
          	$creds['user_pass'] = $password;
          	$creds['user_email'] = $email;
            $creds['first_name'] = '';
            $creds['nickname'] = '';    
          	
            $id = wp_insert_user($creds);
            
            //mail -------------------------------------------------------------
            // FIXME
            
            $code = md5(uniqid().$id);
            
            $data = json_encode(array(
              'code' => $code,
              'time' => time(),
              'email' => $email
            )); 
              
            add_option($code, $data);  
            
            $options = get_option('inout');
            
            if($options['confirm'] === 'on') {
            
              $admin_email = $options['email'];
              $blog_name = get_bloginfo('name'); 
              $blog_url = get_bloginfo('url');
              
              $code_link = $blog_url.'?confirmation='.$code;
              
              $tags = array(
                '{BLOGNAME}' => $blog_name,
                '{BLOGLINK}' => '<a href="'.$blog_url.'" title="'.$blog_name.'">'.$blog_name.'</a>',
                '{TIME}' => current_time('mysql'),
                '{CODE}' => $code,
                '{CODELINK}' => '<a href="'.$code_link.'" title="'.$code_link.'">'.$code_link.'</a>',
                '{EMAIL}' => $admin_email
              );  
              
              $admin_subject = strtr($options['conf-reg-line'], $tags);     
              $admin_message = strtr($options['conf-reg-text'], $tags); 
              
              $headers = 'From: '.$blog_name.' '.$admin_email."\r\n\\";  
                     
              wp_mail($email, $admin_subject, $admin_message, $headers);
            }

            //------------------------------------------------------------------
            
            echo $validator->success('Successful registration!', inout_redirect('reg-redirect'));  
          }
        break;
        
        case 'auth':
          $login = $_POST['login'];
          $password = $_POST['password'];
          $rememberme = $_POST['rememberme'];
          
          $validator->addValidation('auth', 'login', array(
            'login' => $login,
            'password' => $password
          ));
          
          if($validator->validateForm() !== true) {
            echo $validator->messages();   
          } else {
          
            $creds = array();
          	$creds['user_login'] = $login;
          	$creds['user_password'] = $password;
          	$creds['remember'] = $rememberme === 'on' ? true : false;
          	
          	$user = wp_signon($creds, false);
          	
          	wp_set_current_user($user->ID);
          	
            echo $validator->success('Successful authorization!', inout_redirect('auth-redirect'));  
          } 
        break; 
        
        case 'forgot':
          $email = $_POST['email'];
          
          $validator->addValidation('email', 'email', $email);
          $validator->addValidation('exists', 'email', $email);
          
          if($validator->validateForm() !== true) {
            echo $validator->messages();
          } else {
            
            //mail -------------------------------------------------------------
            // FIXME
          
            $code = md5(uniqid().$validator->id);
            
            $data = json_encode(array(
              'code' => $code,
              'time' => mktime(),
              'email' => $email
            )); 
              
            add_option($code, $data);  
            
            $options = get_option('inout');
            
            if($options['confirm'] === 'on') {
      
              $admin_email = $options['email'];
              $blog_name = get_bloginfo('name'); 
              $blog_url = get_bloginfo('url');
              
              $code_link = $blog_url.'?restoration='.$code;
              
              $tags = array(
                '{BLOGNAME}' => $blog_name,
                '{BLOGLINK}' => '<a href="'.$blog_url.'" title="'.$blog_name.'">'.$blog_name.'</a>',
                '{TIME}' => current_time('mysql'),
                '{CODE}' => $code,
                '{CODELINK}' => '<a href="'.$code_link.'" title="'.$code_link.'">'.$code_link.'</a>',
                '{EMAIL}' => $admin_email
              ); 
              
              $admin_subject = strtr($options['rest-pwd-line'], $tags);     
              $admin_message = strtr($options['rest-pwd-text'], $tags); 
              
              $headers = 'From: '.$blog_name.' '.$admin_email."\r\n\\";   
                     
              wp_mail($email, $admin_subject, $admin_message, $headers);
            }
            
            //------------------------------------------------------------------
          
            echo $validator->success('Please visit your email address!');  
          }
        break; 
        
        default: 
          
      }
      return;
    } 
  
  } catch(Exception $e) {
    echo 'error';
  }

?>