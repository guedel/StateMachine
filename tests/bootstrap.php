<?php

  /*
 * Copyright (C) 2020 Proximit
 *
 * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>.
 * @since 17/10/2020.
 *
 *
 */

require_once "../vendor/autoload.php";

spl_autoload_register(function($name) {
  require_once "../src/$name.php";
});

