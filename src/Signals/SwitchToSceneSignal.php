<?php

namespace App\Signals;

use App\Scene\BaseScene;
use VISU\Signal\Signal;

class SwitchToSceneSignal extends Signal
{
    /**
     * Constructor
     * 
     * @param BaseScene $newScene The scene to switch to
     */
    public function __construct(
        public readonly BaseScene $newScene,
    ) {

    }
}
