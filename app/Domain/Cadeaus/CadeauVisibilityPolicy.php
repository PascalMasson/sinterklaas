<?php

namespace App\Domain\Cadeaus;

use App\Enums\CadeauVisibility;
use App\Models\Cadeau;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class CadeauVisibilityPolicy
{
    public function applyTo(Builder $query, ?User $viewer): Builder
    {
        $viewerId = $viewer?->getKey();

        return $query->where(function (Builder $query) use ($viewerId): void {
            $query->where('visibility', CadeauVisibility::PUBLIC->value);

            if ($viewerId === null) {
                return;
            }

            $query
                ->orWhere(function (Builder $query) use ($viewerId): void {
                    $query
                        ->where('visibility', CadeauVisibility::HIDDEN->value)
                        ->where('list_user_id', '!=', $viewerId);
                })
                ->orWhere(function (Builder $query) use ($viewerId): void {
                    $query
                        ->where('visibility', CadeauVisibility::PRIVATE->value)
                        ->where('created_by_user_id', $viewerId);
                });
        });
    }

    public function canSee(Cadeau $cadeau, ?User $viewer): bool
    {
        return $cadeau->visibility->isVisibleTo(
            viewerId: $viewer?->getKey(),
            recipientId: $cadeau->list_user_id,
            creatorId: $cadeau->created_by_user_id,
        );
    }

    public function canView(Cadeau $cadeau, ?User $viewer): bool
    {
        return $viewer !== null
            && (int) $cadeau->list_user_id !== (int) $viewer->getKey();
    }

    public function canEdit(Cadeau $cadeau, ?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        $viewerId = (int) $viewer->getKey();

        return (int) $cadeau->list_user_id === $viewerId
            || (int) $cadeau->created_by_user_id === $viewerId;
    }

    public function canConfigure(?User $viewer, ?int $listId): bool
    {
        return $viewer !== null
            && $listId !== null
            && (int) $viewer->getKey() !== $listId;
    }

    public function defaultFor(?int $viewerId, ?int $listId): CadeauVisibility
    {
        if ($viewerId !== null && $listId !== null && $viewerId === $listId) {
            return CadeauVisibility::PUBLIC;
        }

        return CadeauVisibility::HIDDEN;
    }
}
