<?php

namespace Drupal\activity_logger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

class DashboardController extends ControllerBase
{

    /**
     * List all user activities with delete links.
     */
    public function list_user_activities()
    {
        $connection = Database::getConnection();

        $query = $connection->select('user_activities', 'ua')
            ->fields('ua')
            ->orderBy('ua.id', 'DESC');
        $results = $query->execute()->fetchAll();

        // Build the table header.
        $header = ['ID', 'User ID', 'Activity', 'Time', 'Operations'];
        $rows = [];

        foreach ($results as $row) {
            // Create a delete URL for this record.
            $delete_url = Url::fromRoute('activity_logger.delete_activity', ['id' => $row->id]);
            $delete_link = Link::fromTextAndUrl('Delete', $delete_url)->toString();

            // Add table row.
            $rows[] = [
                $row->id,
                $row->uid,
                $row->activity,
                date('Y-m-d H:i:s', $row->timestamp),
                $delete_link,
            ];
        }

        $current_user = \Drupal::currentUser();
        if ($current_user->hasPermission('access administration pages')) {
            return [
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#cache' => ['max-age' => 0],
            ];
        } else {
            return [
                '#markup' => 'Access Denied. You do not have permission to view this page.',
                 '#cache' => ['max-age' => 0],
            ];
        }
    }

    /**
     * Delete a specific activity record by ID.
     */
    public function delete_activity($id)
    {
        $connection = Database::getConnection();
        $connection->delete('user_activities')
            ->condition('id', $id)
            ->execute();

        $this->messenger()->addStatus('Activity log with ID ' . $id . ' has been deleted.');

        // Redirect back to the listing page.
        return $this->redirect('activity_logger.show');
    }
}
