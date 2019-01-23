<?php

class FedoraTestHelpers {
  static function randomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }

  static function randomCharString($length) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }
}