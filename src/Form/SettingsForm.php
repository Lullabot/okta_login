<?php

namespace Drupal\okta_login\form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form for Okta Login settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'okta_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'okta_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('okta_login.settings');

    $form['okta_org_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Okta Org URL'),
      '#description' => $this->t('The Org URL in your Okta dashboard.'),
      '#default_value' => $config->get('okta_org_url'),
      '#required' => TRUE,
    ];

    $form['okta_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Okta Client ID'),
      '#description' => $this->t('Your Okta application Client ID.'),
      '#default_value' => $config->get('okta_client_id'),
      '#required' => TRUE,
    ];

    $form['redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL after authentication'),
      '#description' => $this->t('The URL to be redirected to after being logged in Drupal with an Okta account, i.e. "/user".'),
      '#default_value' => $config->get('redirect_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('okta_login.settings')
      ->set('okta_org_url', $form_state->getValue('okta_org_url'))
      ->set('okta_client_id', $form_state->getValue('okta_client_id'))
      ->set('sign_in_container_id', $form_state->getValue('sign_in_container_id'))
      ->set('redirect_url', $form_state->getValue('redirect_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
