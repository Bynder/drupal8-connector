<?php

namespace Drupal\Tests\media_webdam\unit;

use cweagans\webdam\Entity\Asset;
use cweagans\webdam\Entity\MiniFolder;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\media\Entity\Media;
use Drupal\media_webdam\Plugin\media\Source\WebdamAsset;
use Drupal\media_webdam\Webdam;
use Drupal\Tests\UnitTestCase;

/**
 * Asset plugin test.
 *
 * @group media_webdam
 */
class WebdamAssetPluginTest extends UnitTestCase {

  /**
   * Tests the buildConfigurationForm method.
   */
  public function testBuildConfigurationForm() {
    // $field = $this->getMockBuilder(FieldStorageDefinition::class)
    //      ->disableOriginalConstructor()
    //      ->getMock();
    //
    //    $field->method('getType')
    //      ->willReturn($this->returnValue('integer'));
    //
    //    $field->method('getLabel')
    //      ->willReturn($this->returnValue('Test field'));.
    $field_storage = $this->getMockBuilder(FieldStorageDefinition::class)
      ->disableOriginalConstructor()
      ->getMock();
    $field_storage->method('isBaseField')
      ->willReturn(FALSE);

    $field = $this->getMock(FieldDefinitionInterface::class);
    $field->method('getType')
      ->willReturn('integer');
    $field->method('getLabel')
      ->willReturn('Test field');
    $field->method('getFieldStorageDefinition')
      ->willReturn($field_storage);

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_field_manager->method('getFieldDefinitions')
      ->with('media', 'webdam_asset')
      ->willReturn(['test_field' => $field]);

    $entity = new MediaStub([]);

    $form = new FormStub($entity);

    $plugin = new WebdamAsset(
      ['source_field' => 'source'],
      'test_plugin',
      [],
      $this->getMockBuilder(EntityTypeManager::class)->disableOriginalConstructor()->getMock(),
      $entity_field_manager,
      $this->getConfigFactoryStub(['media_entity.settings' => []]),
      new WebdamStub()
    );
    $plugin->setStringTranslation($this->getStringTranslationStub());

    $form_state = new FormState();
    $form_state->setFormObject($form);

    $form_array = $plugin->buildConfigurationForm([], $form_state);

    $this->assertArrayHasKey('source_field', $form_array);
    $this->assertArrayHasKey('#options', $form_array['source_field']);
    $this->assertArrayHasKey('test_field', $form_array['source_field']['#options']);
  }

  /**
   * Tests the getField method.
   */
  public function testGetField() {
    $plugin = new WebdamAsset(
      ['source_field' => 'source'],
      'test_plugin',
      [],
      $this->getMockBuilder(EntityTypeManager::class)->disableOriginalConstructor()->getMock(),
      $this->getMockBuilder(EntityFieldManager::class)->disableOriginalConstructor()->getMock(),
      $this->getConfigFactoryStub(['media_entity.settings' => []]),
      new WebdamStub()
    );

    // If the media entity doesn't have a source field value, then we should get FALSE.
    $media = new MediaStub([]);
    $this->assertFalse($plugin->getField($media, 'asdf'));

    // If the media entity does have a source field, we should get values back.
    $media = new MediaStub(['source' => 'asdf']);
    $this->assertEquals('testfile.jpg', $plugin->getField($media, 'filename'));
    $this->assertEquals(800, $plugin->getField($media, 'width'));
    $this->assertEquals(600, $plugin->getField($media, 'height'));
    $this->assertEquals(12345, $plugin->getField($media, 'folderID'));

    // If we request an unknown field, return FALSE.
    $this->assertFalse($plugin->getField($media, 'some_bogus_field'));
  }

}

/**
 * Testing stubs.
 */
class FieldDefinitionStub extends BaseFieldDefinition {

  /**
   *
   */
  public function __construct() {}

  /**
   *
   */
  public function mainPropertyName() {
    return 'value';
  }

}
/**
 *
 */
class ItemListStub extends ItemList {
  public $value;

  /**
   *
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   *
   */
  public function first() {
    return new FieldDefinitionStub();
  }

}
/**
 *
 */
class WebdamStub extends Webdam {

  /**
   *
   */
  public function __construct() {}

  /**
   *
   */
  public function getAsset($assetId) {
    $asset = new Asset();
    $asset->filename = "testfile.jpg";
    $asset->width = 800;
    $asset->height = 600;
    $asset->folder = new MiniFolder();
    $asset->folder->id = 12345;
    return $asset;
  }

}
/**
 *
 */
class FormStub implements FormInterface {
  protected $entity;

  /**
   *
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   *
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   *
   */
  public function getFormId() {}

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {}

  /**
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
/**
 *
 */
class MediaStub extends Media {
  protected $properties = [];
  protected $sourceField;

  /**
   *
   */
  public function __construct(array $properties) {
    $this->properties = $properties;
    $source = isset($properties['source']) ? $properties['source'] : NULL;
    $this->sourceField = new ItemListStub($source);
  }

  /**
   *
   */
  public function hasField($name) {
    if ($name == 'source') {
      return TRUE;
    }
    return isset($properties[$name]);
  }

  /**
   *
   */
  public function id() {
    return 'webdam_asset';
  }

  /**
   *
   */
  public function &__get($name) {
    if ($name == 'source') {
      return $this->sourceField;
    }
    return $this->properties[$name];
  }

}
