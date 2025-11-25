<?php

namespace Drupal\svg_injector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SvgInjectorSettingsForm extends ConfigFormBase {

    protected function getEditableConfigNames() {
        return ['svg_injector.settings'];
    }

    public function getFormId() {
        return 'svg_injector_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('svg_injector.settings');

        $form['icon_path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path to SVG icons'),
            '#default_value' => $config->get('icon_path'),
            '#description' => $this->t('Relative path from Drupal root (e.g., themes/custom/your_theme/src/assets/images/icons)'),
            '#required' => true
        ];

        return parent::buildForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->config('svg_injector.settings')
            ->set('icon_path', $form_state->getValue('icon_path'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
