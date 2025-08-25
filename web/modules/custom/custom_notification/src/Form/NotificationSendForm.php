<?php

namespace Drupal\custom_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to send custom notifications to users.
 */
class NotificationSendForm extends FormBase {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Construct with DI.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_notifications_send_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select User'),
      '#target_type' => 'user',
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Notification'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('user');
    $message = $form_state->getValue('message');

    if ($uid && $message) {
      $this->database->insert('custom_notifications')
        ->fields([
          'uid' => $uid,
          'message' => $message,
          'status' => 0,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();

      $this->messenger()->addStatus($this->t('Notification sent to user ID @uid.', ['@uid' => $uid]));
    }
  }

}
