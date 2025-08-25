<?php

namespace Drupal\custom_notification\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\UserInterface;

/**
 * Handles login notifications for users.
 */
class LoginNotificationService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs the service.
   */
  public function __construct(Connection $database, MessengerInterface $messenger) {
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * Sends notifications to the user on login.
   *
   * @param \Drupal\user\UserInterface $account
   *   The logged-in user.
   */
  public function handleLogin(UserInterface $account): void {
    $this->messenger->addStatus('✅ Login hook triggered for testing.');

    $results = $this->database->select('custom_notifications', 'cn')
      ->fields('cn', ['id', 'message'])
      ->condition('uid', (int) $account->id())
      ->condition('status', 0)
      ->execute()
      ->fetchAll();

    foreach ($results as $row) {
      $this->messenger->addStatus($row->message);
    }

    $ids = array_map(static fn($r) => (int) $r->id, $results);
    if ($ids) {
      $this->database->update('custom_notifications')
        ->fields(['status' => 1])
        ->condition('id', $ids, 'IN')
        ->execute();
    }
  }

}
