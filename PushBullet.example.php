<?php

require 'PushBullet.class.php';

try {
  #### AUTHENTICATION ####
  // Get your API key here: https://www.pushbullet.com/settings
  $p = new PushBullet('YOUR_API_KEY');


  #### LIST OF DEVICES ####
  #### These methods return arrays containing device definitions, or NULL when no devices found
  // Print an array containing all available devices (including other people's devices shared with you)
  print_r($p->getDevices());

  // Print an array with your own devices
  print_r($p->getMyDevices());

  // Print an array with devices shared with you
  print_r($p->getSharedDevices());


  #### PUSHING TO ONE DEVICE ####
  #### PushBullet::push() and its shorthand methods return true on success, or throw an exception on failure
  // Push to device 31337 a note with a title 'Hey!' and a body 'It works!'
  $p->push(31337, 'note', 'Hey!', 'It works!');
  $p->pushNote(31337, 'Hey!', 'It works!');

  // Push to device 31337 a Google Maps address with a title 'Google HQ' and an address '1600 Amphitheatre Parkway'
  $p->push(31337, 'address', 'Google HQ', '1600 Amphitheatre Parkway');
  $p->pushAddress(31337, 'Google HQ', '1600 Amphitheatre Parkway');

  // Push to device 31337 a to-do list with a title 'Shopping List' and items 'Milk' and 'Butter'
  $p->push(31337, 'list', 'Shopping List', array('Milk', 'Butter'));
  $p->pushList(31337, 'Shopping List', array('Milk', 'Butter'));

  // Push to device 31337 a link with a title 'ivkos at GitHub' and a URL 'https://github.com/ivkos'
  $p->push(31337, 'link', 'ivkos at GitHub', 'https://github.com/ivkos');
  $p->pushLink(31337, 'ivkos at GitHub', 'https://github.com/ivkos');


  #### PUSHING TO MORE THAN 1 DEVICE ####
  // Push to all available devices
  $p->pushNote('all', 'Some title', 'Some text');
  
  // Push to all of your own devices
  $p->pushList('my', 'Buy these', array('PHP for Dummies', 'New charger'));
  
  // Push to all devices shared with you
  $p->pushAddress('shared', 'Lets meet here', 'The Lake, Central Park, NY');
  
  // Push to multiple devices (defined by their IDs)
  // When pushing to multiple device IDs, some of them might fail. If so, an exception saying
  // which devices failed will be thrown. If a device ID isn't in the message, it means push is successful.
  $p->pushLink(array(1234, 1337, 2013), 'Check this out!', 'http://youtu.be/dQw4w9WgXcQ');
  
  // You can use the PushBullet:push() method likewise
  $p->push('all', 'note', 'Some title', 'Some text');
}
catch (PushBulletException $e) {
  // Exception handling
  die($e->getMessage());
}

?>
