<?php

namespace Drupal\svg_injector\Twig;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Component\Utility\Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgInjectorExtension extends AbstractExtension {

    protected $themeHandler;

    public function __construct($theme_handler) {
        $this->themeHandler = $theme_handler;
    }

    public function getFunctions() {
        return [
            new TwigFunction('icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderIcon(string $name, array $attributes = []): string {
        static $cache = [];

        if (isset($cache[$name])) {
            $svg = $cache[$name];
        }
        else {
            $active_theme = \Drupal::theme()->getActiveTheme()->getName();
            $theme_path = $this->themeHandler->getPath($active_theme);

            $path = DRUPAL_ROOT . '/' . $theme_path . '/src/assets/images/icons/' . $name . '.svg';

            if (!is_readable($path)) {
                return sprintf('<!-- Icon not found: %s -->', Html::escape($name));
            }

            $svg = file_get_contents($path) ?: '';
            $cache[$name] = $svg;
        }

        if ($svg === '') {
            return sprintf('<!-- Icon empty: %s -->', Html::escape($name));
        }

        return $svg;
    }
}
