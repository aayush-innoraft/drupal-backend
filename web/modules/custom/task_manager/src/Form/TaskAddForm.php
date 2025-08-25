<?php

namespace Drupal\task_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\task_manager\Service\TaskManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a new task.
 */
class TaskAddForm extends FormBase {

  /**
   * The task manager service.
   *
   * @var \Drupal\task_manager\Service\TaskManagerService
   */
  protected TaskManagerService $taskManager;

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'task_manager_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task Title'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Task Description'),
      '#description' => $this->t('Optional description for your task.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Task'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $title = $form_state->getValue('title');
    $description = $form_state->getValue('description');

    // Use the service to add the task.
    $this->taskManager->addTask($title, $description);

    // Redirect back to task list.
    $this->messenger()->addStatus($this->t('Task "%title" added successfully.', ['%title' => $title]));
    $form_state->setRedirect('task_manager.list');
  }

}
