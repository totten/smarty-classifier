<?php

namespace Civi\SmartyUp;

class Arrays {

  public static function groupBy(array $items, $callback): array {
    $result = [];

    foreach ($items as $item) {
      $group = $callback($item);
      $result[$group][] = $item;
    }

    return $result;
  }

}
