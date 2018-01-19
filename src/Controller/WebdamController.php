<?php

namespace Drupal\media_webdam\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media_webdam\WebdamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Webdam routes.
 */
class WebdamController extends ControllerBase {

  /**
   * A configured API object.
   *
   * @var \Drupal\media_webdam\WebdamInterface
   */
  protected $webdam;

  /**
   * The asset that we're going to render details for.
   *
   * @var \cweagans\webdam\Entity\Asset
   */
  protected $asset;

  /**
   * WebdamController constructor.
   *
   * @param \Drupal\media_webdam\WebdamInterface $webdam
   *   The Webdam Interface.
   */
  public function __construct(WebdamInterface $webdam) {
    $this->webdam = $webdam;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_webdam.webdam')
    );
  }

  /**
   * Get an asset.
   *
   * @param int $assetId
   *   The asset ID for the asset to render details for.
   */
  protected function getAsset($assetId) {
    if (!isset($this->asset)) {
      $this->asset = $this->webdam->getAsset($assetId, TRUE);
    }

    return $this->asset;
  }

  /**
   * Sets the asset details page title.
   *
   * @param int $assetId
   *   The asset ID for the asset to render title for.
   */
  public function assetDetailsPageTitle($assetId) {
    $asset = $this->getAsset($assetId);
    return $this->t("Asset details: %filename", ['%filename' => $asset->filename]);
  }

  /**
   * Render a page that includes details about an asset.
   *
   * @param int $assetId
   *   The asset ID to retrieve data for.
   */
  public function assetDetailsPage($assetId) {

    // Get the asset.
    // @TODO: Catch exceptions here and do the right thing.
    $asset = $this->getAsset($assetId);

    $asset_attributes = [
      'base_properties' => [],
      'additional_metadata' => [],
    ];

    $asset_attributes['base_properties']['Asset ID'] = $asset->id;
    $asset_attributes['base_properties']['Status'] = $asset->status;
    $asset_attributes['base_properties']['Filename'] = $asset->filename;
    $asset_attributes['base_properties']['Version'] = $asset->version;
    $asset_attributes['base_properties']['Description'] = $asset->description;
    $asset_attributes['base_properties']['Width'] = $asset->width;
    $asset_attributes['base_properties']['Height'] = $asset->height;
    $asset_attributes['base_properties']['Filetype'] = $asset->filetype;
    $asset_attributes['base_properties']['Color space'] = $asset->colorspace;
    $asset_attributes['base_properties']['Date created'] = $asset->datecreated;
    $asset_attributes['base_properties']['Date modified'] = $asset->datemodified;
    $asset_attributes['base_properties']['Owner'] = $asset->user->name;
    $asset_attributes['base_properties']['Folder'] = $asset->folder->name;

    if (isset($asset->expiration)) {
      $asset_attributes['base_properties']['Expiration Date'] = $asset->expiration->date;
      $asset_attributes['base_properties']['Expiration Notes'] = $asset->expiration->notes;
    }

    if (!empty($asset->xmp_metadata)) {
      foreach ($asset->xmp_metadata as $metadata) {
        $asset_attributes['additional_metadata'][$metadata['label']] = $metadata['value'];
      }
    }

    // Get an asset preview.
    $asset_preview = $asset->thumbnailurls[3]->url;

    // Get subscription details so that we can generate the correct URL to send the user
    // to the DAM UI.
    $subscription_details = $this->webdam->getAccountSubscriptionDetails();
    $dam_url = $subscription_details->url;

    return [
      '#theme' => 'asset_details',
      '#asset_data' => $asset_attributes,
      '#asset_preview' => $asset_preview,
      '#asset_link' => "https://{$dam_url}/cloud/#asset/{$assetId}",
      '#attached' => [
        'library' => [
          'media_webdam/asset_details',
        ],
      ],
    ];
  }

}
