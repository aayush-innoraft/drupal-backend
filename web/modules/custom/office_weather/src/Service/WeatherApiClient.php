<?php

namespace Drupal\office_weather\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service to fetch weather data from Visual Crossing API.
 */
class WeatherApiClient {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var string|null
   */
  protected ?string $apiKey;

  /**
   * Constructs a WeatherApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to retrieve site settings.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger for error and debug logging.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger,
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    // Load only the API key from settings.php.
    $this->apiKey = $this->configFactory->get('system.site')->get('office_weather_api_key') ?? NULL;
  }

  /**
   * Fetches current weather for a given location string.
   *
   * @param string $location
   *   The location string, e.g., "Jaipur,IN" or "26.9124,75.7873".
   *
   * @return array|null
   *   An array with 'temp' and 'conditions' or NULL on failure.
   */
  public function fetchByLocation(string $location): ?array {
    if (!$this->apiKey) {
      $this->logger->error('Visual Crossing API key is not configured in settings.php.');
      return NULL;
    }

    // Build API endpoint.
    $endpoint = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/' . urlencode($location);

    // Query parameters for the request.
    $options = [
      'query' => [
        'key' => $this->apiKey,
    // Change to 'us' for Fahrenheit.
        'unitGroup' => 'metric',
        'contentType' => 'json',
      ],
      // Optional: set timeout to avoid hanging requests.
      'timeout' => 10,
    ];

    try {
      // Make the API call using request() for interface compatibility.
      $response = $this->httpClient->request('GET', $endpoint, $options);
      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Debug logging (uncomment if needed).
      // $this->logger->debug('Weather API response for @location: @data', [
      //   '@location' => $location,
      //   '@data' => print_r($data, TRUE),
      // ]);.
      // Parse and return current weather conditions.
      if (!empty($data['currentConditions']['temp']) && !empty($data['currentConditions']['conditions'])) {
        return [
          'temp' => $data['currentConditions']['temp'],
          'conditions' => $data['currentConditions']['conditions'],
        ];
      }

      // Log if response structure is unexpected.
      $this->logger->warning('Unexpected API response structure for location: @location', [
        '@location' => $location,
      ]);
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to fetch weather for @location: @message', [
        '@location' => $location,
        '@message' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}
