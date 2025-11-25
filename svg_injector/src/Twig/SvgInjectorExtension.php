<?php

namespace Drupal\svg_injector\Twig;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Component\Utility\Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgInjectorExtension extends AbstractExtension {

    protected $configFactory;
    protected $themeHandler;

    public function __construct($config_factory, $theme_handler) {
        $this->configFactory = $config_factory;
        $this->themeHandler = $theme_handler;
    }

    public function getFunctions() {
        return [
            new TwigFunction('icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderIcon(string $name): string {
        static $cache = [];

        if (!isset($cache[$name])) {
            $icon_path = $this->configFactory->get('svg_injector.settings')->get('icon_path');
            $path = DRUPAL_ROOT . '/' . $icon_path . '/' . $name . '.svg';

            if (!is_readable($path)) {
                return "<!-- Icon not found: $name -->";
            }

            $cache[$name] = file_get_contents($path);
        }

        return $cache[$name];
    }
}
