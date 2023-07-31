# Flappyphpant

A very simple flappybird clone written in PHP build on PHP-GLFW and the VISU framework.

<p align="center">
   <img width="640" src="https://github.com/phpgl/flappyphpant/assets/956212/c48d9c68-427e-4d92-a875-8ed9dc8ba2da" alt="Flappyphpant 2D PHP Game">
</p>

## Installation

Just clone the project and install the dependencies with composer.

Make sure the php-glfw extension is installed and enabled.

```bash
git clone https://github.com/phpgl/flappyphpant.git
cd flappyphpant
composer install
```

## Usage

```bash
bash bin/play
```

## Features

A lot of this is complete overkill for a simple flappybird game, but I see this as an example project how you could be build a 2D game with PHP-GLFW and VISU.

Also for time sake I did cut a few corners, so the code is not as clean as I would like it to be.

 - **Decoupled Simulation from Rendering**

   The `render` and `update` functions are decoupled. 
   This means the game simulation aka `update()` runs at a fixed rate while the rendering aka `render()` runs as fast as possible.
   (Or when vsync is enabled at the refresh rate of the monitor)
   The player movement is interpolated between the last and the current simulation step. Allowing smooth movement even when the simulation is running 
   significantly slower than the rendering.
   
   <img width="634" alt="s" src="https://github.com/phpgl/flappyphpant/assets/956212/eb7c1d03-a1bc-497f-806a-a95da00d7f43">

   Flappyphpant specically has a tickrate of `60ups` but can render at about `3000fps` with a resolution of `2560x1440` on a M1 MacBook Pro.

   _I was honestly really surprised how good the frametimes considering the entire framework is written in PHP._

 - **Proper 2D Camera**

    The camera unlike the real flappybird is not fixed to a vertical resolution. 
    The window can be resized to any resolution and aspect ratio you want and the game should scale properly.

     * Support for High DPI displays, meaning on retina displays the game will be rendered at a higher internal resolution.
     * The number of pipes rendered is based on the viewport and automatically adjusts to the window size.

    | Image 1    | Image 2     |
    |------------|-------------|
    | <img src="https://github.com/phpgl/flappyphpant/assets/956212/b72cd792-927a-438d-839a-030653cfc34e" width="400"> | <img src="https://github.com/phpgl/flappyphpant/assets/956212/19fb28e9-5156-404f-9144-b8b5edeb90b9" width="400"> |
    
