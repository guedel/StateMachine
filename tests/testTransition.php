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
use Guedel\StateMachine\IAutomatonVisitor;
use Guedel\StateMachine\Transition;
use Guedel\StateMachine\Places;
use Guedel\StateMachine\Place;

$ut = new UnitTest();
$ut->setTitle("Test de la classe Transition");

class TestVisitor implements IAutomatonVisitor {

   public function evalTransition(Transition $transition): bool
    {
      return true;
    }

    public function visitCallPlace(Place $place)
    {
    }

    public function visitEnterPlace(Place $place)
    {
    }

    public function visitLeavePlace(Place $place)
    {
    }
  }

function ever(Transition $t)
{
  return true;
}

$ut->addTest("Création", function() {
  $t = new Transition("T0");
  Assert::is_true($t instanceof Transition);
  Assert::is_true($t->before() instanceof Places);
  Assert::is_true($t->after() instanceof Places);
});

$ut->addTest("Activable", function() {
  $t = new Transition("T1");
  Assert::is_false($t->isActivable());
  $t->before(new Place("P0", true));
  Assert::is_true($t->isActivable());
  $p1 = new Place("P1");
  $t->before($p1);
  Assert::is_false($t->isActivable(ACTIVATE_ALL_STATES_BEFORE));
  Assert::is_true($t->isActivable(ACTIVATE_ONE_STATE_BEFORE));
  $p1->activate();
  Assert::is_true($t->isActivable(ACTIVATE_ALL_STATES_BEFORE));
  Assert::is_true($t->isActivable(ACTIVATE_ONE_STATE_BEFORE));
});

$ut->addTest("Franchissement", function() {
  $v = new TestVisitor();
  $t = new Transition("T");
  $t->accept($v);
  Assert::is_true($t->completed());
});

$ut->addTest("Franchissement 2", function() {
  $t = new Transition("T");
  $t->test(function () { return true; });
  Assert::is_true($t->completed());
});

$ut->addTest("Franchissement 3", function() {
  $t = new Transition("T");
  $t->test("ever");
  Assert::is_true($t->completed());
});


$ut->addTest("Conflicts", function() {
  $t2 = new Transition("T2");
  $t3 = new Transition("T3");
  $p2 = new Place("P2");
  $t2->before($p2);
  $t3->before($p2);
  Assert::is_true($t2->structuralConflict($t3));
  Assert::is_true($t3->structuralConflict($t2));
  $p2->activate();
  Assert::is_false($t2->effectiveConflict($t3));
  Assert::is_false($t3->effectiveConflict($t2));
  $v = new TestVisitor();
  $t2->accept($v);
  $t3->accept($v);
  Assert::is_true($t2->effectiveConflict($t3), "il doit y avoir un conflit effectif entre T2 et T3");
  Assert::is_true($t3->effectiveConflict($t2), "il doit y avoir un conflit effectif entre T3 et T2");
});

$ut->testAll();
