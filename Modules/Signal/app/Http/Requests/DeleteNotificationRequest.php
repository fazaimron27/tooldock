<?php

/**
 * Delete Notification Request
 *
 * Form request for validating and authorizing notification deletion.
 * Ensures the user owns the notification before allowing deletion.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DeleteNotificationRequest
 *
 * Handles authorization for notification deletion via the policy system.
 * Validates that the notification exists and belongs to the requesting user.
 *
 * @see \Modules\Signal\Policies\NotificationPolicy::delete()
 */
class DeleteNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Checks the notification route binding and delegates to the
     * NotificationPolicy for ownership and permission verification.
     *
     * @return bool True if authorized, false otherwise
     */
    public function authorize(): bool
    {
        $notification = $this->route('notification');

        if (! $notification) {
            return false;
        }

        return $this->user()->can('delete', $notification);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * No additional validation required as the notification
     * is resolved via route model binding.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
