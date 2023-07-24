<?php

namespace App\Renderer;

use App\Component\ExampleImage;
use App\Pass\ExampleImagePass;
use GL\Buffer\FloatBuffer;
use VISU\ECS\EntitiesInterface;
use VISU\ECS\EntityRegisty;
use VISU\Graphics\BasicInstancedVertexArray;
use VISU\Graphics\BasicVertexArray;
use VISU\Graphics\GLState;
use VISU\Graphics\Rendering\RenderPipeline;
use VISU\Graphics\Rendering\Resource\RenderTargetResource;
use VISU\Graphics\ShaderCollection;
use VISU\Graphics\ShaderProgram;
use VISU\Graphics\Texture;
use VISU\Graphics\TextureOptions;

class ExampleImageRenderer
{
    private ShaderProgram $imageShader;

    /**
     * The background texture
     */
    private Texture $elephpantSprite;

    /**
     * Vertex array used for rendering
     */
    private BasicInstancedVertexArray $vertexArray;

    public function __construct(
        private GLState $gl,
        private ShaderCollection $shaders,
    )
    {
        // create the shader program
        $this->imageShader = $this->shaders->get('example_image');

        // this is pixel artish so we want to use nearest neighbor filtering
        $backgroundOptions = new TextureOptions;
        $backgroundOptions->minFilter = GL_NEAREST;
        $backgroundOptions->magFilter = GL_NEAREST;
        $this->elephpantSprite  = new Texture($gl, 'visuphpant');
        $this->elephpantSprite->loadFromFile(VISU_PATH_RESOURCES . '/sprites/visuphpant2.png', $backgroundOptions);

        // create a vertex array for rendering
        $this->vertexArray = new BasicInstancedVertexArray($gl, [2, 2], [4, 4, 4, 4, 1]);
        $this->vertexArray->uploadVertexData(new FloatBuffer([
            // vertex positions
            // this makes up the quad
            -1.0, -1.0,  0.0, -1.0,
             1.0, -1.0,  1.0, -1.0,
            -1.0,  1.0,  0.0,  0.0,
             1.0,  1.0,  1.0,  0.0,
        ]));
    }

    /**
     * Attaches a render pass to the pipeline
     * 
     * @param RenderPipeline $pipeline 
     * @param RenderTargetResource $renderTarget
     * @param array<ExampleImage> $exampleImages
     */
    public function attachPass(
        RenderPipeline $pipeline, 
        RenderTargetResource $renderTarget,
        EntitiesInterface $entities,
    ) : void
    {
        $pipeline->addPass(new ExampleImagePass(
            $this->gl,
            $this->imageShader,
            $this->elephpantSprite,
            $this->vertexArray,
            $renderTarget,
            $entities
        ));
    }
}
