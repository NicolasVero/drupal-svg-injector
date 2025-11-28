<?php

namespace Drupal\svg_injector\Twig;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Component\Utility\Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgInjectorExtension extends AbstractExtension {

    protected $configFactory;

    public function __construct($config_factory) {
        $this->configFactory = $config_factory;
    }

    public function getFunctions() {
        return [
            new TwigFunction('svg_icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderIcon(string $name, array|null $parameters = null): string|null {
        static $cache = [];

        $cacheKey = md5($name . ':' . serialize($parameters));

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $index = $this->getSvgIndex();

        if (!isset($index[$name])) {
            return "<!-- Icon not found: $name -->";
        }

        $svg = @file_get_contents($index[$name]);

        if ($svg === false) {
            return "<!-- Icon unreadable: $name -->";
        }

        if (!empty($parameters)) {
            $this->addSvgParameters($svg, $parameters);
        }

        $cache[$cacheKey] = $svg;
        return $svg;
    }

    private function addSvgParameters(&$svg, $parameters) {
        $map = [
            'fill'         => ['fill'],
            'stroke'       => ['stroke'],
            'stroke_width' => ['stroke-width'],
            'width'        => ['width'],
            'height'       => ['height'],
            'size'         => ['width', 'height'],
            'class'        => ['class'],
            'id'           => ['id'],
            'aria_label'   => ['aria-label'],
            'role'         => ['role']
        ];

        foreach ($map as $param => $attributes) {
            if (empty($parameters[$param])) {
                continue;
            }

            $value = htmlspecialchars($parameters[$param], ENT_QUOTES);

            foreach ($attributes as $attr) {
                // Cleaning up existing attributes from svg
                $svg = preg_replace('/(<svg[^>]*?)\s' . $attr . '="[^"]*"/i', '$1', $svg);
                $this->addSvgParameter($svg, $attr, $value);
            }
        }
    }

    private function addSvgParameter(&$svg, $element, $value) {
        $svg = preg_replace('/<svg\b/i', '<svg ' . $element . '="' . $value . '"', $svg, 1);
    }

    protected function getSvgIndex(): array {
        $cid = 'svg_injector.index';

        if ($cached = \Drupal::cache()->get($cid)) {
            return $cached->data;
        }

        $icon_path = $this->configFactory->get('svg_injector.settings')->get('icon_path');
        $absolute_path = DRUPAL_ROOT . '/' . $icon_path;

        $index = [];

        if (is_dir($absolute_path)) {
            $files = \Drupal::service('file_system')->scanDirectory($absolute_path, '/\.svg$/');

            foreach ($files as $file) {
                $filename = $file->filename;
                $name = pathinfo($filename, PATHINFO_FILENAME);

                if (!isset($index[$name])) {
                    $index[$name] = $file->uri;
                }
            }
        }

        \Drupal::cache()->set($cid, $index, time() + 3600);
        return $index;
    }
}