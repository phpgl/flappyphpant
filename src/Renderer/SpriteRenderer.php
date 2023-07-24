<?php

namespace App\Renderer;

use App\Component\SpriteComponent;
use App\Pass\SpritePass;
use GL\Buffer\FloatBuffer;
use GL\Math\Vec2;
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

class SpriteRenderer
{
    private ShaderProgram $imageShader;

    /**
     * The loaded sprite textures
     * 
     * @var array<string, Texture>
     */
    private array $spriteTextures = [];

    /**
     * The loaded sprite dimensions
     * 
     * @var array<string, Vec2>
     */
    private array $spriteDimensions = [];

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

        foreach([
            'visuphpant2.png' => new Vec2(32, 32),
            'pipe.png' => new Vec2(64, 64),
        ] as $spriteName => $dimensions) {
            $texture = new Texture($gl, $spriteName);
            $texture->loadFromFile(VISU_PATH_RESOURCES . '/sprites/' . $spriteName, $backgroundOptions);

            $this->bindSprite($spriteName, $texture, $dimensions);
        }

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
     * @param array<SpriteComponent> $exampleImages
     */
    public function attachPass(
        RenderPipeline $pipeline, 
        RenderTargetResource $renderTarget,
        EntitiesInterface $entities,
    ) : void
    {
        $pipeline->addPass(new SpritePass(
            $this->gl,
            $this->imageShader,
            $this->spriteTextures,
            $this->spriteDimensions,
            $this->vertexArray,
            $renderTarget,
            $entities
        ));
    }

    /**
     * Bind a sprite to the renderer
     */
    public function bindSprite(string $name, Texture $texture, Vec2 $dimensions) : void
    {
        $this->spriteTextures[$name] = $texture;
        $this->spriteDimensions[$name] = $dimensions;
    }
}
