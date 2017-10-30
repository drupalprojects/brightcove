<?php

namespace Drupal\brightcove\Controller;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\brightcove\Entity\BrightcoveVideo;
use Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BrightcoveSubscriptionController.
 *
 * @package Drupal\brightcove\Controller
 */
class BrightcoveSubscriptionController extends ControllerBase {

  /**
   * Drupal container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * BrightcoveVideo storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $videoStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('database'),
      $container->get('link_generator'),
      $container->get('entity_type.manager')->getStorage('brightcove_video')
    );
  }

  /**
   * BrightcoveSubscriptionController constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Drupal container.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   Link generator.
   * @param \Drupal\Core\Entity\EntityStorageInterface $video_storage
   *   Brightcove video storage.
   */
  public function __construct(ContainerInterface $container, Connection $connection, LinkGeneratorInterface $link_generator, EntityStorageInterface $video_storage) {
    $this->container = $container;
    $this->connection = $connection;
    $this->linkGenerator = $link_generator;
    $this->videoStorage = $video_storage;
  }

  /**
   * Menu callback to handle the Brightcove notification callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Redirection response.
   */
  public function notificationCallback(Request $request) {
    $content = Json::decode($request->getContent());

    switch ($content['event']) {
      case 'video-change':
        /** @var \Drupal\brightcove\Entity\BrightcoveVideo $video_entity */
        $video_entity = BrightcoveVideo::loadByBrightcoveVideoId($content['account_id'], $content['video']);

        // Get CMS API.
        $cms = BrightcoveUtil::getCMSAPI($video_entity->getAPIClient());

        // Update video.
        $video = $cms->getVideo($video_entity->getVideoId());
        BrightcoveVideo::createOrUpdate($video, $this->videoStorage, $video_entity->getAPIClient());
        break;
    }

    return new Response();
  }

  /**
   * Lists available Brightcove Subscriptions.
   */
  public function listSubscriptions() {
    // Set headers.
    $header = [
      'endpoint' => $this->t('Endpoint'),
      'api_client' => $this->t('API Client'),
      'events' => $this->t('Events'),
      'operations' => $this->t('Operations'),
    ];

    // Get Subscriptions.
    $brightcove_subscriptions = BrightcoveSubscription::loadMultiple();

    // Whether a warning has benn shown about the missing subscriptions on
    // Brightcove or not.
    $warning_set = FALSE;

    // Assemble subscription list.
    $rows = [];
    foreach ($brightcove_subscriptions as $key => $brightcove_subscription) {
      $api_client = $brightcove_subscription->getApiClient();

      $rows[$key] = [
        'endpoint' => $brightcove_subscription->getEndpoint() . ($brightcove_subscription->isDefault() ? " ({$this->t('default')})" : ''),
        'api_client' => !empty($api_client) ? $this->linkGenerator->generate($api_client->label(), Url::fromRoute('entity.brightcove_api_client.edit_form', [
          'brightcove_api_client' => $api_client->id(),
        ])) : '',
        'events' => implode(', ', array_filter($brightcove_subscription->getEvents(), function ($value) {
          return !empty($value);
        })),
      ];

      // Default subscriptions can be enabled or disabled only.
      if ((bool) $brightcove_subscription->isDefault()) {
        $enable_link = Url::fromRoute('entity.brightcove_subscription.enable', [
          'id' => $brightcove_subscription->getId(),
        ]);

        $disable_link = Url::fromRoute('entity.brightcove_subscription.disable', [
          'id' => $brightcove_subscription->getId(),
        ]);

        $rows[$key]['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'change_status' => [
                'title' => $brightcove_subscription->isActive() ? $this->t('Disable') : $this->t('Enable'),
                'url' => $brightcove_subscription->isActive() ? $disable_link : $enable_link,
              ],
            ],
          ],
        ];
      }
      // Otherwise show delete button or create button as well if needed.
      else {
        $subscriptions = BrightcoveSubscription::listFromBrightcove($api_client);

        $subscription_found = FALSE;
        foreach ($subscriptions as $subscription) {
          if ($brightcove_subscription->getEndpoint() == $subscription->getEndpoint()) {
            $subscription_found = TRUE;

            // If the endpoint exist but their ID is different, fix it.
            if ($brightcove_subscription->getBcSid() != ($id = $subscription->getId())) {
              $brightcove_subscription->setBcSid($id);
              $brightcove_subscription->save();
            }
            break;
          }
        }

        if (!$warning_set && !$subscription_found) {
          drupal_set_message($this->t('There are subscriptions which are not available on Brightcove.<br>You can either <strong>create</strong> them on Brightcove or <strong>delete</strong> them if no longer needed.'), 'warning');
          $warning_set = TRUE;
        }

        // Add create link if the subscription is missing from Brightcove.
        $create_link = [];
        if (!$subscription_found) {
          $create_link = [
            'create' => [
              'title' => $this->t('Create'),
              'url' => Url::fromRoute('entity.brightcove_subscription.create', [
                'id' => $brightcove_subscription->getId(),
              ]),
            ],
          ];
        }

        $rows[$key]['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $create_link + [
              'delete' => [
                'title' => $this->t('Delete'),
                'weight' => 10,
                'url' => Url::fromRoute('entity.brightcove_subscription.delete_form', [
                  'id' => $brightcove_subscription->getId(),
                ]),
              ],
            ],
          ],
        ];
      }
    }

    $page['subscriptions'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $page;
  }

  /**
   * Create a subscription on Brightcove from an already existing entity.
   *
   * @param int $id
   *   BrightcoveSubscription entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after creating a Drupal only
   *   subscription.
   */
  public function createSubscription($id) {
    try {
      $brightcove_subscription = BrightcoveSubscription::load($id);
      $brightcove_subscription->saveToBrightcove();
    }
    catch (BrightcoveSubscriptionException $e) {
      drupal_set_message($this->t('Failed to create Subscription on Brightcove: @error', ['@error' => $e->getMessage()]), 'error');
    }

    return $this->redirect('entity.brightcove_subscription.list');
  }

  /**
   * Enables and creates the default Subscription from Brightcove.
   *
   * @param string $id
   *   The ID of the Brightcove Subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after enabling the default
   *   subscription.
   */
  public function enable($id) {
    try {
      $subscription = BrightcoveSubscription::load($id);
      $subscription->saveToBrightcove();
      drupal_set_message($this->t('Default subscription for the "@api_client" API client has been successfully enabled.', ['@api_client' => $subscription->getApiClient()->label()]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Failed to enable the default subscription: @error', ['@error' => $e->getMessage()]), 'error');
    }
    return $this->redirect('entity.brightcove_subscription.list');
  }

  /**
   * Disabled and removed the default Subscription from Brightcove.
   *
   * @param string $id
   *   The ID of the Brightcove Subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after enabling the default
   *   subscription.
   */
  public function disable($id) {
    try {
      $subscription = BrightcoveSubscription::load($id);
      $subscription->deleteFromBrightcove();
      drupal_set_message($this->t('Default subscription for the "@api_client" API client has been successfully disabled.', ['@api_client' => $subscription->getApiClient()->label()]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Failed to disable the default subscription: @error', ['@error' => $e->getMessage()]), 'error');
    }
    return $this->redirect('entity.brightcove_subscription.list');
  }

}
