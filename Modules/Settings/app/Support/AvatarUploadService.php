<?php

namespace Modules\Settings\app\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\File;
use Modules\Auth\app\Models\User;
use Modules\Settings\app\Models\Mentor;
use Throwable;

class AvatarUploadService
{
    public function replaceStudentAvatar(User $user, UploadedFile $avatar): string
    {
        return $this->replaceAvatar(
            fileable: $user,
            owner: $user,
            avatar: $avatar,
            directory: 'avatars/students',
            persist: function (string $url) use ($user): void {
                $user->forceFill(['avatar_url' => $url])->save();
            },
        );
    }

    public function replaceMentorAvatar(User $user, Mentor $mentor, UploadedFile $avatar): string
    {
        return $this->replaceAvatar(
            fileable: $mentor,
            owner: $user,
            avatar: $avatar,
            directory: 'avatars/mentors',
            persist: function (string $url) use ($mentor, $user): void {
                $mentor->forceFill(['avatar_url' => $url])->save();
                $user->forceFill(['avatar_url' => $url])->save();
            },
        );
    }

    private function replaceAvatar(Model $fileable, User $owner, UploadedFile $avatar, string $directory, callable $persist): string
    {
        $disk = 'public';
        $path = $avatar->store($directory, $disk);
        $url = Storage::disk($disk)->url($path);
        $existingAvatarUrl = (string) ($fileable->getAttribute('avatar_url') ?? '');
        $existingFiles = $fileable->files()
            ->where('type', 'avatar')
            ->where('is_deleted', false)
            ->get();

        try {
            DB::transaction(function () use ($avatar, $disk, $existingFiles, $fileable, $owner, $path, $persist, $url): void {
                $storedName = basename($path);
                $extension = strtolower($avatar->getClientOriginalExtension() ?: $avatar->extension() ?: pathinfo($storedName, PATHINFO_EXTENSION));

                File::query()->create([
                    'user_id' => $owner->id,
                    'fileable_type' => $fileable->getMorphClass(),
                    'fileable_id' => $fileable->getKey(),
                    'original_name' => $avatar->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'path' => $path,
                    'disk' => $disk,
                    'extension' => $extension,
                    'mime_type' => $avatar->getMimeType() ?: 'application/octet-stream',
                    'size' => $avatar->getSize() ?: 0,
                    'type' => 'avatar',
                    'is_public' => true,
                    'is_deleted' => false,
                ]);

                if ($existingFiles->isNotEmpty()) {
                    File::query()
                        ->whereIn('id', $existingFiles->pluck('id'))
                        ->update([
                            'is_deleted' => true,
                            'deleted_at' => now(),
                        ]);
                }

                $persist($url);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        $this->deleteFileRecordsFromStorage($existingFiles);
        $this->deleteLegacyAvatarUrl($existingAvatarUrl, $existingFiles);

        return $url;
    }

    private function deleteFileRecordsFromStorage(Collection $files): void
    {
        $files
            ->filter(fn (File $file): bool => filled($file->path))
            ->each(function (File $file): void {
                Storage::disk($file->disk ?: 'public')->delete($file->path);
            });
    }

    private function deleteLegacyAvatarUrl(string $avatarUrl, Collection $existingFiles): void
    {
        $legacyPath = $this->publicDiskPathFromUrl($avatarUrl);

        if (! $legacyPath) {
            return;
        }

        $knownPaths = $existingFiles
            ->pluck('path')
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->all();

        if (in_array($legacyPath, $knownPaths, true)) {
            return;
        }

        Storage::disk('public')->delete($legacyPath);
    }

    private function publicDiskPathFromUrl(string $avatarUrl): ?string
    {
        if ($avatarUrl === '') {
            return null;
        }

        $path = parse_url($avatarUrl, PHP_URL_PATH);

        if (! is_string($path) || ! Str::startsWith($path, '/storage/')) {
            return null;
        }

        return ltrim(Str::after($path, '/storage/'), '/');
    }
}
