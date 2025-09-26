<?php

namespace Guava\Calendar\Concerns;

use Guava\Calendar\Contracts\Resourceable;
use Guava\Calendar\ValueObjects\CalendarResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasResources
{
    protected function getResources(): Collection | array | Builder
    {
        return [];
    }

    public function getResourcesJs(): array
    {
        $resources = $this->getResources();

        if ($resources instanceof Builder) {
            $resources = $resources->get();
        }

        if (is_array($resources)) {
            $resources = collect($resources);
        }

        return $resources
            ->map(function (Resourceable|CalendarResource $resource): array {
                if ($resource instanceof Resourceable) {
                    $resource = $resource->toCalendarResource();
                }

                return $this->mapResourceToArray($resource);
            })
            ->values() // optional: reindex keys
            ->toArray();
    }

    protected function mapResourceToArray(CalendarResource $resource): array
    {
        $data = $resource->toCalendarObject();

        if (!empty($data['children'])) {
            $data['children'] = collect($data['children'])
                ->map(function ($child) {
                    // Child may be a CalendarResource or already an array
                    if ($child instanceof CalendarResource) {
                        return $this->mapResourceToArray($child);
                    }

                    // If it’s some object with toCalendarObject(), handle gracefully
                    if (is_object($child) && method_exists($child, 'toCalendarObject')) {
                        return $child->toCalendarObject();
                    }

                    return $child; // array/scalar
                })
                ->values()
                ->toArray();
        }

        return $data;
    }
}
