<?php

namespace Drupal\task_manager\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Service class to manage user tasks.
 */
class TaskManagerService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheInvalidator;

  /**
   * Constructor.
   */
  public function __construct(
    Connection $database,
    AccountInterface $current_user,
    CacheBackendInterface $cache,
    MessengerInterface $messenger,
    CacheTagsInvalidatorInterface $cacheInvalidator,
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->cache = $cache;
    $this->messenger = $messenger;
    $this->cacheInvalidator = $cacheInvalidator;
  }

  /**
   * Get tasks for the current user.
   *
   * @return array
   *   An array of task objects.
   */
  public function getTasks(): array {
    $cid = 'task_manager:tasks:' . $this->currentUser->id();

    // Return from cache if available.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    // Fetch from the database.
    $query = $this->database->select('task_manager_tasks', 't')
      ->fields('t', ['id', 'title', 'description', 'status', 'created'])
      ->condition('uid', $this->currentUser->id())
      ->orderBy('created', 'DESC');
    $tasks = $query->execute()->fetchAll();

    // Cache for 1 hour with the tag for invalidation.
    $this->cache->set($cid, $tasks, time() + 3600, ['task_manager:tasks']);

    return $tasks;
  }

  /**
   * Add a new task.
   *
   * @param string $title
   *   Task title.
   * @param string $description
   *   Task description.
   */
  public function addTask(string $title, string $description): void {
    $this->database->insert('task_manager_tasks')
      ->fields([
        'uid' => $this->currentUser->id(),
        'title' => $title,
        'description' => $description,
        'status' => 0,
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    $this->cacheInvalidator->invalidateTags(['task_manager:tasks']);
    $this->messenger->addStatus('Task added successfully.');
  }

  /**
   * Update task status (toggle complete/incomplete).
   *
   * @param int $task_id
   *   Task ID.
   * @param int $status
   *   Status value (0 or 1).
   */
  public function updateTaskStatus(int $task_id, int $status): void {
    $this->database->update('task_manager_tasks')
      ->fields(['status' => $status])
      ->condition('id', $task_id)
      ->condition('uid', $this->currentUser->id())
      ->execute();

    $this->cacheInvalidator->invalidateTags(['task_manager:tasks']);
  }

  /**
   * Delete a task.
   *
   * @param int $task_id
   *   Task ID.
   */
  public function deleteTask(int $task_id): void {
    $this->database->delete('task_manager_tasks')
      ->condition('id', $task_id)
      ->condition('uid', $this->currentUser->id())
      ->execute();

    $this->cacheInvalidator->invalidateTags(['task_manager:tasks']);
    $this->messenger->addWarning('Task deleted.');
  }

}
