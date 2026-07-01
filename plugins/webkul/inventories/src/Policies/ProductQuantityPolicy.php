<?php

namespace Webkul\Inventory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Security\Models\User;

class ProductQuantityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_inventory_quantity');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductQuantity $productQuantity): bool
    {
        return $user->can('view_any_inventory_quantity');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_inventory_quantity');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductQuantity $productQuantity): bool
    {
        return $user->can('update_inventory_quantity')
            || $user->can('create_inventory_quantity')
            || $user->can('view_any_inventory_quantity');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductQuantity $productQuantity): bool
    {
        return $user->can('delete_inventory_quantity')
            || $user->can('create_inventory_quantity')
            || $user->can('view_any_inventory_quantity');
    }
}
