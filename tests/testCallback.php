<?php

  /*
 * Copyright (C) 2020 Proximit
 *
 * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>.
 * @since 17/10/2020.
 *
 *
 */

function appel() {
  echo "coucou";
}

function test(callable $f) {
  //call_user_func($f);
  $f();
}

test("appel");
