<?php

  /*
   * Copyright (C) 2020 Proximit
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>.
   * @since 17/10/2020.
   *
   *
   */

  require "bootstrap.php";

  use Guedel\Microtest\Assert;
  use Guedel\Microtest\UnitTest;

  class CsvReader implements IAutomatonVisitor
  {
    private $fieldSeparator = ",";
    private $currentChar;
    private $currentToken;
    private $values;
    private $isFinished;

    /**
     *
     * @var Automaton
     */
    private $sm;

    /**
     *
     * @var string la structure à analyse
     */
    private $source = "";
    private $position = 0;

    public function __construct()
    {
      $this->fieldSeparator = ",";
      $this->prepareStateMachine();
    }

    public function setSource($source)
    {
      $this->sm->reset();
      $this->source = $source;
      $this->position = 0;
      $this->advanceChar();
    }

    /**
     * Mise en place de la machine à états
     */
    private function prepareStateMachine()
    {
      $this->sm = new Automaton();
      $this->sm
          ->addPlace("P0", true)
          ->addPlace("Per")       // Erreur
          ->addPlace("Pf")        // Etat final
          ->addTransition("T5", 1)
          ->addTransition("T9", 1)
          ->addTransition("T10", 1)
          ->addTransition("T12", 2)
          ->addTransition("T13", 1)
          ->addTransition("T14", 1)
          ->addTransition("T15", 1)
          ->addTransition("T16", 2)
          ->addTransition("T17", 2)
          ->addArc("P0", "T1", "P0")
          ->addArc("P2", "T2", "P0")
          ->addArc("P5", "T3", "P0")
          ->addArc("P0", "T4", "P1")
          ->addArc("P1", "T5", "P1")
          ->addArc("P1", "T6", "P2")
          ->addArc("P2", "T7", "P3")
          ->addArc("P3", "T8", "P1")
          ->addArc("P2", "T9", "P4")
          ->addArc("P0", "T10", "P4")
          ->addArc("P4", "T11", "P4")
          ->addArc("P4", "T12", "Pf")
          ->addArc("P0", "T13", "P5")
          ->addArc("P5", "T14", "P5")
          ->addArc("P5", "T15", "P4")
          ->addArc("P2", "T16", "Per")
          ->addArc("P5", "T17", "Per")
          ->registerVisitor($this)
      ;
    }

    public function getLine(): array
    {
      $this->values = [];
      $this->isFinished = false;
      $this->resetToken();
      $this->sm->reset();

      $count = 0;
      while (!$this->isFinished) {
        echo $this->sm->getPlaces(), PHP_EOL;
        $this->sm->step();
      }
      return $this->values;
    }

    private function appendToken()
    {
      echo "append: ", $this->currentToken, PHP_EOL;
      $this->values[] = $this->currentToken;
      $this->resetToken();
      $this->advanceChar();
    }

    private function resetToken()
    {
      $this->currentToken = "";
    }

    private function concatChar()
    {
      $this->currentToken .= $this->currentChar;
    }

    private function appendChar()
    {
      $this->concatChar();
      $this->advanceChar();
    }

    private function advanceChar()
    {
      if ($this->position >= strlen($this->source)) {
        $this->currentChar = chr(0);
      } else {
        $this->currentChar = substr($this->source, $this->position, 1);
        $this->position++;
      }
      echo "advance: ", $this->currentChar, PHP_EOL;
    }

    public function evalTransition(\Transition $transition): bool
    {
      switch ($transition->name) {
        case "T1":
        case "T2":
        case "T3":
          return $this->currentChar == $this->fieldSeparator;

        case "T4":
        case "T6":
        case "T7":
          return $this->currentChar == '"';

        case "T5":
          return $this->currentChar != '"';

        case "T9":
        case "T10":
        case "T15":
          return $this->currentChar == chr(0) || $this->currentChar == "\r" || $this->currentChar == "\n";

        case "T11":
          return $this->currentChar == "\r" || $this->currentChar == "\n";

        case "T12":
          return $this->currentChar != "\r" && $this->currentChar != "\n";

        case "T13":
        case "T14":
          return ord($this->currentChar) > 31 && ord($this->currentChar) < 255 && ord($this->currentChar) != 127;

        case "T8":
        case "T16":
        case "T17":
          return true;
      }
    }

    public function visitCallPlace(\Place $place)
    {
      echo "call: ", $place->getName(), PHP_EOL;
      switch ($place->getName()) {
        case "P0":
          $this->appendToken();
          break;
        case "P1":
        case "P5":
          $this->appendChar();
          break;
        case "P4":
          $this->advanceChar();
          break;
      }
    }

    public function visitEnterPlace(\Place $place)
    {
      echo "enter: ", $place->getName(), PHP_EOL;
      switch ($place->getName()) {
        case "P0":
        case "P4":
          $this->appendToken();
          break;
        case "P1":
        case "P2":
          $this->advanceChar();
          break;
        case "P3":
          $this->concatChar();
          break;
        case "P5":
          $this->appendChar();
          break;
        case "Per":
        case "Pf":
          $this->isFinished = true;
          break;
      }
    }

    public function visitLeavePlace(\Place $place)
    {
      // Rien à faire
    }

  }

  $reader = new CsvReader();
  $reader->setSource("alpha,bravo\ncharlie,delta\n");
  $line1 = $reader->getLine();
  $line2 = $reader->getLine();

  var_dump($line1);
  var_dump($line2);
