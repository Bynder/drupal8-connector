<?php

namespace Drupal\media_webdam\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates Webdam assets.
 *
 * @QueueWorker (
 *   id = "media_webdam_asset_refresh",
 *   title = @Translation("Webdam Asset Refresh"),
 *   cron = {"time" = 10}
 * )
 */
class AssetRefresh extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\media\Entity\Media $entity */
    $entity = \Drupal::entityTypeManager()->getStorage('media')->load($data['id']);

    /** @var \Drupal\media\Entity\MediaType $bundle */
    $bundle = \Drupal::entityTypeManager()->getStorage('media_type')->load($entity->bundle());

    foreach ($bundle->getFieldMap() as $entity_field => $mapped_field) {
      // Set all mapped field values to NULL so that they are repopulated from Webdam on save.
      if ($entity->hasField($mapped_field)) {
        $entity->set($mapped_field, NULL);
      }
    }

    // Set flag for thumbnail to be regenerated.
    $entity->updateQueuedThumbnail();

    // Save the entity to repopulate mapped fields.
    $entity->save();
  }

}
