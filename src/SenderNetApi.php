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
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

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
    ConfigFactoryInterface $config_factory
  ) {
    $this->messenger = $messenger;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->client = $client;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->getEditable('sender_net.settings');
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
      $this->logger->get('sender_net')->info("@email email is subscribed.", ['@email' => $param['email']]);
      return TRUE;
    }

    return FALSE;
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
    return $response && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
  }

  /**
   * Get the header of the API call.
   *
   * @see https://api.sender.net/authentication/
   *
   * @return array|null
   *   An array containing API headers or NULL if API settings are not set.
   */
  public function getApiHeader() {
    // Get the value of the config variable `api_access_tokens`.
    $token = $this->config->get('api_access_tokens');

    return [
      'Authorization' => 'Bearer ' . $token,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ];
  }

  /**
   * Test the validity of the API key by making a request to a sample endpoint.
   *
   * @throws \Exception
   */
  public function checkApiKey($api_key) {
    // Make a request to a sample endpoint to test the API key.
    $response = $this->makeApiRequest('campaigns', 'GET', [], [
      'Authorization' => 'Bearer ' . $api_key,
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    ]);

    // Check if the response indicates an authentication issue.
    if ($response && $response->getStatusCode() === 200) {
      return TRUE;
    }
    else {
      return FALSE;
    }
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
   * @param array $headers
   *   An array of headers to include in the request.
   *
   * @return \Psr\Http\Message\ResponseInterface|null
   *   The API response object or NULL if there's an issue.
   */
  protected function makeApiRequest($endpoint, $method, $data, $headers) {
    $base_url = $this->config->get('api_base_url');
    $url = $base_url . $endpoint;

    try {
      return $this->client->request($method, $url, ['headers' => $headers, 'json' => $data]);
    }
    catch (\Throwable $th) {
      $this->addError($th->getMessage());
      $this->logger->get('sender_net')->error($th->getMessage());
      return NULL;
    }
  }

  /**
   * Helper function to add error messages to the messenger.
   *
   * @param string $message
   *   The error message to display.
   */
  protected function addError($message) {
    $this->messenger->addError($message);
  }

}
