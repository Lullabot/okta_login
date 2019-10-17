<?php

namespace Drupal\okta_login;

use Drupal\user\Entity\User;

/**
 * Class UserAuthenticator to implement Okta auth validation and Drupal log in.
 */
class UserAuthenticator {

  /** @var string */
  protected $email = '';

  /** @var User */
  protected $user;

  /**
   * Returns the Okta signed in user email.
   *
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Returns the Drupal user with the Okta signed in email.
   *
   * @return User
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Verifies an Okta authentication token.
   *
   * @param string $token
   * @param string $client_id
   * @param string $issuer
   * @return bool
   *
   * @see https://github.com/okta/okta-jwt-verifier-php
   */
  public function verifyOktaToken($token, $client_id, $issuer) {
    try {
      $jwtVerifier = (new \Okta\JwtVerifier\JwtVerifierBuilder())
        ->setDiscovery(new \Okta\JwtVerifier\Discovery\Oauth)
        ->setAdaptor(new \Okta\JwtVerifier\Adaptors\SpomkyLabsJose)
        ->setAudience('api://default')
        ->setClientId($client_id)
        ->setIssuer($issuer)
        ->build();

      $jwt = $jwtVerifier->verify($token);
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
   * @param $email
   * @return bool
   */
  public function logInDrupal($email) {
    $account = user_load_by_mail($email);

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
   * @param $email
   * @return bool
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
      $account = User::create($new_user);
      $account->save();
      $this->user = $account;
      return TRUE;
    }
    catch (\Throwable $error) {
      return FALSE;
    }
  }
}