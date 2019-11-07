<?php

namespace Drupal\okta_login\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\okta_login\UserAuthenticator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller class to manage the Okta authentication workflow routes.
 *
 * @see https://developer.okta.com/code/javascript/okta_sign-in_widget/
 */
class AuthController extends ControllerBase {

  /**
   * UserAuthenticator injected service.
   *
   * @var \Drupal\okta_login\UserAuthenticator
   */
  protected $userAuthenticator;

  /**
   * AuthController constructor.
   *
   * @param \Drupal\okta_login\UserAuthenticator $userAuthenticator
   *   The injected service to manage the authentication logic.
   */
  public function __construct(UserAuthenticator $userAuthenticator) {
    $this->userAuthenticator = $userAuthenticator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('okta_login.user_authenticator'));
  }

  /**
   * Prepares the Okta Sign-In widget page.
   *
   * @return array
   *   The renderable array for the response.
   */
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
            'containerId' => $config->get('sign_in_container_id'),
            'authenticateUrl' => Url::fromRoute('okta_login.authenticated')->setAbsolute()->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Prepares the Okta sign out page.
   *
   * @return array
   *   The renderable array for the response.
   */
  public function signout() {
    $config = $this->config('okta_login.settings');
    return [
      '#theme' => 'okta_login_signout',
      '#attached' => [
        'library' => ['okta_login/okta_signout'],
        'drupalSettings' => [
          'okta_login' => [
            'baseUrl' => $config->get('okta_org_url'),
            'logoutRedirectUrl' => Url::fromRoute('user.logout')->setAbsolute()->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Verifies authentication and logs the user in, then redirects.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response redirection object with the URL to go to.
   */
  public function authenticate() {
    // Redirect back to Okta Sign in page, unless authentication goes well.
    $redirect_url = Url::fromRoute('okta_login.signin_widget')->setAbsolute()->toString();

    if (empty($_GET['token'])) {
      return new RedirectResponse($redirect_url);
    }

    $token = $_GET['token'];
    $config = $this->config('okta_login.settings');

    /** @var \Drupal\okta_login\UserAuthenticator $auth */
    if (!$this->userAuthenticator->verifyOktaToken($token, $config->get('okta_client_id'), $config->get('okta_org_url') . $config->get('okta_auth_issuer'))) {
      $this->messenger()->addMessage($this->t('Your Okta authentication is not valid. Please try again or contact your administrator.'), MessengerInterface::TYPE_ERROR);
      return new RedirectResponse($redirect_url);
    }

    $email = $this->userAuthenticator->getEmail();
    if (!$this->userAuthenticator->logInDrupal($email)) {
      $this->messenger()->addMessage($this->t('Your Okta account could not be authenticated in Drupal. Please try again or contact your administrator.'), MessengerInterface::TYPE_ERROR);
      return new RedirectResponse($redirect_url);
    }

    // Redirect to URL set in config if valid or user page by default.
    if (!empty($config->get('redirect_url')) && UrlHelper::isValid($config->get('redirect_url'))) {
      $redirect_url = $config->get('redirect_url');
    }
    else {
      $redirect_url = Url::fromRoute('user.page')->setAbsolute()->toString();
    }

    return new RedirectResponse($redirect_url);
  }

}
