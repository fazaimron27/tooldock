<?php

namespace Modules\Newsletter\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;
use Modules\Newsletter\Models\Campaign;

class CampaignPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only view their own campaigns.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.view')
            && $user->id === $campaign->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only update their own campaigns.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.edit')
            && $user->id === $campaign->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only delete their own campaigns.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.delete')
            && $user->id === $campaign->user_id;
    }

    /**
     * Determine whether the user can send the campaign.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only send their own campaigns.
     */
    public function send(User $user, Campaign $campaign): bool
    {
        return $user->hasPermissionTo('newsletter.campaigns.send')
            && $user->id === $campaign->user_id;
    }
}
