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
        return [
            new TwigFunction('svg_icon', [$this, 'renderIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderIcon(string $name, array $parameters = []): ?string {
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

        $unit = $this->configFactory->get('svg_injector.settings')->get('size_unit') ?? 'px';
        if (!empty($parameters)) {
            $this->addSvgParameters($svg, $parameters);
        }

        $cache[$cacheKey] = $svg;
        return $svg;
    }


    private function addSvgParameters(string &$svg, array $parameters): void {
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

            $isSizedAttribute = in_array($param, ['width', 'height', 'size'], true);
            $value = $parameters[$param];

            if ($isSizedAttribute && is_numeric($value)) {
                $value .= $this->getUnit();
            }

            $value = htmlspecialchars($value, ENT_QUOTES);

            foreach ($attributes as $attr) {
                // Cleaning up existing attributes from svg
                $svg = preg_replace('/(<svg[^>]*?)\s' . $attr . '="[^"]*"/i', '$1', $svg);
                $this->addSvgParameter($svg, $attr, $value);
            }
        }
    }

    private function addSvgParameter(string &$svg, string $element, string|int $value): void {
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

        \Drupal::cache()->set($cid, $index, time() + 3600);
        return $index;
    }

    private function getUnit(): string {
        return $this->configFactory->get('svg_injector.settings')->get('size_unit') ?? 'px';
    }
}
