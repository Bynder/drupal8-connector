<?php

namespace Drupal\media_webdam;

/**
 * OAuth Interface.
 */
interface OauthInterface {

  /**
   * Get the URL to redirect a user to to start the oauth process.
   *
   * @return string
   *   The URL to redirect to.
   */
  public function getAuthLink();

  /**
   * Validate that the state token in an auth request is valid.
   *
   * @param string $token
   *   The CSRF token from the auth request.
   *
   * @return bool
   *   TRUE if the state is valid. FALSE otherwise.
   */
  public function authRequestStateIsValid($token);

  /**
   * Get a token for API access + the number of seconds till expiration.
   *
   * @param string $auth_code
   *   The authorization token from oauth.
   *
   * @return array
   *   Returns an array with two keys:
   *     - access_token: The access token used for API authorization.
   *     - expire_time: The unix timestamp when the access token expires.
   */
  public function getAccessToken($auth_code);

}
