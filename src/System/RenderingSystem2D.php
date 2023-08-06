<?php

namespace App\System;

use App\Component\SpriteComponent;
use App\Renderer\BackgroundRenderer;
use App\Renderer\SpriteRenderer;
use GL\Math\{GLM, Quat, Vec2, Vec3};
use VISU\ECS\EntitiesInterface;
use VISU\ECS\SystemInterface;
use VISU\Geo\Transform;
use VISU\Graphics\GLState;
use VISU\Graphics\Rendering\Pass\BackbufferData;
use VISU\Graphics\Rendering\Pass\CameraData;
use VISU\Graphics\Rendering\Pass\ClearPass;
use VISU\Graphics\Rendering\RenderContext;
use VISU\Graphics\Rendering\Renderer\DebugOverlayTextRenderer;
use VISU\Graphics\Rendering\Renderer\FullscreenTextureRenderer;
use VISU\Graphics\Rendering\Renderer\TextLabelRenderer;
use VISU\Graphics\Rendering\Renderer\TextLabelRenderer\TextLabel;
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
    private SpriteRenderer $spriteRenderer;

    /**
     * Fullscreen Texture Debug Renderer
     */
    private FullscreenTextureRenderer $fullscreenRenderer;

    /**
     * Text Label Renderer
     */
    private TextLabelRenderer $textLabelRenderer;

    /**
     * Constructor
     */
    public function __construct(
        private GLState $gl,
        private ShaderCollection $shaders
    )
    {
        $this->backgroundRenderer = new BackgroundRenderer($this->gl, $this->shaders);
        $this->spriteRenderer = new SpriteRenderer($this->gl, $this->shaders);
        $this->fullscreenRenderer = new FullscreenTextureRenderer($this->gl);
        $this->textLabelRenderer = new TextLabelRenderer($this->gl, $this->shaders);
    }
    
    /**
     * Registers the system, this is where you should register all required components.
     * 
     * @return void 
     */
    public function register(EntitiesInterface $entities) : void
    {
        $entities->registerComponent(Transform::class);
        $entities->registerComponent(TextLabel::class);

        $this->textLabelRenderer->loadFont('debug', DebugOverlayTextRenderer::loadDebugFontAtlas());
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
        $this->textLabelRenderer->synchroniseWithEntites($entities);
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
        $this->spriteRenderer->attachPass($context->pipeline, $sceneRenderTarget, $entities);

        // add the text label pass
        $this->textLabelRenderer->attachPass($context->pipeline, $sceneRenderTarget);

        // add a pass that renders the scene render target to the backbuffer
        $this->fullscreenRenderer->attachPass($context->pipeline, $backbuffer, $sceneColor);
    }
}