PushBullet for PHP
==================

## Description
Using this class, you can send push notifications to your Android devices running **PushBullet**.
There are different types of notifications (i.e. notes, Google Maps addresses, to-do lists and URLs).

For more information, you can refer to these links:
* PushBullet official website: https://www.pushbullet.com
* PushBullet for Android: https://play.google.com/store/apps/details?id=com.pushbullet.android
* API reference: https://www.pushbullet.com/api
* Blog: http://blog.pushbullet.com

## Requirements
* PHP 5
* cURL library for PHP
* Your PushBullet API key (get it here: https://www.pushbullet.com/settings)

## Examples
```php
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
```

## Future
I'll try to keep this PHP class up-to-date as the PushBullet API changes. If you find any bugs or have feature requests/ideas, please use the issue tracker.
