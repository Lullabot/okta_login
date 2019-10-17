<?php

namespace Drupal\okta_login\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller class to manage the Okta authentication workflow routes.
 */
class SigninController extends ControllerBase {

  public function signin() {
    $config = $this->config('okta_login.settings');
    return [
      '#theme' => 'okta_login_signin_widget',
      '#container_id' => $config->get('sign_in_container_id'),
      '#attached' => [
        'library' => ['okta_login/okta_auth'],
        'drupalSettings' => [
          'okta_login' => [
            'baseUrl' => $config->get('okta_org_url'),
            'clientId' => $config->get('okta_client_id'),
            'issuer' => $config->get('okta_auth_issuer'),
            'container_id' => $config->get('sign_in_container_id'),
            'authenticate_url' => Url::fromRoute('okta_login.authenticated')->setAbsolute()->toString(),
          ],
        ],
      ],
    ];
  }

  public function signout() {
    return [
      '#theme' => 'okta_login_signout',
      '#attached' => [
        'library' => ['okta_login/okta_signout']
      ],
    ];
  }

  public function authenticate() {
    // Redirect back to Okta Sign in page, unless authentication goes well.
    $redirect_url = Url::fromRoute('okta_login.signin_widget')->setAbsolute()->toString();

    if ($token = $_GET['token']) {
      $config = $this->config('okta_login.settings');

      /** @var \Drupal\okta_login\UserAuthenticator $auth */
      $auth = \Drupal::service('okta_login.user_authenticator');

      if ($auth->verifyOktaToken($token, $config->get('okta_client_id'), $config->get('okta_org_url') . $config->get('okta_auth_issuer'))) {
        $email = $auth->getEmail();
        if ($auth->logInDrupal($email)) {
          // Redirect to URL set in config if valid or user page by default.
          if (!empty($config->get('redirect_url')) && UrlHelper::isValid($config->get('redirect_url'), $absolute = FALSE)) {
            $redirect_url = $config->get('redirect_url');
          } else {
            $redirect_url = Url::fromRoute('user.page')->setAbsolute()->toString();
          }
        } else {
          $this->messenger()->addMessage($this->t('Your Okta account could not be authenticated in Drupal. Please try again or contact your administrator.'), MessengerInterface::TYPE_ERROR);
        }
      } else {
        $this->messenger()->addMessage($this->t('Your Okta authentication is not valid. Please try again or contact your administrator.'), MessengerInterface::TYPE_ERROR);
      }
    }

    return new RedirectResponse($redirect_url);
  }

}
