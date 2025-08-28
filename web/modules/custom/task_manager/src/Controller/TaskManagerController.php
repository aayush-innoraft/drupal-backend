<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\task_manager\Service\TaskManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

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
      $this->t('Mark as Done'),
    ];

    $rows = [];
    foreach ($tasks as $task) {
      $rows[] = [
        $task->title,
        $task->description ?: $this->t('No description'),
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $task->status ? $this->t('Completed') : $this->t('Pending'),
            '#attributes' => ['id' => 'status-text-' . $task->id],
          ],
        ],
        date('Y-m-d H:i', strtotime($task->created)),
        Link::fromTextAndUrl(
          $this->t('Delete'),
          Url::fromRoute('task_manager.delete', ['task_id' => $task->id])
        )->toString(),
        [
          'data' => [
            '#type' => 'checkbox',
            '#default_value' => (int) $task->status,
            '#ajax' => [
              'callback' => '::updateStatusAjax',
              'event' => 'change',
              'progress' => ['type' => 'throbber'],
              'url' => Url::fromRoute('task_manager.update_status', ['task_id' => $task->id]),
            ],
          ],
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
   * AJAX callback to update task status.
   */
  public function updateTaskStatus($task_id): JsonResponse {
    $node = Node::load($task_id);

    if ($node && $node->bundle() === 'task') {
      $current_status = $node->get('field_status')->value;
      $new_status = $current_status ? 0 : 1;

      $node->set('field_status', $new_status);
      $node->save();

      return new JsonResponse([
        'success' => TRUE,
        'new_status' => $new_status ? 'Completed' : 'Pending',
        'task_id' => $task_id,
      ]);
    }

    return new JsonResponse(['success' => FALSE, 'message' => 'Invalid task'], 400);
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
    }
    else {
      $this->messenger()->addError($this->t('Task not found or invalid.'));
    }

    return $this->redirect('task_manager.list');
  }

}
