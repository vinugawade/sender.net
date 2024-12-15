<?php

namespace Drupal\sender_net;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Service description.
 */
class SenderNetApi {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a SenderNetApi object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    MessengerInterface $messenger,
    EntityTypeManagerInterface $entity_type_manager,
    ClientInterface $client,
    LoggerChannelFactoryInterface $logger,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->messenger = $messenger;
    $this->client = $client;
    $this->logger = $logger->get('sender_net');
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('sender_net.settings');
  }

  /**
   * Create new subscriber.
   *
   * @param array $param
   *   An array containing parameters for creating a new subscriber.
   *
   * @return bool
   *   TRUE if the subscriber is created successfully, FALSE otherwise.
   *
   * @see https://api.sender.net/subscribers/add-subscriber/
   *
   * @throws \Exception
   */
  public function createSubscriber(array $param) {
    // Checking if the API settings are set.
    $header = $this->getApiHeader();
    if (empty($header)) {
      throw new \Exception('API settings are not set.');
    }

    // API call to create a new subscriber.
    $response = $this->makeApiRequest('subscribers', 'POST', $param, $header);

    if ($response && $this->isSuccessResponse($response)) {
      $this->logger->info("@name email is subscribed.", ['@name' => $param['email']]);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get subscriber by email.
   *
   * @param string $email
   *   The email address of the subscriber.
   *
   * @return array|null
   *   The subscriber data if found, NULL otherwise.
   */
  public function getSubscriberByEmail($email) {
    // API call to retrieve subscriber by email.
    $header = $this->getApiHeader();
    if (empty($header)) {
      throw new \Exception('API settings are not set.');
    }

    $response = $this->makeApiRequest('subscribers/' . $email, 'GET', [], $header);

    if ($response && $this->isSuccessResponse($response)) {
      // Subscriber found.
      return json_decode($response->getBody()->getContents(), TRUE);
    }

    return NULL;
  }

  /**
   * List all groups.
   *
   * @see https://api.sender.net/groups/list-all/
   *
   * @return \Psr\Http\Message\ResponseInterface|bool
   *   The response result or FALSE if there's an issue.
   *
   * @throws \Exception
   */
  public function listAllGroups() {
    // Checking if the API settings are set.
    $header = $this->getApiHeader();
    if (empty($header)) {
      throw new \Exception('API settings are not set.');
    }

    // API call to return a list of all groups in your account.
    $response = $this->makeApiRequest('groups', 'GET', [], $header);

    if ($response && $this->isSuccessResponse($response)) {
      return $response;
    }

    return FALSE;
  }

  /**
   * Check if the API response indicates success.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The API response object.
   *
   * @return bool
   *   TRUE if the response indicates success, FALSE otherwise.
   */
  protected function isSuccessResponse(ResponseInterface $response) {
    return $response instanceof ResponseInterface
      && $response->getStatusCode() >= 200
      && $response->getStatusCode() < 300;
  }

  /**
   * Get the header of the API call.
   *
   * @see https://api.sender.net/authentication/
   *
   * @return array|null
   *   An array containing API header or NULL if API settings are not set.
   */
  public function getApiHeader() {
    // Get the value of the config variable `api_access_tokens`.
    $api_token = $this->config->get('api_access_tokens');
    if (empty($api_token)) {
      throw new \Exception('API access token is missing in configuration.');
    }

    return [
      'Authorization' => 'Bearer ' . $api_token,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ];
  }

  /**
   * Test the validity of the API key by making a request to a sample endpoint.
   *
   * @param string $api_token
   *   The API key to test.
   *
   * @return bool
   *   TRUE if the key is valid, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function checkApiKey($api_token) {
    // Make a request to a sample endpoint to test the API key.
    $response = $this->makeApiRequest('campaigns', 'GET', [], [
      'Authorization' => 'Bearer ' . $api_token,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ]);

    return $response instanceof ResponseInterface && $response->getStatusCode() === 200;
  }

  /**
   * Helper function to make API requests.
   *
   * @param string $endpoint
   *   The API endpoint.
   * @param string $method
   *   The HTTP method (GET, POST, etc.).
   * @param array $data
   *   An array of data to send in the request body.
   * @param array $header
   *   An array of header to include in the request.
   *
   * @return \Psr\Http\Message\ResponseInterface|null
   *   The API response object or NULL if there's an issue.
   */
  protected function makeApiRequest($endpoint, $method, $data, $header) {
    $base_url = $this->config->get('api_base_url');
    $url = $base_url . $endpoint;

    try {
      return $this->client->request($method, $url, [
        'headers' => $header,
        'json' => $data,
      ]);
    }
    catch (\Throwable $th) {
      watchdog_exception('sender_net', $th);
      $this->logger->error('API request failed: @message', ['@message' => $th->getMessage()]);
      return NULL;
    }
  }

}
