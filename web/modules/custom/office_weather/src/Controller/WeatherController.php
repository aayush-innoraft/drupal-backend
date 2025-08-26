<?php

namespace Drupal\office_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WeatherController.
 */
class WeatherController extends ControllerBase {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Inject the HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Simple API test to verify weather API connectivity.
   */
  public function forcasting() {
    try {
      // Replace YOUR_API_KEY and LOCATION with real values.
      $apiKey = 'LZ6NZ6X2EVXDKBXNHSU9TSLH7';
      $location = 'jaipur , IN';
      $response = $this->httpClient->get("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/" . urlencode($location) . "?key=" . $apiKey);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      return [
        '#markup' => $this->t('API Connected! Current temperature in @city: @temp °C', [
          '@city' => $location,
          '@temp' => $data['currentConditions']['temp'] ?? 'N/A',
        ]),
      ];

    } catch (\Exception $e) {
      return new Response('Error: ' . $e->getMessage(), 500);
    }
  }

}
