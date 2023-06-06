<?php

namespace Drupal\job_type\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Profile Menu Link
 */
class JobPorviderRegistartion extends MenuLinkDefault implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new MenuLinkDefault.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $current_route_match, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  public function getRouteParameters() {
    return ['user' => $this->getUserIdFromRoute()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user', 'url'];
  }


  /**
   * Get the Account user id from the request or fallback to current user.
   *
   * @return int
   */
  public function getUserIdFromRoute() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user instanceof AccountInterface) {
     return $user->id();
    }
    elseif (!empty($user)) {
      $user = $this->entityTypeManager->getStorage('user')->load($user);
      if($user instanceof AccountInterface) {
        return $user->id();
      }
    }

    return $this->currentUser->id();
  }

}
