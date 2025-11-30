<?php

namespace Drupal\svg_injector\Twig;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgInjectorExtension extends AbstractExtension {

    private ConfigFactoryInterface $configFactory;

    public function __construct(ConfigFactoryInterface $config_factory) {
        $this->configFactory = $config_factory;
    }

    public function getFunctions(): array {
        // Makes the function 'svg_icon' available in Twig
        return [
            new TwigFunction('svg_icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderIcon(string $name, array $parameters = []): ?string {
        static $cache = [];

        // Caching the svg while taking its parameters into account
        $cacheKey = md5($name . ':' . serialize($parameters));

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $index = $this->getSvgIndex();

        if (!isset($index[$name])) {
            return "<!-- Icon not found: $name -->";
        }

        // Attempt to read the svg, removal of errors with '@'
        $svg = @file_get_contents($index[$name]);

        if ($svg === false) {
            return "<!-- Icon unreadable: $name -->";
        }

        // Retrieves the unit setting defined in the configuration page
        $unit = $this->configFactory->get('svg_injector.settings')->get('size_unit') ?? 'px';
        if (!empty($parameters)) {
            $this->applySvgParameters($svg, $parameters);
        }

        $cache[$cacheKey] = $svg;
        return $svg;
    }


    private function applySvgParameters(string &$svg, array $parameters): void {
        // Permitted settings
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

            // Applies units to parameters relating to the size of the svg
            $isSizedAttribute = in_array($param, ['width', 'height', 'size'], true);
            $value = $parameters[$param];

            if ($isSizedAttribute && is_numeric($value)) {
                $value .= $this->getUnit();
            }

            $value = htmlspecialchars($value, ENT_QUOTES);

            foreach ($attributes as $attr) {
                // Cleaning up existing attributes from svg
                $svg = preg_replace('/(<svg[^>]*?)\s' . $attr . '="[^"]*"/i', '$1', $svg);
                $this->injectSvgParameter($svg, $attr, $value);
            }
        }
    }

    private function injectSvgParameter(string &$svg, string $element, string|int $value): void {
        $svg = preg_replace('/<svg\b/i', '<svg ' . $element . '="' . $value . '"', $svg, 1);
    }

    private function getSvgIndex(): array {
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

        $cache_duration = $this->configFactory->get('svg_injector.settings')->get('cache_duration') ?? 3600;
        \Drupal::cache()->set($cid, $index, $cache_duration, ['svg_injector:index']);

        return $index;
    }

    private function getUnit(): string {
        return $this->configFactory->get('svg_injector.settings')->get('size_unit') ?? 'px';
    }
}
