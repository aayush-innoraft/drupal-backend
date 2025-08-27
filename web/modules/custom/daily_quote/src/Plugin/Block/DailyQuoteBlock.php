<?php

namespace Drupal\daily_quote\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a Daily Quotes block.
 *
 * @Block(
 *   id = "daily_quote_block",
 *   admin_label = @Translation("Daily Quote Block"),
 *   category = @Translation("Custom")
 * )
 */
class DailyQuoteBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load all quotes from the custom entity 'daily_quote'.
    $quotes = \Drupal::entityTypeManager()
      ->getStorage('daily_quote')
      ->loadMultiple();
    $quote_texts = [];
    // If there are quotes in the entity, extract them safely.
    if (!empty($quotes)) {
      foreach ($quotes as $quote) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $quote */
        if ($quote->hasField('field_quote') && !$quote->get('field_quote')->isEmpty()) {
          $quote_texts[] = $quote->get('field_quote')->value;
        }
        elseif (method_exists($quote, 'label')) {
          $quote_texts[] = $quote->label();
        }
      }
    }

    // Fallback to hardcoded quotes if no entities are found.
    if (empty($quote_texts)) {
      $quote_texts = [
        "🌟 Believe in yourself and all that you are!",
        "🚀 Success is the sum of small efforts repeated daily.",
        "💡 Every expert was once a beginner. Keep learning!",
        "🔥 Push harder than yesterday if you want a different tomorrow.",
        "🌈 Positivity always wins. Stay happy, stay strong!",
        "💪 Challenges are what make life interesting; overcoming them is what makes life meaningful.",
        "🌱 Growth is a process — embrace every step of the journey.",
        "📈 The only limit to your impact is your imagination and commitment.",
        "✨ Consistency is what transforms average into excellence.",
        "⚡ Small progress every day leads to big results.",
        "🌊 Go with the flow, but never lose sight of the shore.",
        "🏆 The harder you work for something, the greater you'll feel when you achieve it.",
        "🧠 Knowledge is power, but action creates change.",
        "🎯 Focus on the goal, not the obstacles.",
        "🕊️ Let your dreams be bigger than your fears.",
      ];
    }

    // Pick one quote based on the day of the year for consistency.
    $index = date('z') % count($quote_texts);
    $quote = $quote_texts[$index];

    return [
      '#markup' => '<div class="daily-quote">"' . $quote . '"</div>',
      '#cache' => [
        // Cache for one day.
        'max-age' => 86400,
      ],
    ];
  }

}
