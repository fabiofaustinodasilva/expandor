<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlatformOwnerProfileService
{
    public function __construct(
        protected MediaUploadService $media,
        protected SecurityService $security,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     * }  $data
     */
    public function update(User $owner, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): User
    {
        if (! $owner->isPlatformAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['Apenas o Platform Owner pode atualizar este perfil.'],
            ]);
        }

        $email = strtolower(trim($data['email']));

        $emailTaken = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('id', '!=', $owner->id)
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => ['Este e-mail já está em uso.'],
            ]);
        }

        return DB::transaction(function () use ($owner, $data, $email, $photo, $removePhoto) {
            $old = [
                'name' => $owner->name,
                'email' => $owner->email,
            ];

            $payload = [
                'name' => $data['name'],
                'email' => $email,
            ];

            $passwordChanged = ! empty($data['password']);
            if ($passwordChanged) {
                $payload['password'] = $data['password'];
            }

            if ($photo instanceof UploadedFile) {
                $result = $this->media->store(
                    $photo,
                    (int) $owner->company_id,
                    MediaCategory::Profiles,
                    MediaPurpose::ProfilePhoto,
                    $owner->photo,
                    $owner->photo_thumb,
                    'photo',
                );
                $payload['photo'] = $result->path;
                $payload['photo_thumb'] = $result->thumbPath;
            } elseif ($removePhoto) {
                $this->media->delete($owner->photo, $owner->photo_thumb);
                $payload['photo'] = null;
                $payload['photo_thumb'] = null;
            }

            $owner->fill($payload)->save();

            $this->security->recordAudit(
                action: 'platform.owner.profile_updated',
                user: $owner,
                auditable: $owner,
                oldValues: $old,
                newValues: [
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'password_changed' => $passwordChanged,
                    'photo_changed' => isset($payload['photo']) || $removePhoto,
                ],
                companyId: $owner->company_id,
            );

            return $owner->refresh();
        });
    }
}
