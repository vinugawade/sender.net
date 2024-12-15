<?php

namespace Drupal\sender_net\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sender_net\SenderNetApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure sender.net settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The sender.net API service.
   *
   * @var \Drupal\sender_net\SenderNetApi
   */
  protected $senderApi;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\sender_net\SenderNetApi $senderApi
   *   The sender.net API service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(SenderNetApi $senderApi, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger,) {
    $this->senderApi = $senderApi;
    $this->messenger = $messenger;
    $this->logger = $logger->get('sender_net');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sender_net.api'),
      $container->get('messenger'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sender_net_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sender_net.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $apiKey = $this->config('sender_net.settings')->get('api_access_tokens');
    $group = $this->config('sender_net.settings')->get('user_group');

    $form['api_access_tokens'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter your API access token'),
      '#default_value' => $apiKey,
      '#description' => $this->t('Get API access tokens from sender.net <a href="@url" target="_blank">account</a>.', ['@url' => 'https://app.sender.net/settings/tokens']),
      '#required' => TRUE,
    ];

    $form['api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => 'https://api.sender.net/v2/',
      '#description' => $this->t('Get Base URL from sender.net <a href="@url" target="_blank">docs</a>.', ['@url' => 'https://api.sender.net/#introduction']),
      '#required' => TRUE,
    ];

    $form['user_group'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Groups'),
      '#options' => $this->loadGroupsOptions($apiKey),
      '#default_value' => $group ?: [],
      '#sort_options' => TRUE,
      '#description' => $this->t('List of all <a href="@url" target="_blank">groups</a> in your sender.net account.', ['@url' => 'https://app.sender.net/subscribers/tags']),
      '#required' => FALSE,
      '#multiple' => TRUE,
      '#prefix' => '<div id="user-group-wrapper">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'ajaxUserGroupCallback'],
        'wrapper' => 'user-group-wrapper',
        'method' => 'replace',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get the API access token from the form state.
    $apiKey = $form_state->getValue('api_access_tokens');

    // Check if the API access token is valid.
    if (!$this->senderApi->checkApiKey($apiKey)) {
      // Set a form error if the API access token is not valid.
      $form_state->setErrorByName('api_access_tokens', $this->t('Invalid API access token.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sender_net.settings')
      ->set('api_access_tokens', $form_state->getValue('api_access_tokens'))
      ->set('api_base_url', $form_state->getValue('api_base_url'))
      ->set('user_group', array_filter($form_state->getValue('user_group')))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * AJAX callback for user_group field.
   */
  public function ajaxUserGroupCallback(array &$form, FormStateInterface $form_state) {
    return $form['user_group'];
  }

  /**
   * Load options for the user_group field.
   *
   * @param string $apiKey
   *   The API access token.
   *
   * @return array
   *   An array of options for the user_group field.
   */
  protected function loadGroupsOptions($apiKey) {
    $options = [];
    if ($apiKey && $this->senderApi->checkApiKey($apiKey)) {
      try {
        // Replace this with actual API call or logic to fetch groups.
        $data = $this->senderApi->listAllGroups();
        $groups = json_decode($data->getBody()->getContents());

        foreach ($groups->data as $group) {
          $options[$group->id] = $group->title;
        }
      }
      catch (\Exception $e) {
        watchdog_exception('sender_net', $e);
        $this->messenger->addError($this->t('Unable to load groups. Please check your API access token and try again.'));
      }
    }
    return $options;
  }

}
