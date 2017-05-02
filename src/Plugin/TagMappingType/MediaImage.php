<?php

namespace Drupal\thunder_print\Plugin\TagMappingType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\thunder_print\IDMS;
use Drupal\thunder_print\Plugin\TagMappingTypeBase;

/**
 * Provides Tag Mapping for media image reference.
 *
 * @package Drupal\thunder_print\Plugin\TagMappingType
 * @todo Provide generic entity reference handler.
 *
 * @TagMappingType(
 *   id = "media_image",
 *   label = @Translation("Media image"),
 * )
 */
class MediaImage extends TagMappingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $return = [];
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityManager */
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = $entityManager->getFieldDefinitions('media', 'image');
    foreach ($fields as $field) {
      if (!$field->getFieldStorageDefinition()->isBaseField()) {
        $return[$field->getName()] = [
          'required' => $field->isRequired(),
          'name' => $field->getLabel(),
        ];
      }
    }

    return $return;
  }

  /**
   * Checks if the given field is supported for mapping.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field definition from the entity bundle.
   *
   * @return bool
   *   Returns TRUE if mapping is supported.
   */
  protected function isFieldSupported(FieldDefinitionInterface $field) {
    // Do not use base fields for mapping.
    if ($field->getFieldStorageDefinition()->isBaseField()) {
      return FALSE;
    }
    // We only support a limitted set of field types for now.
    if (!in_array($field->getType(), ['string', 'text_long', 'image'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMainProperty() {
    // @todo: maybe provide this as an option, in case there are multiple required fields.
    return 'field_image';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinition() {
    return [
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigDefinition() {
    return [
      'handler' => 'default:media',
      'handler_settings' => [
        'target_bundles' => ['image'],
        'sort' => [
          'field' => '_none',
        ],
        'auto_create' => FALSE,
        'auto_create_bundle' => '',
      ],

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplayDefinition() {
    return [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'image_browser',
        'field_widget_display' => 'rendered_entity',
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'selection_mode' => 'selection_append',
        'field_widget_display_settings' => [
          'view_mode' => 'thumbnail',
        ],
        'open' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function replacePlaceholder(IDMS $idms, $fieldItem) {


    foreach ($this->configuration['mapping'] as $field => $tag) {

      $xpath = "(//XmlStory//XMLElement[@MarkupTag='$tag'])[last()]";
      $xmlElement = $idms->getXml()->xpath($xpath)[0];

      if ($xmlElement) {

        $xmlContentId = (string) $xmlElement['XMLContent'];

        $xpath = "//Image[@Self='$xmlContentId']/Link";
        $xmlImageLink = $idms->getXml()->xpath($xpath)[0];

        $media = Media::load($fieldItem['target_id']);
        $fieldValue = $media->get($field)->first();

        if ($fieldValue) {
          if ($xmlImageLink) {

            /** @var File $file */
            $file = File::load($fieldValue->target_id);

            $xmlElement['Value'] = 'file://' . drupal_realpath($file->getFileUri());
            $xmlImageLink['LinkResourceURI'] = $xmlElement['Value'];

          }
          else {
            $xpath = "(//Story//XMLElement[@MarkupTag='$tag'])[last()]";
            $xmlElement = $idms->getXml()->xpath($xpath)[0];

            $errlevel = error_reporting(E_ALL & ~E_WARNING);

            foreach ($xmlElement->children() as $i => $child) {
              unset($child[0]);
            }
            error_reporting($errlevel);

            $xmlElement->Content = trim(strip_tags($fieldValue->value));
          }

        }



      }

    }



    return $idms;
  }

}
