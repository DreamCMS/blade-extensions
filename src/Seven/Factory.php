<?php
/**
 * Created by IntelliJ IDEA.
 * User: radic
 * Date: 8/7/16
 * Time: 1:39 AM
 */

namespace Radic\BladeExtensions\Seven;


use Illuminate\Contracts\Foundation\Application;

class Factory
{
    const MODE_AUTO = 'auto';
    const MODE_CUSTOM = 'custom';
    const MODE_DISABLED = 'disabled';

    /** @var DirectiveRegistry */
    protected $directives;

    /**
     * @var string
     */
    protected $mode = self::MODE_AUTO;

    /** @var Application */
    protected $app;

    /**
     * @var string|\Closure
     */
    protected $customModeHandler;

    protected $blade;

    /**
     * Factory constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application   $app
     * @param \Radic\BladeExtensions\Seven\DirectiveRegistry $directives
     */
    public function __construct(Application $app, DirectiveRegistry $directives)
    {
        $this->app        = $app;
        $this->directives = $directives;
        $this->initModeHandler();
    }


    protected function initModeHandler()
    {
        $this->app->booted(function ($app) {
            if ( $this->mode === self::MODE_DISABLED ) {
                return;
            }
            if ( $this->mode === self::MODE_AUTO ) {
                $this->handleAutoMode();
            } elseif ( $this->mode === self::MODE_CUSTOM ) {
                $this->handleCustomMode();
            } else {
                throw new \RuntimeException('BladeExtensions Factory $mode not valid');
            }
        });
    }

    /**
     * @param Application $app
     */
    protected function handleAutoMode()
    {
        foreach ( $this->directives->directives() as $name ) {
            $this->getBladeCompiler()->extend(function ($value) use ($name) {
                return $this->directives->call($name, [ $value ]);
            });
        }
    }

    /**
     * @return \Illuminate\View\Compilers\BladeCompiler
     */
    protected function getBladeCompiler()
    {
        return $this->blade ?: $this->blade = $this->app->make('view')->getEngineResolver()->resolve('blade')->getCompiler();
    }

    /**
     * @param Application $app
     */
    protected function handleCustomMode()
    {
        if ( null === $this->customModeHandler ) {
            throw new \RuntimeException('[Custom Mode Handler Not Set]');
        }
        $this->app->call($this->customModeHandler, [ ], 'handle');
    }

    public function addDirectives(array $directives)
    {
        $this->directives->set($directives, null, true);
    }

    /**
     * @return \Radic\BladeExtensions\Seven\DirectiveRegistry
     */
    public function getDirectives()
    {
        return $this->directives;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    public function setCustomModeHandler($handler)
    {
        $this->customModeHandler = $handler;
    }


}
