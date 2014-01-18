<?php

require 'PushBullet.class.php';

try {
  #### AUTHENTICATION ####
  // Get your API key here: https://www.pushbullet.com/account
  $p = new PushBullet('YOUR_API_KEY');



  #### LIST OF DEVICES ####
  #### These methods return arrays containing device definitions, or NULL if there are no devices.
  #### Use them to get 'device_iden' which is a unique ID for every device and is used with push methods below.
  
  // Print an array containing all available devices (including other people's devices shared with you). 
  print_r($p->getDevices());

  // Print an array with your own devices
  print_r($p->getMyDevices());

  // Print an array with devices shared with you
  print_r($p->getSharedDevices());



  #### PUSHING TO A SINGLE DEVICE ####
  #### Methods return TRUE on success, or throw an exception on failure.
  
  // Push to device s2GBpJqaq9IY5nx a note with a title 'Hey!' and a body 'It works!'
  $p->pushNote('s2GBpJqaq9IY5nx', 'Hey!', 'It works!');

  // Push to device a91kkT2jIICD4JH a Google Maps address with a title 'Google HQ' and an address '1600 Amphitheatre Parkway'
  $p->pushAddress('a91kkT2jIICD4JH', 'Google HQ', '1600 Amphitheatre Parkway');

  // Push to device qVNRhnXxZzJ95zz a to-do list with a title 'Shopping List' and items 'Milk' and 'Butter'
  $p->pushList('qVNRhnXxZzJ95zz', 'Shopping List', array('Milk', 'Butter'));

  // Push to device gXVZDd2hLY6TOB1 a link with a title 'ivkos at GitHub' and a URL 'https://github.com/ivkos'
  $p->pushLink('gXVZDd2hLY6TOB1', 'ivkos at GitHub', 'https://github.com/ivkos');



  #### PUSHING TO MULTIPLE DEVICES ####
  
  // Push to all available devices
  $p->pushNote('all', 'Some title', 'Some text');
  
  // Push to all of your own devices
  $p->pushList('my', 'Buy these', array('PHP for Dummies', 'New charger'));
  
  // Push to all devices shared with you
  $p->pushAddress('shared', "Let's meet here", 'The Lake, Central Park, NY');
  
  // Push to multiple devices defined by their IDs
  // When pushing to multiple device IDs, some of them might fail. If so, an exception saying
  // which devices have failed will be thrown. If a device ID isn't in the message, it means push is successful.
  $p->pushLink(array('5ZUC8hKOqLU0Jv7', 'SwZLKGn6M0fe7tO', 'tc7JSzKJLGLcKII'), 'Check this out!', 'http://youtu.be/dQw4w9WgXcQ');
}
catch (PushBulletException $e) {
  // Exception handling
  die($e->getMessage());
}

?>
