<?php

  /*
   * Copyright (C) 2020 Proximit
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>.
   * @since 15/10/2020.
   *
   *
   */
  namespace guedel\StateMachine;

  /**
   * Description of State
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>
   */
  class Place
  {
    public $name;
    private $initial = false;
    private $state = false;
    private $prevState = false;
    private $repeat = null;
    private $count = 0;

    public function __construct(string $name, bool $initial = false)
    {
      $this->name = $name;
      $this->initial = $initial ? 1 : 0;
      $this->reset();
    }

    public function getName()
    {
      return $this->name;
    }

    public function saveState()
    {
      $this->prevState = $this->state;
    }

    public function isActive(): bool
    {
      return $this->state > 0;
    }

    public function reset()
    {
      $this->state = $this->initial;
      $this->prevState = 0;
    }

    public function activate($v = true)
    {
      $this->saveState();
      if ($v) {
        $this->state++;
      } else {
        $this->state = 0;
      }
    }

    public function __toString()
    {
      /*
      $ret = "Place: " . $this->name;
      if ($this->initial) {
        $ret .= ", Initial";
      }
      if ($this->state) {
        $ret .= ", actif";
      }
      if ($this->prevState) {
        $ret .= ", fut actif";
      }
      return $ret;
    }
    */
      if ($this->state) {
        return "[$this->name:$this->state]";
      }
      return $this->name;
    }
  }
