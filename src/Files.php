<?php

namespace Civi\SmartyUp;

class Files {

  public static function mkdir(string $dir): void {
    if (!file_exists($dir)) {
      mkdir($dir, 0777, TRUE);
    }
  }

  public static function remove(string $glob): int {
    $count = 0;
    $files = glob($glob);
    foreach ($files as $file) {
      unlink($file);
      $count++;
    }
    return $count;
  }

}
