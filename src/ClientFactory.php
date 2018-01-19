<?php

namespace Drupal\media_webdam;

use cweagans\webdam\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class ClientFactory.
 *
 * @package Drupal\media_webdam
 */
class ClientFactory {

  /**
   * A config object to retrieve Webdam auth information from.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A fully-configured Guzzle client to pass to the dam client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $gclient;

  /**
   * A user data object to retrieve API keys from.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ClientFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config object to retrieve Webdam auth information from.
   * @param \cweagans\webdam\ClientInterface $gclient
   *   A fully configured Guzzle client to pass to the dam client.
   * @param \Drupal\user\UserDataInterface $user_data
   *   A userdata object to retreive user-specific creds from.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The currently authenticated user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $gclient, UserDataInterface $user_data, AccountProxyInterface $currentUser) {
    $this->config = $config_factory->get('media_webdam.settings');
    $this->client = $gclient;
    $this->userData = $user_data;
    $this->currentUser = $currentUser;
  }

  /**
   * Creates a new DAM client object.
   *
   * @param string $credentials
   *   The switch for which credentials the client object
   *   should be configured with.
   *
   * @return \cweagans\webdam\Client
   *   A configured DAM HTTP client object.
   */
  public function get($credentials = 'background') {
    $client = new Client(
      $this->client,
      $this->config->get('username'),
      $this->config->get('password'),
      $this->config->get('client_id'),
      $this->config->get('secret')
    );

    // Set the user's credentials in the client if necessary.
    if ($credentials == 'current') {
      $access_token = $this->userData->get('media_webdam', $this->currentUser->id(), 'webdam_access_token');
      $access_token_expiration = $this->userData->get('media_webdam', $this->currentUser->id(), 'webdam_access_token_expiration');
      $client->setToken($access_token, $access_token_expiration);
    }

    return $client;
  }

}
