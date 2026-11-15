<?php

namespace App\Navigation;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

final class ParticipantListContext
{
    private function __construct(
        private string $routeParameter,
        private string $sessionKey,
    ) {}

    public static function forList(): self
    {
        return new self('listId', 'listId');
    }

    public static function forTarget(): self
    {
        return new self('targetId', 'targetId');
    }

    public function id(): ?int
    {
        $route = request()->route();
        $routeId = $route?->parameter($this->routeParameter);

        $id = $routeId ?? session()->get($this->sessionKey);

        return $id === null ? null : (int) $id;
    }

    public function name(): ?string
    {
        $id = $this->id();

        return $id === null ? null : User::find($id)?->name;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function withCurrentId(array $parameters): array
    {
        if (! array_key_exists($this->routeParameter, $parameters)) {
            $parameters[$this->routeParameter] = $this->id();
        }

        return $parameters;
    }

    public function remember(int $id): void
    {
        session()->put($this->sessionKey, $id);
    }

    public function isCurrent(string $routeBaseName, int $id): bool
    {
        $route = request()->route();

        return request()->routeIs($routeBaseName.'.*')
            && (int) $route?->parameter($this->routeParameter) === $id;
    }

    public function navigationItems(?int $excludeId, Closure $makeItem): array
    {
        $items = [];

        foreach ($this->participants() as $id => $name) {
            if ($excludeId !== null && (int) $id === $excludeId) {
                continue;
            }

            $items[] = $makeItem((int) $id, $name);
        }

        return $items;
    }

    /**
     * @return array<int|string, string>
     */
    private function participants(): array
    {
        return Cache::remember(
            'resource-list-id-nav-items',
            3600,
            fn (): array => User::orderBy('name')->select('id', 'name')->pluck('name', 'id')->toArray(),
        );
    }
}
