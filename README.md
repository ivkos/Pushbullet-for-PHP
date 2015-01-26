Pushbullet
==========

## Description
Using this class, you can send push notifications to Android, iOS, Chrome and Firefox running **Pushbullet**. The following types of push notifications can be sent:
* notes
* links
* addresses
* checklists
* files (smaller than 25 MB)

For more information, you can refer to these links:
* **Official website**: https://www.pushbullet.com
* **API reference**: https://docs.pushbullet.com
* **Blog**: http://blog.pushbullet.com
* **Android app**: https://play.google.com/store/apps/details?id=com.pushbullet.android
* **iOS app**: https://itunes.apple.com/us/app/pushbullet/id810352052
* **Chrome extension**: https://chrome.google.com/webstore/detail/pushbullet/chlffgpmiacpedhhbkiomidkjlcfhogd
* **Firefox extension**: https://addons.mozilla.org/en-US/firefox/addon/pushbullet/

## Requirements
* PHP >= 5.2.0
* cURL library for PHP
* Your Pushbullet API key (get it here: https://www.pushbullet.com/account)
* PHP for Windows users: if you are getting SSL certificate errors, see issue #25

## Install

Via Composer:

```json
{
    "require": {
        "ivkos/pushbullet": "2.*"
    }
}
```

## Examples

For more detailed usage information, consult the PHPDoc of the methods.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

// If you don't use Composer, include the class like so:
// require 'src/Pushbullet.php';

try {
  #### AUTHENTICATION ####
  // Get your API key here: https://www.pushbullet.com/account
  $p = new Pushbullet('YOUR_API_KEY');


  #### Get methods

  // Print the definitions for your own devices. Useful for getting the 'iden' for using with the push methods.
  print_r($p->getDevices());

  // Print the definitions for contacts/devices shared with you. Useful for getting 'iden', too.
  print_r($p->getContacts());

  // Print information about your Pushbullet account
  print_r($p->getUserInformation());
  
  // Print a list of sent push notifications, modified after 1400441645 unix time
  print_r($p->getPushHistory(1400441645));



  #### Push methods
  
  // Push to email me@example.com a note with a title 'Hey!' and a body 'It works!'
  $p->pushNote('me@example.com', 'Hey!', 'It works!');
  
  // Push to device s2GBpJqaq9IY5nx a note with a title 'Hey!' and a body 'It works!'
  $p->pushNote('s2GBpJqaq9IY5nx', 'Hey!', 'It works!');
  
  // Push to device gXVZDd2hLY6TOB1 a link with a title 'ivkos at GitHub', a URL 'https://github.com/ivkos' and body 'Pretty useful.'
  $p->pushLink('gXVZDd2hLY6TOB1', 'ivkos at GitHub', 'https://github.com/ivkos', 'Pretty useful.');

  // Push to device a91kkT2jIICD4JH a Google Maps address with a name 'Google HQ' and an address '1600 Amphitheatre Parkway'
  $p->pushAddress('a91kkT2jIICD4JH', 'Google HQ', '1600 Amphitheatre Parkway');

  // Push to device qVNRhnXxZzJ95zz a to-do list with a title 'Shopping List' and items 'Milk' and 'Butter'
  $p->pushList('qVNRhnXxZzJ95zz', 'Shopping List', array('Milk', 'Butter'));
  
  // Push to device 0PpyWzARDK0w6et the file '../pic.jpg' of MIME type image/jpeg
  // Method accepts absolute and relative paths.
  $p->pushFile('0PpyWzARDK0w6et', '../pic.jpg', 'image/jpeg');
  // If the MIME type argument is omitted, an attempt to guess it will be made.
  $p->pushFile('0PpyWzARDK0w6et', '../pic.jpg');
  
  
  #### Pushing to multiple devices
  
  // Push to all of your own devices, if you set the first argument to NULL or an empty string
  $p->pushNote(NULL, 'Some title', 'Some text');
  $p->pushNote('', 'Some title', 'Some text');
  
  
  
  #### Delete methods
  
  // Delete the push notification with the 'iden' specified
  $p->deletePush('a91kkT2jIICD4JH');
  
  // Delete the device with the 'iden' specified
  $p->deleteDevice('s2GBpJqaq9IY5nx');
  
  // Delete the contact with the 'iden' specified
  $p->deleteContact('0PpyWzARDK0w6et');
} catch (PushbulletException $e) {
  // Exception handling
  die($e->getMessage());
}

?>
```
