<?php

namespace Drupal\task_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\task_manager\Service\TaskManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\Markup; // <-- ADD THIS LINE

/**
 * Controller for displaying task lists.
 */
class TaskManagerController extends ControllerBase
{

  /**
   * The task manager service.
   *
   * @var \Drupal\task_manager\Service\TaskManagerService
   */
  protected TaskManagerService $taskManager;

  /**
   * Constructor.
   */
  public function __construct(TaskManagerService $task_manager)
  {
    $this->taskManager = $task_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
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
  public function listTasks(): array
  {
    $tasks = $this->taskManager->getTasks();
    $current_user = $this->currentUser();

    if (empty($tasks)) {
      return [
        '#markup' => $this->t('No tasks found. @link', [
          '@link' => Link::fromTextAndUrl('Add a task', Url::fromRoute('task_manager.add'))->toString(),
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
      $actions = [];

      // Add delete link only for admins.
      if ($current_user->hasPermission('administer nodes')) {
        $actions[] = Link::fromTextAndUrl(
          $this->t('Delete'),
          Url::fromRoute('task_manager.delete', ['task' => $task->id])
        )->toString();
      }
      $rows[] = [
        $task->title,
        $task->description ?: $this->t('No description'),
        $task->status ? $this->t('Completed') : $this->t('Pending'),
        date('Y-m-d H:i', $task->created),
        [
          'data' => Link::fromTextAndUrl(
            $this->t('Delete'),
            Url::fromRoute('task_manager.delete', ['task' => $task->id])
          )->toRenderable(),
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No tasks found.'),
    ];
  }

  /**
   * Delete a task.
   */
  public function deleteTask($task)
  {
    $node = \Drupal\node\Entity\Node::load($task);

    if ($node && $node->bundle() === 'task') {
      $node->delete();
      $this->messenger()->addMessage($this->t('Task @title has been deleted.', ['@title' => $node->getTitle()]));
    } else {
      $this->messenger()->addError($this->t('Task not found or invalid.'));
    }

    return $this->redirect('task_manager.list');
  }
}
