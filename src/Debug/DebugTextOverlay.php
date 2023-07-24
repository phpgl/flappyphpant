<?php

namespace App\Debug;

use GameContainer;
use GL\Math\Vec3;
use VISU\Graphics\Font\DebugFontRenderer;
use VISU\Graphics\Rendering\PipelineResources;
use VISU\Graphics\Rendering\Renderer\DebugOverlayText;
use VISU\Graphics\Rendering\Renderer\DebugOverlayTextRenderer;
use VISU\Graphics\Rendering\RenderPipeline;
use VISU\Graphics\Rendering\Resource\RenderTargetResource;
use VISU\OS\Input;
use VISU\OS\Key;
use VISU\Signals\Input\KeySignal;

class DebugTextOverlay
{
    /**
     * Rows to be rendered on the next frame
     * 
     * @var array
     */
    static private $globalRows = [];

    /**
     * Adds a string to the global debug overlay
     * 
     * @param string $row 
     * @return void 
     */
    static public function debugString(string $row) : void
    {
        self::$globalRows[] = $row;
    }

    /**
     * Debug font renderer
     */
    private DebugOverlayTextRenderer $debugTextRenderer;

    /**
     * An array of string to be renderd on the next frame
     * 
     * @var array<string>
     */
    private array $rows = [];

    /**
     * Toggles debug overlay
     */
    public bool $enabled = true;

    /**
     * Constructor
     * 
     * As this is a debugging utility, we will use the container directly
     */
    public function __construct(
        private GameContainer $container
    ) {
        $this->debugTextRenderer = new DebugOverlayTextRenderer(
            $container->resolveGL(),
            DebugOverlayTextRenderer::loadDebugFontAtlas(),
        );

        // listen to keyboard events to toggle debug overlay
        $container->resolveVisuDispatcher()->register('input.key', function(KeySignal $keySignal) {
            if ($keySignal->key == Key::F1 && $keySignal->action == Input::PRESS) {
                $this->enabled = !$this->enabled;
            }
        });
    }

    private function gameLoopMetrics(float $deltaTime) : string
    {
        $gameLoop = $this->container->resolveGameLoopMain();

        $row =  str_pad("FPS: " . round($gameLoop->getAverageFps()), 8);
        $row .= str_pad(" | TC: " . sprintf("%.2f", $gameLoop->getAverageTickCount()), 10);
        $row .= str_pad(" | UT: " . $gameLoop->getAverageTickTimeFormatted(), 18);
        $row .= str_pad(" | FT: " . $gameLoop->getAverageFrameTimeFormatted(), 16);
        $row .= " | delta: " . sprintf("%.4f", $deltaTime);
    
        return $row;
    }

    private function formatNStoHuman(int $ns) : string
    {
        if ($ns < 1000) {
            return sprintf("%.2f ns", $ns);
        }
        elseif ($ns < 1000000) {
            return sprintf("%.2f µs", $ns / 1000);
        }
        elseif ($ns < 1000000000) {
            return sprintf("%.2f ms", $ns / 1000000);
        }
        else {
            return sprintf("%.2f s", $ns / 1000000000);
        }
    }

    private function gameProfilerMetrics() : array
    {
        $profiler = $this->container->resolveProfiler();

        $scopeAverages = $profiler->getAveragesPerScope();
        $rows = [];

        // sort the averages by GPU consumption
        uasort($scopeAverages, function($a, $b) {
            return $b['gpu'] <=> $a['gpu'];
        });

        foreach($scopeAverages as $scope => $averages) {
            $row = str_pad("[" . $scope . ']', 25);
            $row .= str_pad(" CPU(" . $averages['cpu_samples'] . "): " . $this->formatNStoHuman((int) $averages['cpu']), 20);
            $row .= str_pad(" | GPU(" . $averages['gpu_samples'] . "): " . $this->formatNStoHuman((int) $averages['gpu']), 20);
            $row .= str_pad(" | Tri: " . round($averages['gpu_triangles']), 10);
            $rows[] = $row;
        } 

        return $rows;
    }

    /**
     * Draws the debug text overlay if enabled
     */
    public function attachPass(RenderPipeline $pipeline, PipelineResources $resources, RenderTargetResource $rt, float $compensation)
    {
        // we sync the profile enabled state with the debug overlay
        $this->container->resolveProfiler()->enabled = $this->enabled;
        
        if (!$this->enabled) {
            $this->rows = []; // reset the rows to avoid them stacking up
            self::$globalRows = [];
            return;
        }

        // get the actual rendering target
        $target = $resources->getRenderTarget($rt);

        // Add current FPS plus the average tick count and the compensation
        $this->rows[] = $this->gameLoopMetrics($compensation);
        $this->rows[] = "Scene: " . $this->container->resolveGame()->getCurrentScene()->getName() . 
            ' | Press CTRL + C to open the console';

        // add global rows
        $this->rows = array_merge($this->rows, self::$globalRows);

        
        // we render to the backbuffer
        $this->debugTextRenderer->attachPass($pipeline, $rt, [
            new DebugOverlayText(implode("\n", $this->rows), 10, 10)
        ]);

        $profilerLines =  $this->gameProfilerMetrics();
        $y = $rt->height - (count($profilerLines) * $this->debugTextRenderer->lineHeight * $target->contentScaleX);
        $y -= 25;
        $this->debugTextRenderer->attachPass($pipeline, $rt, [
            new DebugOverlayText(implode("\n", $profilerLines), 10, $y, new Vec3(0.726, 0.865, 1.0)),
        ]);


        // clear the rows for the next frame
        $this->rows = [];
        self::$globalRows = [];
    }

    public function __debugInfo()
    {
        return [
            'enabled' => $this->enabled,
            'rows' => $this->rows,
        ];
    }
}
