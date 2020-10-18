<?php

  /*
   * Copyright (C) 2020 Proximit
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>.
   * @since 16/10/2020.
   *
   *
   */
  namespace guedel\StateMachine;

  /**
   *
   * @author Guillaume de Lestanville <guillaume.delestanville@proximit.fr>
   */
  interface IAutomatonVisitor
  {
    public function evalTransition(Transition $transition): bool;
    public function visitCallPlace(Place $place);
    public function visitEnterPlace(Place $place);
    public function visitLeavePlace(Place $place);
  }
