<?php

namespace Drupal\media_webdam;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use GuzzleHttp\ClientInterface;

/**
 * OAuth Class.
 */
class Oauth implements OauthInterface {

  /**
   * The base URL to use for the DAM API.
   *
   * @var string
   */
  protected $damApiBase = "https://apiv2.webdamdb.com";

  /**
   * The media_webdam configuration.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * A URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Destination URI after authentication is completed.
   *
   * @var string
   */
  protected $authFinishRedirect;

  /**
   * Oauth constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The CSRF Token generator.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP guzzle Client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CsrfTokenGenerator $csrfTokenGenerator, UrlGeneratorInterface $urlGenerator, ClientInterface $httpClient) {
    $this->config = $config_factory->get('media_webdam.settings');
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->urlGenerator = $urlGenerator;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthLink() {
    $client_id = $this->config->get('client_id');
    $token = $this->csrfTokenGenerator->get('media_webdam.oauth');
    $redirect_uri = $this->urlGenerator->generateFromRoute('media_webdam.auth_finish', ['auth_finish_redirect' => $this->authFinishRedirect], ['absolute' => TRUE]);

    return "{$this->damApiBase}/oauth2/authorize?response_type=code&state={$token}&redirect_uri={$redirect_uri}&client_id={$client_id}";
  }

  /**
   * {@inheritdoc}
   */
  public function authRequestStateIsValid($token) {
    return $this->csrfTokenGenerator->validate($token, 'media_webdam.oauth');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($auth_code) {
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $response = $this->httpClient->post("{$this->damApiBase}/oauth2/token", [
      'form_params' => [
        'grant_type' => 'authorization_code',
        'code' => $auth_code,
        'redirect_uri' => $this->urlGenerator->generateFromRoute('media_webdam.auth_finish', ['auth_finish_redirect' => $this->authFinishRedirect], ['absolute' => TRUE]),
        'client_id' => $this->config->get('client_id'),
        'client_secret' => $this->config->get('secret'),
      ],
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body);

    return [
      'access_token' => $body->access_token,
      'expire_time' => time() + $body->expires_in,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthFinishRedirect($authFinishRedirect) {
    // TODO: sanitize and validate $redirect_uri.
    $this->authFinishRedirect = $authFinishRedirect;
  }

  /**
   * Gets the auth_finish_redirect url.
   *
   * @return mixed
   *   Url string if is set, null if not set.
   */
  public function getAuthFinishRedirect() {
    if (isset($this->authFinishRedirect)) {
      return $this->authFinishRedirect;
    }
    else {
      return NULL;
    }
  }

}
