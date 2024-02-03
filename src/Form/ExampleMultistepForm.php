<?php

namespace Drupal\example_multistep_form\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ExampleMultistepForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'example_multistep_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $step = $form_state->get('step');
    if (!$step) {
      $step = 1;
      $form_state->set('step', $step);
      $form_state->set('steps_values', []);
    }
    $is_last_step = !method_exists($this, 'buildStep' . ($step + 1));

    $form = $this->{'buildStep' . $step}($form, $form_state);

    $form['actions'] = [
      '#type' => 'actions',
    ];

    if ($step > 1) {
      $form['actions']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Prev'),
        '#name' => 'prev',
        '#submit' => ['::stepNavigation'],
        // Disable validation
        '#limit_validation_errors' => [],
      ];
    }
    if (!$is_last_step) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#name' => 'next',
        '#submit' => ['::stepNavigation'],
      ];
    }
    if ($is_last_step) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    }

    return $form;
  }

  /**
   * Step 1 form.
   */
  public function buildStep1(array $form, FormStateInterface $form_state): array {
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#default_value' => $form_state->getValue('first_name'),
    ];

    return $form;
  }

  /**
   * Step 2 form.
   */
  public function buildStep2(array $form, FormStateInterface $form_state): array {
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#default_value' => $form_state->getValue('last_name'),
      '#required' => TRUE,
    ];

    $form['#attributes']['novalidate'] = TRUE;

    return $form;
  }

  /**
   * Step 3 form.
   */
  public function buildStep3(array $form, FormStateInterface $form_state): array {
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => $form_state->getValue('phone'),
    ];

    return $form;
  }

  /**
   * Prev/Next button submit callback.
   */
  public function stepNavigation(array $form, FormStateInterface $form_state): void {
    $triggering_button_name = $form_state->getTriggeringElement()['#name'];
    $step = $form_state->get('step');
    $steps_values = $form_state->get('steps_values');

    // Change step
    $form_state->set('step', $triggering_button_name == 'next' ? $step + 1 : $step - 1);

    // Store current values to all-steps values
    if ($triggering_button_name == 'next') {
      $steps_values = NestedArray::mergeDeep($steps_values, $form_state->cleanValues()->getValues());
      $form_state->set('steps_values', $steps_values);
    }

    // Copy all-steps values to current values, so that they are available in buildForm
    $form_state->setValues($steps_values);

    // Disable form reload (redirect)
    $form_state->setRebuild(TRUE);

    // Disable deletion form cache.
    // Fix situation when user click "next", drupal generate next step form and delete previous form state cache,
    // user press F5 in browser, browser send form_build_id which does not exist in database.
    $form_state->setCached(FALSE);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = NestedArray::mergeDeep($form_state->get('steps_values'), $form_state->cleanValues()->getValues());
    dsm($values);
  }

}
