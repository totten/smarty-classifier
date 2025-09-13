<?php

namespace Civi\SmartyUp;

class ActionPrompt {

  protected $prompt = NULL;

  protected $actions = [];

  /**
   * @param string $prompt
   * @return static
   */
  public static function create(string $prompt) {
    $a = new static();
    $a->prompt = $prompt;
    return $a;
  }

  /**
   * @param string $letter
   * @param string $title
   * @param callable $callback
   * @return $this
   */
  public function add(string $letter, string $title, callable $callback) {
    unset($this->actions[$letter]);
    $this->actions[$letter] = [
      'title' => $title,
      'callback' => $callback,
    ];
    return $this;
  }

  public function run($default = NULL) {
    $io = SmartyUp::io();
    $this->add('s', 'Skip', function() use ($io) {
      $io->text('<comment>Skipped</comment>');
    });
    $this->add('a', 'Abort', fn() => throw new \RuntimeException("User aborted"));
    $menu = array_combine(array_keys($this->actions), array_column($this->actions, 'title'));
    $choice = $io->choice($this->prompt, $menu, $default);
    $action = $this->actions[$choice];
    call_user_func($action['callback']);
    return $choice;
  }

}
