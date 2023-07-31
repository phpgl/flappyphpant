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

    | Vertical   | Horizontal  |
    |------------|-------------|
    | <img width="400" src="https://github.com/phpgl/flappyphpant/assets/956212/10238007-f2ce-4e87-9e8c-c307e3f53a13"> | <img src="https://github.com/phpgl/flappyphpant/assets/956212/b72cd792-927a-438d-839a-030653cfc34e" width="400"> |
    
 - **Abstracted Input Handling**

    Input handling can get messy fast, this example utilizes Input and Action maps to abstract the input handling and 
    theoretically allow for easy remapping of the controls.

    ```php
     // apply jump
    if ($this->inputContext->actions->didButtonPress('jump')) {
        $playerComponent->velocity = $playerComponent->jumpForce;
    }
    ```

    (I do understand how silly this is in a game where you basically just press one button.)

 - **Entity Component System**

    This example ueses an Entity Component System to manage the game objects and share state between the different systems.

    ```php
    $playerEntity = $entities->create()
    $entities->attach($playerEntity, new SpriteComponent('visuphpant2.png'));
    $entities->attach($playerEntity, new PlayerComponent);
    $entities->attach($playerEntity, new Transform);
    ```

    This kinda game is unfortunately not the best example for an ECS.

 - **Simple Sprite Renderer**

    This project showcases a simple sprite renderer that can render individual sprites from a sprite sheet.
    This is used to render the animated player elephant as well as the pipes. Nothing complex but should give you 
    a starting point if you want to build a 2D game with VISU.

 - **Very Basic AABB Collision Detection**

    The collision detection is very basic and only checks for collisions between the player AABB and the pipe AABBs.
    It will be infuriating at times as the elephant will collide with the pipes even if it looks like it should not.
