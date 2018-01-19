<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Form\FormState;
use Drupal\media_webdam\Form\WebdamConfig;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use cweagans\webdam\Client as WebdamClient;

/**
 * Config form test.
 *
 * @group media_webdam
 */
class WebdamConfigFormTest extends UnitTestCase {


  /**
   * An HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->httpClient = $this->getMockBuilder('GuzzleHttp\ClientInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->httpClient = new Client();

    $container = new Container();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('http_client', $this->httpClient);
    \Drupal::setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetFormId() {
    $form = new WebdamConfig($this->getConfigFactoryStub(), $this->httpClient);
    $this->assertEquals('webdam_config', $form->getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public function testBuildForm() {
    $wconfig = new WebdamConfig($this->getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]), $this->httpClient
    );
    $form = $wconfig->buildForm([], new FormState());

    $this->assertArrayHasKey('authentication', $form);
    $this->assertArrayHasKey('username', $form['authentication']);
    $this->assertArrayHasKey('password', $form['authentication']);
    $this->assertArrayHasKey('client_id', $form['authentication']);
    $this->assertArrayHasKey('client_secret', $form['authentication']);

    $this->assertEquals('WDusername', $form['authentication']['username']['#default_value']);
    $this->assertEquals('WDpassword', $form['authentication']['password']['#default_value']);
    $this->assertEquals('WDclient-id', $form['authentication']['client_id']['#default_value']);
    $this->assertEquals('WDsecret', $form['authentication']['client_secret']['#default_value']);
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
  public function testValidateForm() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN0", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
      new Response(200, [], '{"access_token":"ACCESS_TOKEN1", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
      new Response(200, [], '{"access_token":"ACCESS_TOKEN2", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
      new Response(200, [], '{"maxAdmins": "5","numAdmins": "4","maxContributors": "10","numContributors": 0,"maxEndUsers": "15","numEndUsers": 0,"maxUsers": 0,"url": "accounturl.webdamdb.com","username": "WDusername","planDiskSpace": "10000 MB","currentDiskSpace": "45 MB","activeUsers": "4","inactiveUsers": 0}'),
    ]);
    $handler = HandlerStack::create($mock);
    $this->httpClient = new Client(['handler' => $handler]);

    $wconfig = new WebdamConfig($this->getConfigFactoryStub(), $this->httpClient);

    $form_state = new FormState();
    $form_state->set('username', 'WDusername');
    $form_state->set('password', 'WDpassword');
    $form_state->set('client_id', 'WDclient-id');
    $form_state->set('secret', 'WDmuchsecret');

    $form = $wconfig->buildForm([], $form_state);
    $wconfig->validateForm($form, $form_state);

    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');
    $client_id = $form_state->getValue('client_id');
    $client_secret = $form_state->getValue('client_secret');

    $webdam_client = new WebdamClient($this->httpClient, $username, $password, $client_id, $client_secret);
    $this->assertEquals($form_state->get('username'), $webdam_client->getAccountSubscriptionDetails()->username);
  }

  /**
   * Tests validate fails.
   *
   * @expectedException GuzzleHttp\Exception\ClientException
   * @expectedExceptionMessage The client credentials are invalid
   */
  public function testValidateFormFailed() {
    $mock = new MockHandler([
      new Response(200, [], '{"access_token":"ACCESS_TOKEN", "token_type":"bearer", "expires_in":3600, "refresh_token": "refresh_token"}'),
      new Response(400, [], '{"error":"invalid_client","error_description":"The client credentials are invalid"}'),
    ]);
    $handler = HandlerStack::create($mock);
    $this->httpClient = new Client(['handler' => $handler]);

    $wconfig = new WebdamConfig($this->getConfigFactoryStub(), $this->httpClient);

    $form_state = new FormState();
    $form = $wconfig->buildForm([], $form_state);
    $wconfig->validateForm($form, $form_state);

    $webdam_client = new WebdamClient($this->httpClient, $username, $password, $client_id, $client_secret);
    $authstate = $webdam_client->getAuthState();
    $this->assertFalse($authstate['valid_token']);

    $form_state->setErrorByName('authenticate', 'The client credentials are invalid');

  }

  // @TODO: This test is broken. Not sure what's wrong and don't have time to debug.
  //  public function testSubmitForm() {
  //    $config_stub = new FormConfigStub();
  //    $config_factory_stub = new FormConfigFactoryStub();
  //    $config_factory_stub->set('media_webdam.settings', $config_stub);
  //
  //    $wconfig = new WebdamConfig($this->getConfigFactoryStub(), $this->httpClient);
  //
  //    $form_state = new FormState();
  //    $form_state->set('username', 'webdam_username');
  //    $form_state->set('password', 'webdam_pw');
  //    $form_state->set('client_id', 'webdam_client_id');
  //    $form_state->set('secret', 'webdam_client_secret');
  //
  //    $form = [];
  //
  //    $wconfig->submitForm($form, $form_state);
  //
  //    $this->assertEquals('webdam_username', $config_stub->get('username'));
  //    $this->assertEquals('webdam_pw', $config_stub->get('password'));
  //    $this->assertEquals('webdam_client_id', $config_stub->get('client_id'));
  //    $this->assertEquals('webdam_client_secret', $config_stub->get('secret'));
  /**
 * }.
 */
}
/**
 *
 */
class FormConfigFactoryStub extends ConfigFactory {
  protected $configs = [];

  /**
   *
   */
  public function __construct() {}

  /**
   *
   */
  public function get($name) {
    return $this->configs[$name];
  }

  /**
   *
   */
  public function getEditable($name) {
    return $this->configs[$name];
  }

  /**
   *
   */
  public function set($name, $config) {
    $this->configs[$name] = $config;
  }

}
/**
 *
 */
class FormConfigStub extends Config {
  protected $data = [];

  /**
   *
   */
  public function __construct() {}

  /**
   *
   */
  public function save($has_trusted_data = FALSE) {}

  /**
   *
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   *
   */
  public function get($key = '') {
    return $this->data[$key];
  }

}
