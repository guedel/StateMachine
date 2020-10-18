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
use guedel\StateMachine\Places;

$ut = new UnitTest();
$ut->setTitle("Test de la classe Places");

$coll = null;

$ut->addTest("Création", function() {
  global $coll;
  $coll = new Places();
  Assert::is_true($coll instanceof Places);
  Assert::equal(0, $coll->count());
});

$ut->addTest("Créattion de place", function() {
  global $coll;
  $p = Places::create("P");
  Assert::is_true($p instanceof Place);
});

$ut->addTest("Ajout dans la collection", function() {
  global $coll;
  $p = Places::create("P0", true);
  $coll->append($p);
  Assert::equal(1, $coll->count());
});

$ut->addTest("Parcours", function () {
  global $coll;
  $ct = 0;
  foreach($coll as $p) {
    Assert::is_true($p instanceof Place);
    $ct ++;
  }
  Assert::equal(1, $ct);
});

$ut->testAll();
