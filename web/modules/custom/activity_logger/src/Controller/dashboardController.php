<?php

namespace Drupal\activity_logger\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;

class DashboardController extends ControllerBase
{
    public function list_user_activities()
    {
        $connection = \Drupal::database();
        $query = $connection->select('user_activities', 'ua')
            ->fields('ua')
            ->orderBy('ua.id', 'DESC');

        $results = $query->execute()->fetchAll();

        // Convert the array of objects into an HTML string
        $output = '<ul>';
        foreach ($results as $row) {
            $output .= '<li>User ID: ' . $row->uid . ' | Activity: ' . $row->activity . ' | Time: ' . date('Y-m-d H:i:s', $row->timestamp) . '</li>';
        }
        $output .= '</ul>';

        $current_user = \Drupal::currentUser();
        if ($current_user->hasPermission('access administration pages')) {
            return [
                '#markup' => $output,
                '#cache' => ['max-age' => 0],
            ];
        } else {
            return [
                '#markup' => 'Access Denied. You do not have permission to view this page.',
                '#cache' => ['max-age' => 0],
            ];
        }
    }
}
