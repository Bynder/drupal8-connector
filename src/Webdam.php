<?php

namespace Drupal\media_webdam;

/**
 * Class Webdam.
 *
 * Abstracts away details of the REST API.
 *
 * @package Drupal\media_webdam
 */
class Webdam implements WebdamInterface {

  /**
   * A dam client.
   *
   * @var \cweagans\webdam\Client
   */
  protected $client;

  /**
   * Webdam constructor.
   *
   * @param \Drupal\media_webdam\ClientFactory $client_factory
   *   An instance of ClientFactory that we can get a webdam client from.
   * @param string $credential_type
   *   The type of credentials to use.
   */
  public function __construct(ClientFactory $client_factory, $credential_type) {
    $this->client = $client_factory->get($credential_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlattenedFolderList($folder_id = NULL) {
    $folder_data = [];

    if (is_null($folder_id)) {
      $folders = $this->client->getTopLevelFolders();
    }
    else {
      $folders = $this->client->getFolder($folder_id)->folders;
    }

    foreach ($folders as $folder) {
      $folder_data[$folder->id] = $folder->name;

      $folder_list = $this->getFlattenedFolderList($folder->id);

      foreach ($folder_list as $folder_id => $folder_name) {
        $folder_data[$folder_id] = $folder_name;
      }
    }

    return $folder_data;
  }

  /**
   * {@inheritdoc}
   */
  public function __call($name, $arguments) {
    $method_variable = [$this->client, $name];
    if (is_callable($method_variable)) {
      return call_user_func_array($method_variable, $arguments);
    }
  }

}
