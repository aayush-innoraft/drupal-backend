<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\task_manager\Service\TaskManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Controller for displaying and managing tasks.
 */
class TaskManagerController extends ControllerBase {

  /**
   * The task manager service.
   *
   * @var \Drupal\task_manager\Service\TaskManagerService
   */
  protected TaskManagerService $taskManager;

  /**
   * Constructor.
   */
  public function __construct(TaskManagerService $task_manager) {
    $this->taskManager = $task_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('task_manager.manager')
    );
  }

  /**
   * List tasks for the current user.
   *
   * @return array
   *   Render array of the task listing.
   */
  public function listTasks(): array {
    $tasks = $this->taskManager->getTasks();

    if (empty($tasks)) {
      return [
        '#markup' => $this->t('No tasks found. @link', [
          '@link' => Link::fromTextAndUrl(
            $this->t('Add a task'),
            Url::fromRoute('task_manager.add')
          )->toString(),
        ]),
      ];
    }

    $header = [
      $this->t('Title'),
      $this->t('Description'),
      $this->t('Status'),
      $this->t('Created'),
      $this->t('Actions'),
    ];

    $rows = [];
    foreach ($tasks as $task) {
      $rows[] = [
        $task->title,
        $task->description ?: $this->t('No description'),
        $task->status ? $this->t('Completed') : $this->t('Pending'),
        date('Y-m-d H:i', strtotime($task->created)),
        [
          'data' => Link::fromTextAndUrl(
            $this->t('Delete'),
            Url::fromRoute('task_manager.delete', ['task_id' => $task->id])
          )->toString(),
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No tasks found.'),
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Delete a task by ID.
   */
  public function deleteTask($task_id) {
    $connection = \Drupal::database();

    $deleted = $connection->delete('task_manager_tasks')
      ->condition('id', $task_id)
      ->execute();

    if ($deleted) {
      $this->messenger()->addMessage($this->t('Task with ID @id has been deleted.', ['@id' => $task_id]));
      return [
           '#cache' => ['max-age' => 0],
      ];
    }
    else {
      $this->messenger()->addError($this->t('Task not found or invalid.'));
    }

    return $this->redirect('task_manager.list');
  }

}
