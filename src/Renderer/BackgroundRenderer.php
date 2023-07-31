<?php

namespace App\Renderer;

use App\Component\SpriteComponent;
use App\Component\GlobalStateComponent;
use App\Component\PlayerComponent;
use GL\Math\Vec3;
use VISU\ECS\EntitiesInterface;
use VISU\Geo\Transform;
use VISU\Graphics\GLState;
use VISU\Graphics\QuadVertexArray;
use VISU\Graphics\Rendering\Pass\CallbackPass;
use VISU\Graphics\Rendering\Pass\CameraData;
use VISU\Graphics\Rendering\PipelineContainer;
use VISU\Graphics\Rendering\PipelineResources;
use VISU\Graphics\Rendering\RenderPass;
use VISU\Graphics\Rendering\RenderPipeline;
use VISU\Graphics\Rendering\Resource\RenderTargetResource;
use VISU\Graphics\ShaderCollection;
use VISU\Graphics\ShaderProgram;
use VISU\Graphics\Texture;
use VISU\Graphics\TextureOptions;

class BackgroundRenderer
{
    /**
     * Simple background shader
     */
    private ShaderProgram $backgroundShader;

    /**
     * The background texture
     */
    private Texture $backgroundTexture;

    /**
     * The vertex array for the quad
     */
    private QuadVertexArray $backgroundVA;

    /**
     * Constructor
     */
    public function __construct(
        private GLState $gl,
        private ShaderCollection $shaders,
    )
    {
        // load the background shader
        $this->backgroundShader = $this->shaders->get('background');

        // load the background texture
        // this is pixel artish so we want to use nearest neighbor filtering
        $backgroundOptions = new TextureOptions;
        $backgroundOptions->minFilter = GL_NEAREST;
        $backgroundOptions->magFilter = GL_NEAREST;
        $this->backgroundTexture  = new Texture($gl, 'background');
        $this->backgroundTexture->loadFromFile(VISU_PATH_RESOURCES . '/background/seamlessbg.png', $backgroundOptions);

        // create the vertex array
        $this->backgroundVA = new QuadVertexArray($gl);
    }

    /**
     * Attaches a render pass to the pipeline
     * 
     * @param RenderPipeline $pipeline 
     * @param RenderTargetResource $renderTarget
     * @param array<SpriteComponent> $exampleImages
     */
    public function attachPass(
        RenderPipeline $pipeline, 
        RenderTargetResource $renderTarget,
        EntitiesInterface $entities,
    ) : void
    {
        // you do not always have to create a new class for a render pass
        // often its more convenient to just create a closure as showcased here
        // to render the background
        $pipeline->addPass(new CallbackPass(
            'BackgroundPass',
            // setup (we need to declare who is reading and writing what)
            function(RenderPass $pass, RenderPipeline $pipeline, PipelineContainer $data) use($renderTarget) {
                $pipeline->writes($pass, $renderTarget);
            },
            // execute
            function(PipelineContainer $data, PipelineResources $resources) use($renderTarget, $entities)
            {
                $playerEntity = $entities->firstWith(PlayerComponent::class);
                $playerTransform = $entities->get($playerEntity, Transform::class);

                $resources->activateRenderTarget($renderTarget);

                glDisable(GL_DEPTH_TEST);
                glDisable(GL_CULL_FACE);

                $cameraData = $data->get(CameraData::class);
               

                // create a copy of the view matrix and remove the translation
                // because we want a parallax effect

                $view = $cameraData->view->copy();
                $view[12] = 0.0;
                $view[13] = 0.0;
                $view[14] = 0.0;
                // $view->translate($cameraData->renderCamera->transform->position * -1 * $scale);

                // enable our shader and set the uniforms camera uniforms
                $this->backgroundShader->use();
                $this->backgroundShader->setUniformMat4('u_view', false, $view);
                $this->backgroundShader->setUniformMat4('u_projection', false, $cameraData->projection);
                $this->backgroundShader->setUniform1i('u_texture', 0);
                
                // bind the texture
                $this->backgroundTexture->bind(GL_TEXTURE0);

                // draw the quad to fill the screen
                $aspectRatio = $this->backgroundTexture->width() / $this->backgroundTexture->height();

                $height = $cameraData->viewport->getHeight();
                $width = $height * $aspectRatio;

                $transform = new Transform;
                $transform->scale->x = $width * 0.5;
                $transform->scale->y = $height * 0.5;
                $transform->position->x = 0;
                $transform->position->y = 0;
                $this->backgroundShader->setUniformMat4('u_model', false, $transform->getLocalMatrix());
                
                // determine the background movement based
                $this->backgroundShader->setUniform1f('bgmove', $playerTransform->position->x * 0.0003);


                $this->backgroundVA->draw();
            }
        ));
    }
}
