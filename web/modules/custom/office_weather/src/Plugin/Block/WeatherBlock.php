<?php

namespace Drupal\office_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a Weather API Block.
 *
 * @Block(
 *   id = "office_weather_block",
 *   admin_label = @Translation("Office Weather Block")
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Guzzle HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new WeatherBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing plugin configuration.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    try {
      // Get API key from settings.php
      $apiKey = \Drupal::service('settings')->get('office_weather_api_key');
      if (!$apiKey) {
        return [
          '#markup' => $this->t('Error: API key is missing in settings.php.'),
        ];
      }

      $location = 'Jaipur, IN';
      $response = $this->httpClient->get("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/" . urlencode($location) . "?key=" . $apiKey);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      return [
        '#markup' => $this->t('Current temperature in @city: @temp °C', [
          '@city' => $location,
          '@temp' => $data['currentConditions']['temp'] ?? 'N/A',
          '#cache' => ['max-age' => 21600],
        ]),
      ];

    } catch (\Exception $e) {
      return [
        '#markup' => $this->t('Error fetching weather: @message', [
          '@message' => $e->getMessage(),
        ]),
      ];
    }
  }

}
