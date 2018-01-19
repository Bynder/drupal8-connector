<?php

namespace Drupal\Tests\media_webdam\unit;

use Drupal\Core\Session\AccountProxy;
use Drupal\media_webdam\ClientFactory;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserDataInterface;
use GuzzleHttp\Client as GClient;

/**
 * Client factory test.
 *
 * @group media_webdam
 */
class WebdamClientFactoryTest extends UnitTestCase {

  /**
   *
   */
  public function testFactory() {
    $config_factory = $this->getConfigFactoryStub([
      'media_webdam.settings' => [
        'username' => 'WDusername',
        'password' => 'WDpassword',
        'client_id' => 'WDclient-id',
        'secret' => 'WDsecret',
      ],
    ]);
    $guzzle_client = new GClient();
    $client_factory = new ClientFactory($config_factory, $guzzle_client, $this->getMock(UserDataInterface::class), $this->getMock(AccountProxy::class));

    $client = $client_factory->get('background');

    $this->assertInstanceOf('cweagans\webdam\Client', $client);
  }

}
