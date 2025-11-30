<?php

namespace Drupal\svg_injector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;

class SvgInjectorSettingsForm extends ConfigFormBase {

    protected function getEditableConfigNames(): array {
        return ['svg_injector.settings'];
    }

    public function getFormId(): string {
        return 'svg_injector_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array {
        $config = $this->config('svg_injector.settings');
        $path = $form_state->getValue('icon_path') ?? $config->get('icon_path');
        $svgCount = $this->countSvgInFolder($path ?? "");

        // Configuration form on the path to svg
        $form['icon_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path to SVG icons'),
            '#default_value' => $path,
            '#description' => $this->t('Relative path from Drupal root (e.g., themes/custom/your_theme/src/assets/icons)'),
            '#required' => true,
            '#ajax' => [
                'callback' => '::ajaxUpdateSvgCount',
                'event' => 'blur',
                'wrapper' => 'svg-count-wrapper',
            ],
        ];

        // svg counter found in the specified folder
        $form['svg_count_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'svg-count-wrapper'],
        ];

        $form['svg_count_wrapper']['icons_count'] = [
            '#markup' => '<p><strong>' . $this->formatSvgCountMessage($svgCount) . '</strong></p><br>',
        ];

        // Configuration form for configuring the unit of measurement used 
        $form['size_unit'] = [
            '#type' => 'select',
            '#title' => $this->t('Unit for size / width / height'),
            '#options' => [
                'px'  => 'px',
                'em'  => 'em',
                'rem' => 'rem',
                '%'   => '%',
                'vh'  => 'vh',
                'vw'  => 'vw',
            ],
            '#default_value' => $config->get('size_unit') ?? 'px',
            '#description' => $this->t('Unit used when applying size, width, or height attributes to SVG icons.'),
        ];

        // Configuration form for cache duration
        $form['cache_duration'] = [
            '#type' => 'number',
            '#title' => $this->t('Cache duration (in seconds)'),
            '#default_value' => $config->get('cache_duration') ?? 3600,
            '#min' => 0,
            '#description' => $this->t('Duration during which the generated SVG index is cached. Set to 0 to disable caching.'),
        ];

        return parent::buildForm($form, $form_state);
    }

    public function ajaxUpdateSvgCount(array &$form, FormStateInterface $form_state): mixed {
        return $form['svg_count_wrapper'];
    }

    private function formatSvgCountMessage($count): string {
        return match (true) {
            $count <= 0 => $this->t('No icons found in this folder.'),
            $count == 1 => $this->t('1 icon found in this folder.'),
            $count > 1 => $this->t('@count icons found in this folder.', ['@count' => $count]),
        };
    }

    private function countSvgInFolder(string $path): int {
        // Recursively search for svg files in the folder specified by the user
        if (empty($path)) {
            return 0;
        }

        $absolute_path = DRUPAL_ROOT . '/' . $path;

        if (!is_dir($absolute_path)) {
            return 0;
        }

        $file_system = \Drupal::service('file_system');
        $results = $file_system->scanDirectory($absolute_path, '/\.svg$/');

        return count($results);
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void {
        // Invalidate cache tag when configuration changes.
        \Drupal\Core\Cache\Cache::invalidateTags(['svg_injector:index']);

        // Saving user settings during registration
        $this->config('svg_injector.settings')
            ->set('icon_path', $form_state->getValue('icon_path'))
            ->set('size_unit', $form_state->getValue('size_unit'))
            ->set('cache_duration', $form_state->getValue('cache_duration'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
