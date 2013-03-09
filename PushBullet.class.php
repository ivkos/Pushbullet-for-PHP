<?php

// Check if cURL is loaded
if (!function_exists('curl_init')) {
  throw new Exception('cURL library is not loaded.');
}

class PushBulletException extends Exception {
  // Exception thrown by PushBullet
}

class PushBullet {
  const HOST = 'https://www.pushbullet.com/api';
  private $apiKey;

  public function __construct($secret) {
    // Basic, but fast API key validation
    if (preg_match('/^[a-f0-9]{32}$/', $secret)) {
      $this->apiKey = $secret;
    } else {
      throw new PushBulletException('Malformed API key.');
    }
  }

  public function getDevices() {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::HOST . '/devices');
    curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($httpCode >= 400) {
      curl_close($curl);
      throw new PushBulletException('HTTP Error ' . $httpCode . ': ' . $response);
    } else if ($httpCode == 200) {
      curl_close($curl);
      return json_decode($response, true);
    }
  }

  public function push($deviceId, $type, $title, $data) {
    if (!is_int($deviceId) && $deviceId <= 0) {
      throw new PushBulletException('Invalid device ID.');
    }

    // Push types
    switch ($type) {
      case 'note':
        $pushData = http_build_query(array(
          'device_id' => $deviceId,
          'type' => 'note',
          'title' => $title,
          'body' => $data
        ));
        break;

      case 'address':
        $pushData = http_build_query(array(
          'device_id' => $deviceId,
          'type' => 'address',
          'name' => $title,
          'address' => $data
        ));
        break;

      case 'list':
        $pushData = http_build_query(array(
          'device_id' => $deviceId,
          'type' => 'list',
          'title' => $title,
          'items' => $data
        ));
        break;

      case 'file':
        throw new PushBulletException('Pushing files is not implemented.');
        break;

      case 'link':
        $pushData = http_build_query(array(
          'device_id' => $deviceId,
          'type' => 'link',
          'title' => $title,
          'url' => $data
        ));
        break;

      default:
        throw new PushBulletException('Invalid push type.');
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::HOST . '/pushes');
    curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $pushData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($httpCode >= 400) {
      curl_close($curl);
      throw new PushBulletException('HTTP Error ' . $httpCode . ': ' . $response);
    } else if ($httpCode == 200) {
      curl_close($curl);
      return json_decode($response, true);
    }
  }
}
