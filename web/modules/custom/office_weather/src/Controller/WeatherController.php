<?php

namespace Drupal\office_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WeatherController.
 */
class WeatherController extends ControllerBase
{

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Inject the HTTP client.
     */
    public function __construct(ClientInterface $http_client)
    {
        $this->httpClient = $http_client;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('http_client')
        );
    }

    /**
     * Simple API test to verify weather API connectivity.
     */
    public function forcasting()
    {
        try {
            // Replace YOUR_API_KEY and LOCATION with real values.
            $apiKey = 'LZ6NZ6X2EVXDKBXNHSU9TSLH7';
            $location = 'Jaipur, IN';

            // Make API request
            $response = $this->httpClient->get("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/" . urlencode($location) . "?key=" . $apiKey);
            $data = json_decode($response->getBody()->getContents(), TRUE);

            // Extract weather details safely
            $city = $location;
            $temperature = $data['currentConditions']['temp'] ?? 'N/A';
            $humidity = $data['currentConditions']['humidity'] ?? 'N/A';
            $windSpeed = $data['currentConditions']['windspeed'] ?? 'N/A';
            $conditions = $data['currentConditions']['conditions'] ?? 'N/A';

            // Return data as a Drupal renderable table
            return [
                'title' => [
                    '#markup' => '<h2>' . $this->t('Current Weather Forecast') . '</h2>',
                ],
                'table' => [
                    '#type' => 'table',
                    '#header' => [
                        $this->t(string: 'City'),
                        $this->t('Temperature (°C)'),
                        $this->t('Humidity (%)'),
                        $this->t('Wind Speed (km/h)'),
                        $this->t('Conditions'),
                    ],
                    '#rows' => [
                        [
                            ['data' => $city],
                            ['data' => $temperature],
                            ['data' => $humidity],
                            ['data' => $windSpeed],
                            ['data' => $conditions],
                        ],
                    ],
                    '#attributes' => [
                        'border' => 1,
                        'cellpadding' => 5,
                        'cellspacing' => 0,
                    ],
                    '#empty' => $this->t('No weather data available.'),
                ],
            ];
        } catch (\Exception $e) {
            // Handle and display any errors
            return [
                '#markup' => $this->t('<strong>Error:</strong> @message', ['@message' => $e->getMessage()]),
            ];
        }
    }
}
