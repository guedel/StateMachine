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
   * Description of Places
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>
   */
  class Places implements \ArrayAccess, \Countable, \IteratorAggregate
  {
    /**
     *
     * @var type
     */
    private $places;

    public function __construct(Place ... $places)
    {
      $this->places = [];
      foreach ($places as $place) {
        $this->append($place);
      }
    }

    public function append(Place $place)
    {
      if (! key_exists($place->name, $this->places)) {
        $this->places[$place->name] = $place;
      }
    }

    public static function create(string $name, bool $isInitial = false): Place
    {
      $place = new Place($name, $isInitial);
      return $place;
    }

    public function count(): int
    {
      return count($this->places);
    }

    public function getIterator(): \Traversable
    {
      return new \ArrayIterator($this->places);
    }

    public function offsetExists($offset): bool
    {
      return isset($this->places[$offset]);
    }

    public function offsetGet($offset)
    {
      return $this->places[$offset];
    }

    public function offsetSet($offset, $value): void
    {
      $this->places[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
      unset($this->places[$offset]);
    }

    public function __toString()
    {
      $ret = '';
      $first = true;
      foreach ($this->places as $place) {
        if ($first) {
          $ret = (string)$place;
          $first = false;
        } else {
          $ret .= ", " . (string) $place;
        }
      }
      return $ret;
    }

  }
