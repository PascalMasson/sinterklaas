<?php

namespace Tests\Feature;

use App\Models\Cadeau;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CadeauVisibilityQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('cadeaus', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('visibility');
            $table->unsignedBigInteger('list_user_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->softDeletes();
        });
    }

    public function test_visible_for_filters_records_using_the_same_visibility_rules(): void
    {
        DB::table('cadeaus')->insert([
            ['title' => 'public', 'visibility' => 'public', 'list_user_id' => 10, 'created_by_user_id' => 20],
            ['title' => 'hidden for viewer', 'visibility' => 'hidden', 'list_user_id' => 10, 'created_by_user_id' => 20],
            ['title' => 'hidden for another list', 'visibility' => 'hidden', 'list_user_id' => 20, 'created_by_user_id' => 30],
            ['title' => 'private by viewer', 'visibility' => 'private', 'list_user_id' => 20, 'created_by_user_id' => 10],
            ['title' => 'private by another person', 'visibility' => 'private', 'list_user_id' => 20, 'created_by_user_id' => 30],
        ]);

        $viewer = new User;
        $viewer->id = 10;

        self::assertSame([
            'public',
            'hidden for another list',
            'private by viewer',
        ], Cadeau::query()->visibleFor($viewer)->orderBy('id')->pluck('title')->all());
    }

    public function test_guests_can_only_query_public_cadeaus(): void
    {
        DB::table('cadeaus')->insert([
            ['title' => 'public', 'visibility' => 'public', 'list_user_id' => 10, 'created_by_user_id' => 20],
            ['title' => 'hidden', 'visibility' => 'hidden', 'list_user_id' => 20, 'created_by_user_id' => 30],
            ['title' => 'private', 'visibility' => 'private', 'list_user_id' => 20, 'created_by_user_id' => 30],
        ]);

        self::assertSame(['public'], Cadeau::query()->visibleFor(null)->pluck('title')->all());
    }
}
