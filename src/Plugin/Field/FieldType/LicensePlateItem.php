<?php

namespace Drupal\license_plate\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
* Plugin implementation of the 'license_plate' field type.
*/
#[FieldType(
  id: "license_plate",
  label: new TranslatableMarkup("License plate"),
  description: new TranslatableMarkup("Field for storing license plates"),
  default_widget: "default_license_plate_widget",
  default_formatter: "default_license_plate_formatter",
)]
class LicensePlateItem extends FieldItemBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
        'number_max_length' => 255,
        'code_max_length' => 5,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $elements = [];
    $elements['number_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Plate number maximum length'),
      '#default_value' => $this->getSetting
      ('number_max_length'),
      '#required' => TRUE,
      '#description' => $this->t('Maximum length for the plate number in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];
    $elements['code_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Plate code maximum length'),
      '#default_value' => $this->getSetting
      ('code_max_length'),
      '#required' => TRUE,
      '#description' => $this->t('Maximum length for the plate code in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];
    return $elements + parent::storageSettingsForm($form,
        $form_state, $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'number' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting
          ('number_max_length'),
        ],
        'code' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting
          ('code_max_length'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['number'] = DataDefinition::create('string')
      ->setLabel(t('Plate number'));
    $properties['code'] = DataDefinition::create('string')
      ->setLabel(t('Plate code'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->
    getValidationConstraintManager();
    $number_max_length = $this->getSetting
    ('number_max_length');
    $code_max_length = $this->getSetting('code_max_length');
    $constraints[] = $constraint_manager->create
    ('ComplexData', [
      'number' => [
        'Length' => [
          'max' => $number_max_length,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
            '%name' => $this->getFieldDefinition()->
              getLabel() . ' (number)',
            '@max' => $number_max_length
          ]),
        ],
      ],
      'code' => [
        'Length' => [
          'max' => $code_max_length,
          'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
            '%name' => $this->getFieldDefinition()->
              getLabel() . ' (code)',
            '@max' => $code_max_length
          ]),
        ],
      ],
    ]);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $random = new Random();
    $values['number'] = $random->word(mt_rand(1,
      $field_definition->getSetting('number_max_length')));
    $values['code'] = $random->word(mt_rand(1,
      $field_definition->getSetting('code_max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   * @throws MissingDataException
   */
  public function isEmpty(): bool {
    // We consider the field empty if either of the properties is left empty.
    $number = $this->get('number')->getValue();
    $code = $this->get('code')->getValue();
    return $number === NULL || $number === '' || $code ===
      NULL || $code === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
        'codes' => '',
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form,
                                    FormStateInterface $form_state): array {
    $element = [];
    $element['codes'] = [
      '#title' => $this->t('License plate codes'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('codes'),
      '#description' => $this->t('If you want the field to be
        have a select list with license plate codes instead
        of a textfield, please provide the available codes.
        Each code on a new line.')
    ];
    return $element;
  }
}
