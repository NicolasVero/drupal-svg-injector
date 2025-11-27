<?php

namespace Drupal\svg_injector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;

class SvgInjectorSettingsForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return ['svg_injector.settings'];
    }

    public function getFormId() {
        return 'svg_injector_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('svg_injector.settings');
        $path = $form_state->getValue('icon_path') ?? $config->get('icon_path');
        $svgCount = $this->countSvgInFolder($path);

        $form['icon_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path to SVG icons'),
            '#default_value' => $path,
            '#description' => $this->t('Relative path from Drupal root (e.g., themes/custom/your_theme/src/assets/images/icons)'),
            '#required' => true,
            '#ajax' => [
                'callback' => '::updateSvgCount',
                'event' => 'blur',
                'wrapper' => 'svg-count-wrapper',
            ],
        ];

        $form['svg_count_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'svg-count-wrapper'],
        ];

        $form['svg_count_wrapper']['icons_count'] = [
            '#markup' => '<p><strong>' . $this->getSvgCountMessage($svgCount) . '</strong></p>',
        ];

        return parent::buildForm($form, $form_state);
    }

    public function updateSvgCount(array &$form, FormStateInterface $form_state) {
        return $form['svg_count_wrapper'];
    }

    private function getSvgCountMessage($count): string {
        return match (true) {
            $count <= 0 => $this->t('No icons found in this folder.'),
            $count == 1 => $this->t('1 icon found in this folder.'),
            $count > 1 => $this->t('@count icons found in this folder.', ['@count' => $count]),
        };
    }

    private function countSvgInFolder(string $path): int {
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

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('svg_injector.settings')
            ->set('icon_path', $form_state->getValue('icon_path'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
