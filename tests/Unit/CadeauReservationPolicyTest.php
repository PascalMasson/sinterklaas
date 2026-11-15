<?php

namespace Tests\Unit;

use App\Domain\Cadeaus\CadeauReservationPolicy;
use App\Enums\CadeauStatus;
use App\Models\Cadeau;
use App\Models\User;
use LogicException;
use PHPUnit\Framework\TestCase;

class CadeauReservationPolicyTest extends TestCase
{
    private CadeauReservationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CadeauReservationPolicy;
    }

    public function test_a_viewer_can_reserve_an_available_cadeau(): void
    {
        $cadeau = $this->cadeau(CadeauStatus::AVAILABLE, null);

        $this->policy->changeStatus($cadeau, CadeauStatus::RESERVED, $this->user(20));

        self::assertSame(CadeauStatus::RESERVED, $cadeau->status);
        self::assertSame(20, $cadeau->reserved_by_user_id);
    }

    public function test_a_partner_can_change_a_reservation(): void
    {
        $cadeau = $this->cadeau(CadeauStatus::RESERVED, 30);
        $viewer = $this->user(20, partnerId: 30);

        self::assertTrue($this->policy->canChangeStatus($cadeau, $viewer));

        $this->policy->changeStatus($cadeau, CadeauStatus::PURCHASED, $viewer);

        self::assertSame(CadeauStatus::PURCHASED, $cadeau->status);
        self::assertSame(20, $cadeau->reserved_by_user_id);
    }

    public function test_an_unrelated_viewer_cannot_change_a_reservation(): void
    {
        $this->expectException(LogicException::class);

        $this->policy->changeStatus(
            $this->cadeau(CadeauStatus::RESERVED, 30),
            CadeauStatus::PURCHASED,
            $this->user(20),
        );
    }

    public function test_reset_returns_a_cadeau_to_an_available_unreserved_state(): void
    {
        $cadeau = $this->cadeau(CadeauStatus::PURCHASED, 20);

        $this->policy->reset($cadeau);

        self::assertSame(CadeauStatus::AVAILABLE, $cadeau->status);
        self::assertNull($cadeau->reserved_by_user_id);
    }

    private function cadeau(CadeauStatus $status, ?int $reservedBy): Cadeau
    {
        $cadeau = new Cadeau;
        $cadeau->status = $status;
        $cadeau->reserved_by_user_id = $reservedBy;

        return $cadeau;
    }

    private function user(int $id, ?int $partnerId = null): User
    {
        $user = new User;
        $user->id = $id;
        $user->partnerId = $partnerId;

        return $user;
    }
}
