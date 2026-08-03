<?php

namespace App\Http\Requests\Operations;

use App\Domains\Company\Models\User;
use App\Domains\Company\Support\CommercialProfileCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $member */
        $member = $this->route('user');

        return $this->user()?->can('managePermissions', $member) ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function desiredPermissions(): array
    {
        $raw = $this->input('permissions', []);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach (CommercialProfileCatalog::commercialPermissionSlugs() as $slug) {
            // Checkbox forms omit unchecked keys — treat missing as false when form posts all groups.
            $out[$slug] = array_key_exists($slug, $raw)
                ? filter_var($raw[$slug], FILTER_VALIDATE_BOOLEAN)
                : false;
        }

        return $out;
    }
}
