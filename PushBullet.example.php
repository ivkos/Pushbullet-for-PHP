<?php

require 'PushBullet.class.php';

try {
  $p = new PushBullet('YOUR_API_KEY');

  // Print an array containing all available devices (including other people's devices shared with you)
  print_r($p->getDevices());

  // Push to device 31337 a note with a title 'Hey!' and a body 'It works!'
  $p->push(31337, 'note', 'Hey!', 'It works!');

  // Push to device 31337 a Google Maps address with a title 'Google HQ' and an address '1600 Amphitheatre Parkway'
  $p->push(31337, 'address', 'Google HQ', '1600 Amphitheatre Parkway');

  // Push to device 31337 a to-do list with a title 'Shopping List' and items 'Milk' and 'Butter'
  $p->push(31337, 'list', 'Shopping List', array('Milk', 'Butter'));

  // Push to device 31337 a link with a title 'ivkos at GitHub' and a URL 'https://github.com/ivkos'
  $p->push(31337, 'link', 'ivkos at GitHub', 'https://github.com/ivkos');
}
catch (PushBulletException $e) {
  // Exception handling
  die($e->getMessage());
}

?>
