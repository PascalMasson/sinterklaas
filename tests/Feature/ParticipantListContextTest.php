<?php

namespace Tests\Feature;

use App\Navigation\ParticipantListContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParticipantListContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Pascal'],
            ['id' => 2, 'name' => 'Marianne'],
        ]);

        Cache::forget('resource-list-id-nav-items');
    }

    public function test_it_resolves_the_current_participant_list_from_session(): void
    {
        $context = ParticipantListContext::forList();
        session()->put('listId', 2);

        self::assertSame(2, $context->id());
        self::assertSame('Marianne', $context->name());
        self::assertSame(['view' => 'index', 'listId' => 2], $context->withCurrentId(['view' => 'index']));
    }

    public function test_it_builds_navigation_items_without_the_excluded_participant(): void
    {
        $context = ParticipantListContext::forList();

        self::assertSame([
            [2, 'Marianne'],
        ], $context->navigationItems(1, fn (int $id, string $name): array => [$id, $name]));
    }

    public function test_named_contexts_keep_resource_route_keys_distinct(): void
    {
        session()->put('listId', 1);
        session()->put('targetId', 2);

        self::assertSame(['listId' => 1], ParticipantListContext::forList()->withCurrentId([]));
        self::assertSame(['targetId' => 2], ParticipantListContext::forTarget()->withCurrentId([]));
    }
}
