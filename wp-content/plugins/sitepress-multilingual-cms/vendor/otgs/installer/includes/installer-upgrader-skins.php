<?php
  class Installer_Upgrader_Skins extends WP_Upgrader_Skin{
      
      function __construct($args = array()){
          $defaults = array( 'url' => '', 'nonce' => '', 'title' => '', 'context' => false );
          $this->options = wp_parse_args($args, $defaults);
      }
      
      function header(){
          
      }
      
      function footer(){
          
      }
      
      function error($error){
          $this->installer_error = $error;
      }
      
      function add_strings(){
          
      }
      
      function feedback($string){
          
      }
      
      function before(){
          
      }
      
      function after(){
          
      }
      
  }