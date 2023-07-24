<?php

namespace App\System;

use App\Component\GameCamera2DComponent;
use App\Component\PlayerComponent;
use App\Debug\DebugTextOverlay;
use GL\Math\Vec2;
use GL\Math\Vec3;
use VISU\ECS\EntitiesInterface;
use VISU\Geo\Transform;
use VISU\Graphics\Camera;
use VISU\Graphics\CameraProjectionMode;
use VISU\Graphics\Rendering\RenderContext;
use VISU\OS\Input;
use VISU\OS\InputContextMap;
use VISU\Signal\Dispatcher;
use VISU\Signals\Input\CursorPosSignal;
use VISU\Signals\Input\ScrollSignal;
use VISU\System\VISUCameraSystem;

class CameraSystem2D extends VISUCameraSystem
{
    /**
     * Default camera mode is game in the game... 
     */
    protected int $visuCameraMode = self::CAMERA_MODE_GAME;

    /**
     * Constructor
     */
    public function __construct(
        Input $input,
        Dispatcher $dispatcher,
        protected InputContextMap $inputContext,
    )
    {
        parent::__construct($input, $dispatcher);
    }

    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        parent::register($entities);

        $entities->registerComponent(PlayerComponent::class);

        // create an inital camera entity
        $cameraEntity = $entities->create();
        $camera = $entities->attach($cameraEntity, new Camera(CameraProjectionMode::orthographicStaticWorld));
        $camera->nearPlane = -10;
        $camera->farPlane = 10;

        // make the camera the active camera
        $this->setActiveCameraEntity($cameraEntity);
    }

    /**
     * Unregisters the system, this is where you can handle any cleanup.
     * 
     * @return void 
     */
    public function unregister(EntitiesInterface $entities) : void
    {
        parent::unregister($entities);
    }

    /**
     * Override this method to handle the cursor position in game mode
     * 
     * @param CursorPosSignal $signal 
     * @return void 
     */
    protected function handleCursorPosVISUGame(EntitiesInterface $entities, CursorPosSignal $signal) : void
    {
        // handle mouse movement
    }

    /**
     * Override this method to handle the scroll wheel in game mode
     * 
     * @param ScrollSignal $signal
     * @return void 
     */
    protected function handleScrollVISUGame(EntitiesInterface $entities, ScrollSignal $signal) : void
    {
        // handle mouse scroll
    }

    /**
     * Override this method to update the camera in game mode
     * 
     * @param EntitiesInterface $entities
     */
    public function updateGameCamera(EntitiesInterface $entities, Camera $camera) : void
    {
        $playerEntity = $entities->firstWith(PlayerComponent::class);
        $playerTransform = $entities->get($playerEntity, Transform::class);

        // because the camera is attached to the player
        // we have to disable interpolation to avoid jittering
        $camera->allowInterpolation = false;

        // copy the player position to the camera
        $camera->transform->markDirty();
        $camera->transform->position = new Vec3(
            $playerTransform->position->x, // camera always follows the player
            0, // camera is always in the vertical center of the world
            0
        );
    }
}
