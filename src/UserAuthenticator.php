<?php

namespace Drupal\okta_login;

use Drupal\Core\Entity\EntityTypeManager;
use Okta\JwtVerifier\Adaptors\SpomkyLabsJose;
use Okta\JwtVerifier\Discovery\Oauth;
use Okta\JwtVerifier\JwtVerifierBuilder;

/**
 * Class UserAuthenticator to implement Okta auth validation and Drupal log in.
 */
class UserAuthenticator {

  /**
   * The injected entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Okta signed in user email.
   *
   * @var string
   */
  protected $email = '';

  /**
   * The Drupal user with the Okta signed in email.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Returns the Okta signed in user email after verified.
   *
   * @return string
   *   The Okta signed in user email.
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Returns the Drupal user with the Okta signed in email.
   *
   * @return \Drupal\user\Entity\User
   *   The Drupal user with the Okta signed in email.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * AuthController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The injected service to manage user functionality.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Verifies an Okta authentication token.
   *
   * @param string $token
   *   The token received from Okta authentication to verify.
   * @param string $client_id
   *   The Okta Client ID value for authentication.
   * @param string $issuer
   *   The Okta remote URL for authentication.
   *
   * @return bool
   *   If it has been verified or not.
   *
   * @see https://github.com/okta/okta-jwt-verifier-php
   */
  public function verifyOktaToken($token, $client_id, $issuer) {
    try {
      $jwt_verifier = (new JwtVerifierBuilder())
        ->setDiscovery(new Oauth)
        ->setAdaptor(new SpomkyLabsJose)
        ->setAudience('api://default')
        ->setClientId($client_id)
        ->setIssuer($issuer)
        ->build();

      $jwt = $jwt_verifier->verify($token);
      $this->email = $jwt->getClaims()['sub'];
      return TRUE;
    }
    catch (\Throwable $error) {
      return FALSE;
    }
  }

  /**
   * Logs the user with that email, creating it first if it does not exist.
   *
   * @param string $email
   *   The user email to log in Drupal.
   *
   * @return bool
   *   If login is successful or not.
   */
  public function logInDrupal($email) {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $email]);
    $account = reset($users);

    // Create user if not already present.
    if (!$account) {
      if ($this->createUserAccount($email)) {
        $account = $this->getUser();
      }
    }

    if ($account && !user_is_blocked($account->getUsername())) {
      user_login_finalize($account);
      $this->user = $account;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates an user account in Drupal with the specified email.
   *
   * @param string $email
   *   The email used to create the user as both username and mail.
   *
   * @return bool
   *   If user creation is successful or not.
   */
  public function createUserAccount($email) {
    try {
      $random_password = user_password(16);
      $new_user = [
        'name' => $email,
        'mail' => $email,
        'pass' => $random_password,
        'status' => 1,
      ];
      $account = $this->entityTypeManager->getStorage('user')->create($new_user);
      $account->save();
      $this->user = $account;
      return TRUE;
    }
    catch (\Throwable $error) {
      return FALSE;
    }
  }

}
