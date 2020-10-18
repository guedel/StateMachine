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

  class AutomatonException extends \Exception
  {
  }

  /**
   * Représente un automate
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>
   */
  class Automaton
  {
    /**
     *
     * @var Places|Place|array
     */
    private $places = null;
    /**
     *
     * @var array|Transition
     */
    private $transitions = [];

    /**
     *
     * @var IAutomatonVisitor
     */
    private $stepVisitor;

    /**
     * @var callable
     */
    private $callbackCallPlace;
    /**
     * @var callable
     */
    private $callbackEnterPlace;
    /**
     * @var callable
     */
    private $callbackLeavePlace;
    /**
     * @var callable
     */
    private $callbackTest;

    /**
     * Conserve l'état de la dernière vérification de l'évolution de l'automate
     * @var type
     */
    private $lastCheck = null;

    /**
     *
     * @var int
     */
    private $activationMode;

    public function __construct($activationMode = ACTIVATE_ALL_STATES_BEFORE)
    {
      $this->places = new Places();
      $this->activationMode = $activationMode;
    }

    public function registerCallPlace(callable $fn = null): Automaton
    {
      $this->callbackCallPlace = $fn;
      return $this;
    }

    public function registerEnterPlace(callable $fn = null): Automaton
    {
      $this->callbackEnterPlace = $fn;
      return $this;
    }

    public function registerLeavePlace(callable $fn = null): Automaton
    {
      $this->callbackLeavePlace = $fn;
      return $this;
    }

    public function registerTestTransition(callable $fn = null): Automaton
    {
      $this->callbackTest = $fn;
      return $this;

    }

    public function registerVisitor(IAutomatonVisitor $visitor = null): Automaton
    {
      $this->stepVisitor = $visitor;
      return $this;
    }

    /**
     *
     * @param Transition $t
     */
    private function test(Transition $t)
    {
      if ($this->stepVisitor !== null) {
        $t->accept($this->stepVisitor);
      } elseif ($this->callbackTest) {
        $t->test($this->callbackTest);
      }
    }

    /**
     *
     * @param Place $p
     */
    private function enterPlace(Place $p)
    {
      if ($this->stepVisitor !== null) {
        $this->stepVisitor->visitEnterPlace($p);
      } elseif ($this->callbackEnterPlace !== null) {
        call_user_func($this->callbackEnterPlace, $p);
      }
    }

    /**
     *
     * @param Place $p
     */
    private function callPlace(Place $p)
    {
      if ($this->stepVisitor !== null) {
        $this->stepVisitor->visitCallPlace($p);
      } elseif ($this->callbackCallPlace !== null) {
        call_user_func($this->callbackCallPlace, $p);
      }
    }

    private function leavePlace(Place $p)
    {
      if ($this->stepVisitor !== null) {
        $this->stepVisitor->visitLeavePlace($p);
      } elseif ($this->callbackLeavePlace !== null) {
        call_user_func($this->callbackLeavePlace, $p);
      }
    }

    /**
     * Controle que l'automate a évolué depuis le dernier appel de step
     * @return bool
     */
    public function checkStep(): bool
    {
      $v = "";
      foreach ($this->places as $place) {
        $v .= (string)$place;
      }
      //$ret = md5($v);
      $ret = $v;
      if ($this->lastCheck !== null) {
        return $ret !== $this->lastCheck;
      }
      $this->lastCheck = $ret;
      return true;
    }

    /**
     * Réalise une étape de l'automate
     * @param IAutomatonVisitor $visitor
     */
    public function step()
    {
      // Vérifier que l'automate n'est pas bloqué sinon -> Exception
      // En bref que les états ont évolués
      // Evite certzines boucles infinies (pas les aller-retours)
      if (! $this->checkStep()) {
        // throw new AutomatonException("L'automate n'a pas évolé depuis le dernier appel");
      }
      echo "activables: ";
      foreach ($this->transitions as $t) {
        $t->deactivate();
        if ($t->isActivable($this->activationMode)) {
          echo $t->name, ",";

          $this->test($t);
        }
      }

      foreach ($this->places as $p) {
        $p->saveState();
      }

      // Activer les places en fonctions des transitions franchies
      echo "; franchissement: ";
      foreach ($this->transitions as $t) {
        if ($t->completed()) {
          echo $t->name, ",";
          if ($this->countEffectiveConflict($t) == 0) {
            foreach ($t->after() as $p) {
              if ($p->isActive()) {
                $this->callPlace($p);
              } else {
                $this->enterPlace($p);
              }
              $p->activate();
            }
            foreach($t->before() as $p) {
              // On ne désactive la place qui si elle ne fait pas partie
              // des successeurs de la transition
              if (! $t->after()->offsetExists($p->name)) {
                if ($p->isActive()) {
                  $this->leavePlace($p);
                }
                $p->activate(false);
              }
            }
          }
        }
      }
    }

    /**
     * Réinitialisation à l'état initial
     */
    public function reset()
    {
      foreach($this->places as $place) {
        $place->reset();
      }
    }

    public function getPlaces() : Places
    {
      return $this->places;
    }

    public function getTransitions(): array
    {
      return $this->transitions;
    }


    public function addPlace(string $name, bool $isInitial = false )
    {
      $place = Places::create($name, $isInitial);
      if (! isset($this->places[$name])) {
        $this->places->append($place);
      }
      return $this;
    }

    public function removePlace(string $name) {
      foreach ($this->transitions as $t) {
        if ($t instanceof Transition) {
          $t->before()->offsetUnset($name);
          $t->after()->offsetUnset($name);
        }
      }
      unset($this->places[$name]);
      return $this;
    }

    public function addTransition(string $name, int $priority = 0)
    {
      $t = new Transition($name, $priority);
      if (! isset($this->transitions[$name])) {
        $this->transitions[$name] = $t;
      } else {
        throw new AutomatonException("La transition existe déjà");
      }
      return $this;
    }

    /**
     * Ajoute un arc pré entre une transition et une place
     * @param string $placeName
     * @param string $transitionName
     */
    public function addArcPre(string $placeName, string $transitionName): Automaton
    {
      if (isset($this->places[$placeName])) {
        $place = $this->places[$placeName];
      } else {
        $place = $this->places->create($placeName);
        $this->places->append($place);
      }

      if (! isset($this->transitions[$transitionName])) {
        $tr = new Transition($transitionName);
        $this->transitions[$transitionName] = $tr;
      } else {
        $tr = $this->transitions[$transitionName];
      }
      $tr->before($place);
      return $this;
    }

    public function removeArcPre(string $placeName, string $transitionName)
    {
      if ( isset($this->transitions[$transitionName])) {
        $tr = $this->transitions[$transitionName];
        $tr->before()->offsetUnset($placeName);
      }
    }

    public function addArcPost(string $transitionName, string $placeName): Automaton
    {
      if (isset($this->places[$placeName])) {
        $place = $this->places[$placeName];
      } else {
        $place = $this->places->create($placeName);
        $this->places->append($place);
      }

      if (! isset($this->transitions[$transitionName])) {
        $tr = new Transition($transitionName);
        $this->transitions[$transitionName] = $tr;
      } else {
        $tr = $this->transitions[$transitionName];
      }
      $tr->after($place);
      return $this;
    }

    public function removeArcPost(string $transitionName, string $placeName)
    {
      if (key_exists($transitionName, $this->transitions)) {
        $tr = $this->transitions[$transitionName];
        $tr->after()->offsetUnset($placeName);
      }
    }

    public function removeTransition(string $name)
    {
      unset($this->transitions[$name]);
    }

    /**
     * Ajoute un arc complet de place à place
     * @param string $p1
     * @param string $t
     * @param string $p2
     */
    public function addArc(string $p1, string $t, string $p2)
    {
      $this->addArcPre($p1, $t);
      $this->addArcPost($t, $p2);
      return $this;
    }


    public function haveStructuralConflict(): bool
    {
      foreach ($this->transitions as $t1) {
        foreach ($this->transitions as $t2) {
          if ($t1->name != $t2->name) {
            if ($t1->structuralConflict($t2)) {
              return true;
            }
          }
        }
      }
      return false;
    }

    /**
     * Indique un conflit effectif
     * @return bool
     */
    public function haveEffectiveConflict(): bool
    {
      foreach ($this->transitions as $t1) {
        foreach ($this->transitions as $t2) {
          if ($t1->name != $t2->name) {
            if (Transition::effectiveConflict($t1, $t2)) {
              return true;
            }
          }
        }
      }
      return false;
    }

    private function countEffectiveConflict(Transition $trans)
    {
      $ret = 0;
      if ($trans->completed()) {
        foreach ($this->transitions as $t) {
          if ($trans->name != $t->name) {
            if ($trans->effectiveConflict($t)) {
              if ($t->priority > $trans->priority) {
                $t->deactivate();
              } elseif ($t->priority < $trans->priority) {
                $ret++;
              } elseif ($t->priority == $trans->priority) {
                // conflit insolvable
                throw new AutomatonException("Conflit effectif insolvable entre $t->name et $trans->name");
              }
            }
          }
        }
      }
      return $ret;
    }
  }
