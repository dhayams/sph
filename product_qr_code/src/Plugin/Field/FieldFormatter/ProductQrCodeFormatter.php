<?php

namespace Drupal\product_qr_code\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'QR link' formatter.
 *
 * @FieldFormatter(
 *   id = "link_qr",
 *   label = @Translation("QR code"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class ProductQrCodeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('path.validator')
    );
  }

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'qr_width' => '4',
      'qr_height' => '4',
      'qr_color' => 'Black',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['qr_width'] = [
      '#type' => 'number',
      '#title' => t('Width of a single rectangle element in pixels'),
      '#default_value' => $this->getSetting('qr_width'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $elements['qr_height'] = [
      '#type' => 'number',
      '#title' => t('Height of a single rectangle element in pixels'),
      '#default_value' => $this->getSetting('qr_height'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $elements['qr_color'] = [
      '#title' => t('Color'),
      '#type' => 'select',
      '#description' => 'Select color for QR code',
      '#default_value' => $this->getSetting('qr_color'),
      '#options' => [
        'Black' => t('Black'),
        'Blue' => t('Blue'),
        'Red' => t('Red'),
        'Cyan' => t('Cyan'),
      ],
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    $summary[] = t('Width of a single rectangle element in pixels @width', ['@width' => $settings['qr_width']]);
    $summary[] = t('Height of a single rectangle element in pixels @height', ['@height' => $settings['qr_height']]);
    $summary[] = t('Color of QR code @color', ['@color' => $settings['qr_color']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      $entity = $items->getEntity();

      $url = $item->getUrl() ?: Url::fromRoute('<none>');

      if ($this->pathValidator->isValid($url->getUri()) && class_exists('TCPDF2DBarcode')) {

        // Set the barcode content and type.
        $qr_code = new \TCPDF2DBarcode($url->getUri(), 'QRCODE,H');
        // Store the barcode as HTML object.
        $code = $qr_code->getBarcodeHTML($settings['qr_width'], $settings['qr_height'], $settings['qr_color']);

        $element[$delta] = [
          '#theme' => 'link_formatter_qr_code',
          '#qrcode' => $code,
          '#cache' => [
            'max-age' => 0,
          ],
        ];

      }
    }
    return $element;
  }

}
