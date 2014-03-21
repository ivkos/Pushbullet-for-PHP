<?php

class PushBulletException extends Exception {
  // Exception thrown by PushBullet
}

class PushBullet {
  public function __construct($secret) {
    // Check if cURL is loaded
    if (!function_exists('curl_init')) {
      throw new PushBulletException('cURL library is not loaded.');
    }

    $this->_apiKey = $secret;

    // Get all devices associated with the API key.
    // This is also a more reliable way of API key validation.
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::API_HOST . '/devices');
    curl_setopt($curl, CURLOPT_USERPWD, $this->_apiKey);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    
    if ($response === false) {
      throw new PushBulletException('cURL Error: ' . curl_error($curl));
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode != 200) {
      throw new PushBulletException('Unable to authenticate. HTTP Error ' . $this->_pushBulletErrors[$httpCode]);
    }

    // Check PHP version to determine whether a JSON big int workaround should be used
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
      $this->_allDevices = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
    } else {
      $this->_allDevices = json_decode(preg_replace('/\"([a-zA-Z0-9_]+)\":\s?(\d{14,})/', '"${1}":"${2}"', $response), true);
    }

    if ($this->_allDevices) {
      $this->_myDevices = $this->_allDevices['devices'];
      $this->_sharedDevices = $this->_allDevices['shared_devices'];
    } else {
      throw new PushBulletException('Unable to decode JSON response.');
    }
  }

  public function getDevices() {
    return $this->_allDevices;
  }

  public function getMyDevices() {
    return $this->_myDevices;
  }

  public function getSharedDevices() {
    return $this->_sharedDevices;
  }

  public function pushNote($devices, $title, $body) {
    return $this->_push($devices, 'note', $title, $body);
  }

  public function pushAddress($devices, $name, $address) {
    return $this->_push($devices, 'address', $name, $address);
  }

  public function pushList($devices, $title, $items) {
    return $this->_push($devices, 'list', $title, $items);
  }

  public function pushFile($devices, $fileName) {
    return $this->_push($devices, 'file', $fileName, NULL);
  }

  public function pushLink($devices, $title, $url) 
  {
    return $this->_push($devices, 'link', $title, $url);
  }


  const API_HOST = 'https://api.pushbullet.com/api';
  private $_apiKey;
  private $_allDevices;
  private $_myDevices;
  private $_sharedDevices;

  private $_pushBulletErrors = array(
    400 => '400 Bad Request. Missing a required parameter.',
    401 => '401 Unauthorized. No valid API key provided.',
    402 => '402 Request Failed.',
    403 => '403 Forbidden. The API key is not valid for that request.',
    404 => '404 Not Found. The requested item doesn\'t exist.',
    500 => '500 Internal Server Error.',
    502 => '502 Bad Gateway.',
    503 => '503 Service Unavailable.',
    504 => '504 Gateway Timeout.'
  );

  private function _buildCurlQuery($deviceId, $type, $primary, $secondary) {
    switch ($type) {
      case 'note':
        if (empty($primary) && !empty($secondary)) {
          // PushBullet doesn't set a placeholder title if it's not supplied, so we have to.
          $primary = 'Note';
        } else if (empty($primary) && empty($secondary)) {
          throw new PushBulletException('Note: No title and body supplied.');
        }

        $queryData = http_build_query(array(
          'device_iden' => $deviceId,
          'type' => 'note',
          'title' => $primary,
          'body' => $secondary
        ));
      break;

      case 'address':
        if (!$secondary) {
          throw new PushBulletException('Address: No address supplied.');
        }

        $queryData = http_build_query(array(
          'device_iden' => $deviceId,
          'type' => 'address',
          'name' => $primary,
          'address' => $secondary
        ));
      break;

      case 'list':
        if (empty($primary) && !empty($secondary)) {
          // PushBullet doesn't set a placeholder title if it's not supplied.
          $primary = 'List';
        }
        else if (empty($secondary)) {
          // PushBullet accepts absolutely empty to-do lists, but there's no point.
          throw new PushBulletException('List: No items supplied.');
        }

        $queryData = http_build_query(array(
          'device_iden' => $deviceId,
          'type' => 'list',
          'title' => $primary,
          'items' => $secondary
        ));
        
        // Remove array keys in square brackets
        $queryData = preg_replace('/%5B[0-9]+%5D/i', '', $queryData);
      break;

      case 'file':
        $fullFilePath = realpath($primary);

        if (!is_readable($fullFilePath)) {
          throw new PushBulletException('File: File does not exist or is unreadable.');
        }

        if (filesize($fullFilePath) > 25*1024*1024) {
          throw new PushBulletException('File: File size exceeds 25 MB.');
        }

        $queryData = array(
          'device_iden' => $deviceId,
          'type' => 'file',
          'file' => '@' . $fullFilePath . ';filename=' . basename($fullFilePath)
        );
      break;

      case 'link':
        if (empty($secondary)) {
          throw new PushBulletException('Link: No URL supplied.');
        }
        $queryData = http_build_query(array(
          'device_iden' => $deviceId,
          'type' => 'link',
          'title' => $primary,
          'url' => $secondary
        ));
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::API_HOST . '/pushes');
    curl_setopt($curl, CURLOPT_USERPWD, $this->_apiKey);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $queryData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    
    if ($response === false) {
      throw new PushBulletException('cURL Error: ' . curl_error($curl));
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode >= 400) {
      throw new PushBulletException('Push failed for device ' . $deviceId . '. HTTP Error ' . $this->_pushBulletErrors[$httpCode]);
    }
    
    return true;
  }

  private function _push($pushTo, $pushType, $primary, $secondary) {
    if (empty($this->_allDevices)) {
      throw new PushBulletException('Push: No devices found.');
    }

    if (is_string($pushTo) && $pushTo != 'all' && $pushTo != 'my' && $pushTo != 'shared') {
      return $this->_buildCurlQuery($pushTo, $pushType, $primary, $secondary);
    } else if (is_array($pushTo)) {
      // Push to multiple devices in an array.

      // Check if the device ID is in the list of devices we have permissions to push to.
      $failedDevices = array();
      foreach ($pushTo as $device) {
        if ($this->_in_array($device, $this->_allDevices)) {
          $this->_buildCurlQuery($device, $pushType, $primary, $secondary);
        } else {
          $failedDevices[] = $device;
        }
      }

      if (!empty($failedDevices)) {
        throw new PushBulletException('Push failed for devices: ' . implode(', ', $failedDevices));
      }
    } else if ($pushTo == 'all' || $pushTo == 'my' || $pushTo == 'shared') {
      // Push to my devices, if any.
      if (($pushTo == 'all' || $pushTo == 'my') && !empty($this->_myDevices)) {
        foreach ($this->_myDevices as $myDevice) {
          $this->_buildCurlQuery($myDevice['iden'], $pushType, $primary, $secondary);
        }
      } else if ($pushTo == 'my' && empty($this->_myDevices)) {
        throw new PushBulletException('Push: No own devices found.');
      }

      // Push to shared devices, if any.
      if (($pushTo == 'all' || $pushTo == 'shared') && !empty($this->_sharedDevices)) {
        foreach ($this->_sharedDevices as $sharedDevice) {
          $this->_buildCurlQuery($sharedDevice['iden'], $pushType, $primary, $secondary);
        }
      } else if ($pushTo == 'shared' && empty($this->_sharedDevices)) {
        throw new PushBulletException('Push: No shared devices found.');
      }
    } else {
      throw new PushBulletException('Push: Invalid device definition (' . $pushTo . ').');
    }

    return true;
  }

  // Multidimensional in_array()
  private function _in_array($needle, $haystack, $strict = false) {
    if (is_array($haystack)) {
      foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->_in_array($needle, $item, $strict))) {
          return true;
        }
      }
    }

    return false;
  }
}
