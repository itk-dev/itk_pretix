<?php

namespace Drupal\itk_pretix\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Access check.
 */
class AccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatchInterface $routeMatch) {
    $node = $routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $this->canRunExport($node, $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Decide af an account can run exports for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return bool
   *   True if the account can run exports for the node. Otherwise false.
   */
  public function canRunExport(NodeInterface $node, AccountInterface $account) {
    $user = User::load($account->id());

    return $node->access('update', $user);
  }

}
