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

use guedel\Microtest\Assert;
use guedel\StateMachine\Automaton;
use guedel\StateMachine\Place;
use guedel\StateMachine\Transition;

$ut = new guedel\Microtest\UnitTest();
$sm = null;
$counter = 0;

function testTransition(Transition $t)
{
  return true;
}

function passPlace(Place $p) {
  global $counter;
  switch ($p->getName()) {
    case 'P1':
      $counter += 1;
      break;
    case 'Pf':
      $counter += 2;
      break;
  }
}

$ut->setTitle("Test of Automaton");
$ut->addTest("Création", function() {
  global $sm;
  $sm = new Automaton();
  $sm->reset();
  Assert::is_true($sm instanceof Automaton);
});

$ut->addTest("Add Places", function() {
  global $sm;
  $sm->addPlace("P0", true)
      ->addPlace("P1")
      ->addPlace("Pf")
  ;
});

$ut->addTest("Add Transitions", function() {
  global $sm;
  $sm->addTransition("T1");
});

$ut->addTest("Add arc pre", function() {
  global $sm;
  $sm->addArcPre('P0', 'T1');
});

$ut->addTest("Add arc post", function() {
  global $sm;
  $sm->addArcPost('T1', 'P1');
});

$ut->addTest("AddArc", function() {
  global $sm;
  $sm->addArc('P1', 'T2', 'Pf');
});

$ut->addTest("Advance", function() {
  global $counter;
  $sm = new Automaton();
  $sm
    ->addPlace('P0', true)
    ->addArc('P0', 'T1', 'P1')
    ->addArc('P1', 'T2', 'Pf')
    ->registerTestTransition("testTransition")
    ->registerEnterPlace("passPlace")
  ;
  $sm->step();
  $sm->step();
  Assert::is_true($sm->getPlaces()["Pf"]->isActive());
  Assert::equal(3, $counter);
});

$ut->addTest("And junction", function() {
  $sm = new Automaton();
  $sm
    ->addPlace("P0", true)
    ->addPlace("P1", false)
    ->addArcPre("P0", "T1")
    ->addArcPre("P1", "T1")
    ->addArcPost("T1", "P2")
    ->registerTestTransition("testTransition")
  ;
  $sm->step();
  $l = $sm->getPlaces();
  Assert::is_false($l["P2"]->isActive());
  $l['P1']->activate();
  $sm->step();
  Assert::is_true($l["P2"]->isActive());
});

$ut->addTest("And junction 2", function() {
  $sm = new Automaton(ACTIVATE_ONE_STATE_BEFORE);
  $sm
    ->addPlace("P0", false)
    ->addPlace("P1", true)
    ->addArcPre("P0", "T1")
    ->addArcPre("P1", "T1")
    ->addArcPost("T1", "P2")
    ->registerTestTransition("testTransition")
  ;
  $sm->step();
  $l = $sm->getPlaces();
  Assert::is_true($l["P2"]->isActive());
});

$ut->addTest("Or junction", function() {
  $sm = new Automaton(ACTIVATE_ONE_STATE_BEFORE);
  $sm
    ->addPlace("P0", true)
    ->addPlace("P1", true)
    ->addArc("P0", "T1", "Pf")
    ->addArc("P1", "T2", "Pf")
    ->registerTestTransition("testTransition")
  ;
  $sm->step();
  $l = $sm->getPlaces();
  Assert::is_true($l["Pf"]->isActive());
});

$ut->addTest("And derivation", function() {
  $sm = new Automaton();
  $sm
    ->addPlace("P0", true)
    ->addArc("P0", "T1", "P1")
    ->addArc("P0", "T1", "P2")
    ->registerTestTransition("testTransition")
  ;
  $sm->step();
  $l = $sm->getPlaces();
  Assert::is_false($l["P0"]->isActive());
  Assert::is_true($l["P1"]->isActive());
  Assert::is_true($l["P2"]->isActive());
});

$ut->addTest("Or derivation", function() {
  $sm = new Automaton();
  $sm
    ->addPlace("P0", true)
    ->addArc("P0", "T1", "P1")
    ->addArc("PO", "T2", "P2")
    ->registerTestTransition(function($t) { return $t->name == "T1"; })
  ;
  $sm->step();
  $l = $sm->getPlaces();
  Assert::is_false($l["P0"]->isActive());
  Assert::is_true($l["P1"]->isActive());
  Assert::is_false($l["P2"]->isActive());
});


$ut->testAll();
