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

  if (!defined('ACTIVATE_ALL_STATES_BEFORE')) {
    define('ACTIVATE_ALL_STATES_BEFORE', 0);
  }
  if (!defined('ACTIVATE_ONE_STATE_BEFORE')) {
    define('ACTIVATE_ONE_STATE_BEFORE', 1);
  }


  /**
   * Description of Transition
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>
   */
  class Transition
  {
    public $name;
    public $priority = 0;

    /**
     *
     * @var array|Place|Places
     */
    protected $prevPlaces = null;

    /**
     *
     * @var array|Place|Places
     */
    protected $nextPlaces = null;

    private $isTrue = false;

    public function __construct(string $name, $priority=0)
    {
      $this->name = $name;
      $this->priority = $priority;
      $this->prevPlaces = new Places();
      $this->nextPlaces = new Places();
    }

    /**
     * Détermine si la transition est activable en fonction des états précédents
     * @param int $activationMode Mode de franchissment de la transition
     * @return bool
     */
    public function isActivable($activationMode = ACTIVATE_ALL_STATES_BEFORE): bool
    {
      $ret = false;
      switch ($activationMode) {
        case ACTIVATE_ALL_STATES_BEFORE:
          // Standard: Toutes les places doivent être actives
          $first = true;
          foreach ($this->prevPlaces as $place) {
            if ($first) {
              $ret = $place->isActive();
              $first = false;
            } else {
              $ret = $ret && $place->isActive();
            }
          }
          break;

        case ACTIVATE_ONE_STATE_BEFORE:
          // Alternative: Au moins une place est active
          $first = true;
          foreach ($this->prevPlaces as $place) {
            if ($first) {
              $ret = $place->isActive();
              $first = false;
            } else {
              $ret = $ret || $place->isActive();
            }
          }
          break;
      }
      return $ret;
    }

    /**
     *
     * @param Place $states
     */
    public function before(Place ...$places): Places
    {
      foreach ($places as $place) {
        $this->prevPlaces->append($place);
      }
      return $this->prevPlaces;
    }

    /**
     *
     * @param Place $places
     * @return \Places
     */
    public function after(Place ...$places): Places
    {
      foreach ($places as $place) {
        $this->nextPlaces->append($place);
      }
      return $this->nextPlaces;
    }

    /**
     * Indique que la transition est active et franchissable
     * @return bool
     */
    public function completed() : bool
    {
      return $this->isTrue;
    }

    public function deactivate() {
      $this->isTrue = false;
    }

    /**
     * Teste le franchissement de la transition
     * @return bool
     */
    public function accept(IAutomatonVisitor $visitor)
    {
      $this->isTrue = $visitor->evalTransition($this);
      return $this->isTrue;
    }

    public function test(callable $callback)
    {
      $this->isTrue = call_user_func($callback, $this);
      return $this->isTrue;
    }

    /**
     * Indique qu'une transition est en conflit structurel avec une autre
     * Il y conflit structurel lorsque 2 transititions se partagent le même état
     * @param Transition $t1
     * @param Transition $t2
     * @return bool
     */
    public function structuralConflict(Transition $t2)
    {
      $ctTrue = 0;
      foreach ($this->prevPlaces as $place) {
        if ($place instanceof Place) {
          if (isset($t2->prevPlaces[$place->name])) {
            $ctTrue++;
          }
        }
      }
      return $ctTrue > 0;
    }

    /**
     * Indique ques les transitions ont un conflit effectif
     * Il y a conflit effectif lorsque 2 transitions vraies en même temps se partagent le même état actif
     * @param Transition $t1
     * @param Transition $t2
     * @return bool
     */
    public function effectiveConflict(Transition $t2)
    {
      $ctTrue = 0;
      if ($this->isTrue && $t2->isTrue) {
        foreach ($this->prevPlaces as $place) {
            if ($t2->prevPlaces->offsetExists($place->name)) {
              if ($place->isActive()) {
                $ctTrue++;
              }
            }
        }
      }
      return $ctTrue > 0;
    }
  }
