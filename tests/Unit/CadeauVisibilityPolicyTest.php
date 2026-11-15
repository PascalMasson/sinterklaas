<?php

namespace Tests\Unit;

use App\Domain\Cadeaus\CadeauVisibilityPolicy;
use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use App\Models\Cadeau;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class CadeauVisibilityPolicyTest extends TestCase
{
    private CadeauVisibilityPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CadeauVisibilityPolicy;
    }

    public function test_public_cadeau_is_visible_to_everyone(): void
    {
        $cadeau = $this->cadeau(CadeauVisibility::PUBLIC, 10, 20);

        self::assertTrue($this->policy->canSee($cadeau, null));
        self::assertTrue($this->policy->canSee($cadeau, $this->user(30)));
    }

    public function test_hidden_cadeau_is_not_visible_to_recipient(): void
    {
        $cadeau = $this->cadeau(CadeauVisibility::HIDDEN, 10, 20);

        self::assertFalse($this->policy->canSee($cadeau, $this->user(10)));
        self::assertTrue($this->policy->canSee($cadeau, $this->user(30)));
        self::assertFalse($this->policy->canSee($cadeau, null));
    }

    public function test_private_cadeau_is_visible_only_to_creator(): void
    {
        $cadeau = $this->cadeau(CadeauVisibility::PRIVATE, 10, 20);

        self::assertTrue($this->policy->canSee($cadeau, $this->user(20)));
        self::assertFalse($this->policy->canSee($cadeau, $this->user(10)));
        self::assertFalse($this->policy->canSee($cadeau, $this->user(30)));
    }

    public function test_creator_or_list_owner_can_edit(): void
    {
        $cadeau = $this->cadeau(CadeauVisibility::PUBLIC, 10, 20);

        self::assertTrue($this->policy->canEdit($cadeau, $this->user(10)));
        self::assertTrue($this->policy->canEdit($cadeau, $this->user(20)));
        self::assertFalse($this->policy->canEdit($cadeau, $this->user(30)));
        self::assertFalse($this->policy->canEdit($cadeau, null));
    }

    public function test_only_other_participant_lists_can_be_viewed(): void
    {
        $cadeau = $this->cadeau(CadeauVisibility::PUBLIC, 10, 20);

        self::assertTrue($this->policy->canView($cadeau, $this->user(20)));
        self::assertFalse($this->policy->canView($cadeau, $this->user(10)));
        self::assertFalse($this->policy->canView($cadeau, null));
    }

    public function test_default_visibility_is_public_for_own_list_and_hidden_otherwise(): void
    {
        self::assertSame(CadeauVisibility::PUBLIC, $this->policy->defaultFor(10, 10));
        self::assertSame(CadeauVisibility::HIDDEN, $this->policy->defaultFor(20, 10));
        self::assertSame(CadeauVisibility::HIDDEN, $this->policy->defaultFor(null, 10));
    }

    public function test_visibility_can_only_be_configured_for_another_persons_list(): void
    {
        self::assertFalse($this->policy->canConfigure($this->user(10), 10));
        self::assertTrue($this->policy->canConfigure($this->user(10), 20));
        self::assertFalse($this->policy->canConfigure(null, 20));
    }

    public function test_creation_defaults_are_applied_by_the_visibility_policy(): void
    {
        $cadeau = new Cadeau;
        $cadeau->list_user_id = 10;

        $this->policy->applyCreationDefaults($cadeau, $this->user(20));

        self::assertSame(CadeauStatus::AVAILABLE, $cadeau->status);
        self::assertSame(20, $cadeau->created_by_user_id);
        self::assertSame(CadeauVisibility::HIDDEN, $cadeau->visibility);
    }

    private function cadeau(CadeauVisibility $visibility, int $listUserId, int $creatorId): Cadeau
    {
        $cadeau = new Cadeau;
        $cadeau->visibility = $visibility;
        $cadeau->list_user_id = $listUserId;
        $cadeau->created_by_user_id = $creatorId;

        return $cadeau;
    }

    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }
}
