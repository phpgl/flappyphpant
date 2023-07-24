<?php

namespace App\SignalHandler;

use VISU\Signals\Input\KeySignal;

class WindowActionsHandler
{
    /**
     * Simple window key event handler 
     * 
     *  - ESC: close the window
     * 
     * @param KeySignal $signal
     */
    public function handleWindowKey(KeySignal $signal)
    {
        // handle ESC key to close the window
        if ($signal->key == GLFW_KEY_ESCAPE && $signal->action == GLFW_PRESS) {
            $signal->window->setShouldClose(true);
        }
    }
}
