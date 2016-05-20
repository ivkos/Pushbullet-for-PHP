Pushbullet for PHP
================
[![](https://img.shields.io/packagist/v/ivkos/pushbullet.svg?style=flat-square)](https://packagist.org/packages/ivkos/pushbullet)
[![](https://img.shields.io/packagist/dt/ivkos/pushbullet.svg?style=flat-square)](https://packagist.org/packages/ivkos/pushbullet)
[![](https://img.shields.io/packagist/l/ivkos/pushbullet.svg?style=flat-square)](LICENSE)

## Description
A PHP library for the **[Pushbullet](https://www.pushbullet.com)** API allowing you to send all supported push notification types, manage contacts, send SMS messages, create/delete channels, and manage channel subscriptions.

For more information, you can refer to these links:
* **Official website**: https://www.pushbullet.com
* **API reference**: https://docs.pushbullet.com
* **Blog**: http://blog.pushbullet.com
* **Apps**: https://www.pushbullet.com/apps

## Requirements
* PHP 5.4.0 or newer
* Composer
* cURL library for PHP
* Your Pushbullet access token: https://www.pushbullet.com/account

## Install
Create a `composer.json` file in your project root:

```json
{
    "require": {
        "ivkos/pushbullet": "3.*"
    }
}
```

Run `php composer.phar install` to download the library and its dependencies.

## Quick Documentation

Add this line to include Composer packages:

```php
<?php
require 'vendor/autoload.php';
```

Initialize Pushbullet with your API key:
```php
// Get your access token here: https://www.pushbullet.com/account
$pb = new Pushbullet\Pushbullet('YOUR_ACCESS_TOKEN');
```

If you use PHP for Windows it *may* be necessary to point cURL to a CA certificate bundle, or disable SSL certificate verification altogether:
```php
Pushbullet\Connection::setCurlCallback(function ($curl) {
	// Get a CA certificate bundle here:
    // https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
    curl_setopt($curl, CURLOPT_CAINFO, 'C:/path/to/ca-bundle.crt');

	// Not recommended! Makes communication vulnerable to MITM attacks:
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
});
```

### Devices
To list all active devices on your account:
```php
$pb->getDevices();
```
Returns an array of `Device` objects.

----------

You can target a particular device by using its `iden` or `nickname`:
```php
$pb->device("Galaxy S4")->getPhonebook();
```
Returns an array of `PhonebookEntry` objects with names and phone numbers.

To target all available devices for pushing:
```php
$pb->allDevices()->pushAddress("Google HQ", "1600 Amphitheatre Parkway");
```
This will send the address to all devices, and return a `Push` object.

### Push Notifications
You can use `push*` methods for `Contact`, `Channel` and `Device` objects. Every `push*` method returns a `Push` object. If an object cannot be pushed to, a `NotPushableException` will be thrown.

#### Note
Arguments:

 - Title
 - Body

```php
$pb->device("Galaxy S4")->pushNote("Hello world!", "Lorem ipsum...");
```

#### Link
Arguments:

- Title
- URL
- Body

```php
$pb->device("Galaxy S4")->pushLink("ivkos on GitHub", "https://github.com/ivkos", "Look at my page!");
```

#### Address
Arguments:

- Name - the place's name.
- Address - the place's address or a map search query.

```php
$pb->device("Galaxy S4")->pushAddress("Google HQ", "1600 Amphitheatre Parkway");
```

#### List
Arguments:

- Title
- Array of items in the list

```php
$pb->device("Galaxy S4")->pushList("Shopping List", [
	"Milk",
	"Butter",
	"Eggs"
]);
```

#### File
Arguments:

- File path
- MIME type (optional) - if `null`, MIME type will be magically guessed
- Title (optional)
- Body (optional)
- Alternative file name (optional) - push the file as if it had this file name

```php
$pb->device("Galaxy S4")->pushFile(
	"/home/ivkos/photos/20150314_092653.jpg",
	"image/jpeg",
	"Look at this photo!",
	"I think it's pretty cool",
	"coolphoto.jpg"
);
```

### SMS Messaging
You can send SMS messages only from supported devices. If an attempt is made to send an SMS message from a device doesn't support it, a `NoSmsException` will be thrown.

```php
$pb->device("Galaxy S4")->sendSms("+359123", "Hello there!");
```

Send an SMS text to all people in a device's phonebook:
```php
$people = $pb->device("Galaxy S4")->getPhonebook();

foreach ($people as $person) {
	$person->sendSms("Happy New Year!");
}
```

### Channel Management
Get a list of channel subscriptions:
```php
$pb->getChannelSubscriptions();
```
Returns an array of `Channel` objects with subscription information.

----------

To subscribe or unsubscribe from channels:
```php
$pb->channel("greatchannel")->subscribe();
$pb->channel("mehchannel")->unsubscribe();
```
Subscribing to a channel will return a `Channel` object with subscription information.

----------

Get a list of channels created by the current user:
```php
$pb->getMyChannels();
```
Returns an array of `Channel` objects.

### Contact Management
Contacts are people you can send push notification to. They are not to be confused with entries in a device's phonebook.

To list contacts on your account:
```php
$pb->getContacts();
```
Returns an array of `Contact` objects.

---

To create a contact:
```php
$pb->createContact("John Doe", "johndoe@example.com");
```
Returns a `Contact` object for the newly created contact.

---

You can target a particular contact by its email or name:
```php
$pb->contact("johndoe@example.com")->pushNote("Hey John!", "Where are you?");
```

To delete a contact:
```php
$pb->contact("Caroline")->delete();
```

To change a contact's name:
```php
$pb->contact("William")->changeName("Bill");
```
Returns a `Contact` object with an updated name.


----------


***For more detailed documentation, please refer to the PHPDoc in the source files.***
