<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\media_webdam\Oauth;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Oauth test.
 *
 * @group media_webdam
 */
class OauthTest extends UnitTestCase {

  /**
   * The base URL to use for the API.
   *
   * @var string
   */
  protected $webdamApiBase = "https://apiv2.webdamdb.comh";

  /**
   * The media_webdam configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $config;

  /**
   * A CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfTokenGenerator;

  /**
   * A URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpClient;

  /**
   * Destination URI after authentication is completed.
   *
   * @var string
   */
  protected $authFinishRedirect;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->config = $this->getConfigFactoryStub()->get('media_webdam.settings');

    $this->csrfTokenGenerator = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $token = 'testToken112233';
    $this->csrfTokenGenerator->expects($this->any())
      ->method('get')
      ->willReturn($token);

    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturn('some/url/test');

    $this->guzzle_client = new GClient();

    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->oAuth = new Oauth($this->getConfigFactoryStub(), $this->csrfTokenGenerator, $this->urlGenerator, $this->guzzle_client);

  }

  /**
   * {@inheritdoc}
   */
  public function getConfigFactoryStub(array $configs = []) {
    return parent::getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetAuthLink() {
    $authUrl = $this->oAuth->getAuthLink();

    $this->assertContains('some/url/test', $authUrl);
    $this->assertContains('testToken112233', $authUrl);
    $this->assertContains('WDclient-id', $authUrl);
    $this->assertContains('/oauth2/authorize', $authUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function testAuthRequestStateIsValid() {
    $token = 'testToken112233';
    $this->csrfTokenGenerator->expects($this->any())
      ->method('validate')
      ->with($token)
      ->willReturn(TRUE);

    $this->oAuth->authRequestStateIsValid($token);
    $this->assertTrue($this->csrfTokenGenerator->validate($token));
  }

  /**
   * {@inheritdoc}
   */
  public function testGetAccessToken($auth_code = '') {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $this->httpClient = new GClient(['handler' => $handler]);

    $auth_code = 'somedummycode123';

    $this->oAuth = new Oauth($this->getConfigFactoryStub(), $this->csrfTokenGenerator, $this->urlGenerator, $this->httpClient);
    $getAccessToken = $this->oAuth->getAccessToken($auth_code);

    $this->assertArrayHasKey('expire_time', $getAccessToken);
    $this->assertArrayHasKey('access_token', $getAccessToken);
    $this->assertNotEmpty($getAccessToken['access_token']);

  }

  /**
   * Tests that auth_finish_redirect is set.
   */
  public function testSetAndGetAuthFinishRedirect() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $this->httpClient = new GClient(['handler' => $handler]);
    $authFinishRedirect = 'https://thelongurl.with.useful?parameters';

    $this->oAuth = new Oauth($this->getConfigFactoryStub(), $this->csrfTokenGenerator, $this->urlGenerator, $this->httpClient);
    $this->oAuth->setAuthFinishRedirect($authFinishRedirect);

    $this->assertSame($authFinishRedirect, $this->oAuth->getAuthFinishRedirect());
  }

  /**
   * Tests that the auth_finish_redirect is not set.
   */
  public function testSetAndGetAuthFinishRedirectNull() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $this->httpClient = new GClient(['handler' => $handler]);

    $this->oAuth = new Oauth($this->getConfigFactoryStub(), $this->csrfTokenGenerator, $this->urlGenerator, $this->httpClient);

    $this->assertSame(NULL, $this->oAuth->getAuthFinishRedirect());
  }

}
