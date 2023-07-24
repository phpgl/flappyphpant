<?php

namespace App\System;

use App\Component\ExampleImage;
use App\Renderer\BackgroundRenderer;
use App\Renderer\ExampleImageRenderer;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Transform;
use VISU\Graphics\GLState;
use VISU\Graphics\Rendering\Pass\BackbufferData;
use VISU\Graphics\Rendering\Pass\CameraData;
use VISU\Graphics\Rendering\Pass\ClearPass;
use VISU\Graphics\Rendering\RenderContext;
use VISU\Graphics\Rendering\Renderer\FullscreenTextureRenderer;
use VISU\Graphics\ShaderCollection;
use VISU\Graphics\TextureOptions;

class RenderingSystem2D implements SystemInterface
{
    /**
     * Background renderer
     */
    private BackgroundRenderer $backgroundRenderer;

    /**
     * The example image renderer
     */
    private ExampleImageRenderer $exampleImageRenderer;

    /**
     * Fullscreen Texture Debug Renderer
     */
    private FullscreenTextureRenderer $fullscreenRenderer;

    /**
     * Constructor
     */
    public function __construct(
        private GLState $gl,
        private ShaderCollection $shaders
    )
    {
        $this->backgroundRenderer = new BackgroundRenderer($this->gl, $this->shaders);
        $this->exampleImageRenderer = new ExampleImageRenderer($this->gl, $this->shaders);
        $this->fullscreenRenderer = new FullscreenTextureRenderer($this->gl);
    }
    
    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        $entities->registerComponent(Transform::class);
    }

    /**
     * Unregisters the system, this is where you can handle any cleanup.
     * 
     * @return void 
     */
    public function unregister(EntitiesInterface $entities) : void
    {
    }

    /**
     * Updates handler, this is where the game state should be updated.
     * 
     * @return void 
     */
    public function update(EntitiesInterface $entities) : void
    {
    }

    /**
     * Handles rendering of the scene, here you can attach additional render passes,
     * modify the render pipeline or customize rendering related data.
     * 
     * @param RenderContext $context
     */
    public function render(EntitiesInterface $entities, RenderContext $context) : void
    {
        // retrieve the backbuffer and clear it
        $backbuffer = $context->data->get(BackbufferData::class)->target;
        $context->pipeline->addPass(new ClearPass($backbuffer));

        // fetch the camera data
        $cameraData = $context->data->get(CameraData::class);

        // create an intermediate 
        $sceneRenderTarget = $context->pipeline->createRenderTarget('scene', $cameraData->resolutionX, $cameraData->resolutionY);

        // depth
        $sceneDepth = $context->pipeline->createDepthAttachment($sceneRenderTarget);

        $sceneColorOptions = new TextureOptions;
        $sceneColorOptions->internalFormat = GL_RGB;
        $sceneColor = $context->pipeline->createColorAttachment($sceneRenderTarget, 'sceneColor', $sceneColorOptions);

        // add the background pass
        $this->backgroundRenderer->attachPass($context->pipeline, $sceneRenderTarget, $entities);

        // add the image example pass
        $this->exampleImageRenderer->attachPass($context->pipeline, $sceneRenderTarget, $entities);

        // add a pass that renders the scene render target to the backbuffer
        $this->fullscreenRenderer->attachPass($context->pipeline, $backbuffer, $sceneColor);
    }
}