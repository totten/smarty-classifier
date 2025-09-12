<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Console\IOStack;

class SmartyUp {

  protected static $instances = [];

  /**
   * Get a list of input/output objects for pending commands.
   *
   * @return \Civi\SmartyUp\Console\IOStack
   */
  public static function ioStack(): IOStack {
    if (!isset(static::$instances[__FUNCTION__])) {
      static::$instances[__FUNCTION__] = new IOStack();
    }
    return static::$instances[__FUNCTION__];
  }

  /**
   * @return \Symfony\Component\Console\Application
   */
  public static function app() {
    return static::ioStack()->current('app');
  }

  /**
   * @return \Symfony\Component\Console\Input\InputInterface
   */
  public static function input() {
    return static::ioStack()->current('input');
  }

  /**
   * Get a reference to STDOUT (with support for highlighting) for current action.
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   */
  public static function output() {
    return static::ioStack()->current('output');
  }

  /**
   * Get a reference to STDERR (with support for highlighting) for current action .
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   */
  public static function errorOutput() {
    $out = static::output();
    return method_exists($out, 'getErrorOutput') ? $out->getErrorOutput() : $out;
  }

  /**
   * @return \Symfony\Component\Console\Style\StyleInterface
   */
  public static function io() {
    return static::ioStack()->current('io');
  }

}
