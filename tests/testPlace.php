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
use guedel\Microtest\UnitTest;
use guedel\StateMachine\Place;

$ut = new UnitTest();
$ut->setTitle("Test de la classe Place");

$ut->addTest("Création", function() {
  $p0 = new Place("P0", true);
  $p1 = new Place("P1");

  Assert::is_true($p0 instanceof Place);
  Assert::is_true($p1 instanceof Place);
  Assert::equal(true, $p0->isActive());
  Assert::equal(false, $p1->isActive());
});

$ut->addTest("Save state", function() {
  $p = new Place("P");
  $p->activate(true);
  Assert::equal(true, $p->isActive());
  Assert::equal("Place: P, actif", (string)$p);
  $p->activate(false);
  Assert::equal(false, $p->isActive());
  Assert::equal("Place: P, fut actif", (string)$p);
});

$ut->testAll();
