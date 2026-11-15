<?php

namespace App\Domain\Cadeaus;

use App\Enums\CadeauStatus;
use App\Models\Cadeau;
use App\Models\User;
use LogicException;

final class CadeauReservationPolicy
{
    public function canChangeStatus(Cadeau $cadeau, ?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        if ($cadeau->status === CadeauStatus::AVAILABLE) {
            return true;
        }

        $viewerIds = [
            (int) $viewer->getKey(),
            (int) $viewer->partnerId,
        ];

        return in_array((int) $cadeau->reserved_by_user_id, $viewerIds, true);
    }

    public function changeStatus(Cadeau $cadeau, CadeauStatus $status, ?User $viewer): void
    {
        if (! $this->canChangeStatus($cadeau, $viewer)) {
            throw new LogicException('The viewer cannot change this cadeau status.');
        }

        $cadeau->status = $status;
        $cadeau->reserved_by_user_id = $status === CadeauStatus::AVAILABLE
            ? null
            : $viewer?->getKey();
    }

    public function reset(Cadeau $cadeau): void
    {
        $cadeau->status = CadeauStatus::AVAILABLE;
        $cadeau->reserved_by_user_id = null;
    }
}
