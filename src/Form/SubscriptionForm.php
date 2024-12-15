<?php

namespace Drupal\sender_net\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sender_net\SenderNetApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Sender.net email subscription form.
 */
class SubscriptionForm extends FormBase {

  /**
   * Sender.net API service.
   *
   * @var \Drupal\sender_net\SenderNetApi
   */
  protected $senderApi;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SubscriptionForm object.
   *
   * @param \Drupal\sender_net\SenderNetApi $senderApi
   *   The sender.net API service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    SenderNetApi $senderApi,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->senderApi = $senderApi;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sender_net.api'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sender_net_subscription_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');

    // Check if subscriber already exists.
    $existingSubscriber = $this->senderApi->getSubscriberByEmail($email);
    if ($existingSubscriber) {
      $form_state->setErrorByName('email', $this->t('Subscriber with email "@email" already exists.', ['@email' => $email]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $user_group = $this->config('sender_net.settings')->get('user_group');

    $param = [
      'email' => $email,
      'groups' => $user_group,
    ];

    // Check if a user account exists with the provided email.
    $account = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
    if (!empty($account)) {
      // Get the first and last names of the user.
      $user = reset($account);
      $param['firstname'] = $user->get('name')->value ?? '';
      $param['lastname'] = '';
    }

    // Proceed with subscription.
    $result = $this->senderApi->createSubscriber($param);

    // Status message after the API call.
    if ($result) {
      $this->messenger()->addStatus($this->t('The email "@email" has been subscribed successfully.', ['@email' => $email]));
    }
    else {
      $this->getLogger('sender_net')->error('Failed to subscribe email "@email".', ['@email' => $email]);
      $this->messenger()->addError($this->t('There was an error subscribing the email. Please try again.'));
    }
  }

}
