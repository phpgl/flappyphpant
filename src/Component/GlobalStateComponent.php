<?php

namespace App\Component;

use GL\Math\Vec2;

class GlobalStateComponent
{
    /**
     * The current tick
     */
    public int $tick = 0;

    /**
     * Boolean to check if the game is paused
     */
    public bool $paused = true;

    /**
     * Boolean if the game is waiting to start
     */
    public bool $waitingForStart = true;
}
