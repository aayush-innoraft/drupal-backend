<?php

declare(strict_types=1);

namespace Drupal\daily_quote;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a daily quote entity type.
 */
interface DailyQuoteInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
