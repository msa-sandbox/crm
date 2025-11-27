<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class PermissionChecker
{
    public function isGranted(
        User $user,
        PermissionEntityEnum $entity,
        PermissionActionEnum $action,
    ): bool {
        $permissions = $user->getPermissions();

        return isset($permissions[$entity->value])
            && in_array($action->value, $permissions[$entity->value], true);
    }

    /**
     * @param User $user
     * @param PermissionEntityEnum $entity
     * @param PermissionActionEnum $action
     *
     * @return void
     */
    public function assertGranted(
        User $user,
        PermissionEntityEnum $entity,
        PermissionActionEnum $action,
    ): void {
        if (!$this->isGranted($user, $entity, $action)) {
            throw new AccessDeniedException(sprintf(
                'User does not have permission "%s:%s"',
                $entity->value,
                $action->value
            ));
        }
    }
}
