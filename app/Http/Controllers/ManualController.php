<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    public function index(Request $request): View
    {
        $sections = collect(config('manual.sections', []))
            ->filter(fn (array $section): bool => $this->canView(
                $request,
                $section
            ))
            ->map(function (array $section) use (
                $request
            ): array {
                $routeName = $section['route'] ?? null;

                $section['url'] = $routeName && Route::has(
                    $routeName
                )
                    ? route(
                        $routeName,
                        $section['route_params'] ?? []
                    )
                    : null;

                $defaultStepAction = $routeName
                    ? [
                        'route' => $routeName,
                        'route_params' => $section[
                            'route_params'
                        ] ?? [],
                        'label' => $section['route_label']
                            ?? 'Buka '.$section['title'],
                        'icon' => $section['icon']
                            ?? 'fa-arrow-up-right-from-square',
                        'tone' => $section['color']
                            ?? 'primary',
                        'permissions' => $section[
                            'permissions'
                        ] ?? [],
                        'roles' => $section['roles'] ?? [],
                    ]
                    : null;

                $section['prerequisites'] = $this
                    ->normalizeManualItems(
                        $request,
                        $section['prerequisites'] ?? [],
                        null,
                        $section['slug'],
                        'prerequisites'
                    );

                $section['steps'] = $this
                    ->normalizeManualItems(
                        $request,
                        $section['steps'] ?? [],
                        $defaultStepAction,
                        $section['slug'],
                        'steps'
                    );

                $section['tips'] = $this
                    ->normalizeManualItems(
                        $request,
                        $section['tips'] ?? [],
                        null,
                        $section['slug'],
                        'tips'
                    );

                $section['warnings'] = $this
                    ->normalizeManualItems(
                        $request,
                        $section['warnings'] ?? [],
                        null,
                        $section['slug'],
                        'warnings'
                    );

                $section['search_text'] = mb_strtolower(
                    implode(' ', [
                        $section['title'] ?? '',
                        $section['summary'] ?? '',
                        implode(
                            ' ',
                            $section['keywords'] ?? []
                        ),
                        $this->manualItemsSearchText(
                            $section['prerequisites']
                        ),
                        $this->manualItemsSearchText(
                            $section['steps']
                        ),
                        $this->manualItemsSearchText(
                            $section['tips']
                        ),
                        $this->manualItemsSearchText(
                            $section['warnings']
                        ),
                    ])
                );

                return $section;
            })
            ->values();

        return view('manual.index', [
            'manualTitle' => config(
                'manual.title',
                'Panduan Pengguna POS & Stok'
            ),
            'sections' => $sections,
            'categories' => $sections
                ->pluck('category')
                ->unique()
                ->values(),
            'permissionCount' => $request->user()
                ->getAllPermissions()
                ->count(),
        ]);
    }

    public function download(string $format): BinaryFileResponse
    {
        abort_unless(
            Gate::allows('settings.manage')
                || Gate::allows('roles.manage'),
            403
        );

        abort_unless(
            in_array($format, ['pdf', 'docx'], true),
            404
        );

        $filename = 'manual-book-pos-stok.'.$format;
        $path = storage_path('app/manual/'.$filename);

        abort_unless(is_file($path), 404);

        return response()->download(
            $path,
            $filename,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }

    private function normalizeManualItems(
        Request $request,
        array $items,
        ?array $defaultAction = null,
        string $sectionSlug = 'section',
        string $group = 'items'
    ): array {
        return collect($items)
            ->map(function (
                array|string $item,
                int $index
            ) use (
                $request,
                $defaultAction,
                $sectionSlug,
                $group
            ): array {
                $normalized = is_array($item)
                    ? $item
                    : ['text' => $item];

                $actions = $normalized['actions'] ?? [];

                if (
                    $actions === []
                    && $defaultAction !== null
                ) {
                    $actions = [$defaultAction];
                }

                $normalized['actions'] = collect($actions)
                    ->map(
                        fn (array $action): ?array =>
                            $this->resolveManualAction(
                                $request,
                                $action
                            )
                    )
                    ->filter()
                    ->unique('url')
                    ->values()
                    ->all();

                $normalized['anchor'] = sprintf(
                    'manual-%s-%s-%d',
                    $sectionSlug,
                    $group,
                    $index + 1
                );

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function resolveManualAction(
        Request $request,
        array $action
    ): ?array {
        if (! $this->canUseManualAction(
            $request,
            $action
        )) {
            return null;
        }

        $manualSlug = $action['manual_slug'] ?? null;
        $routeName = $action['route'] ?? null;
        $url = null;

        if ($manualSlug) {
            $url = route('manual.index')
                .'#manual-'.$manualSlug;
        } elseif ($routeName && Route::has($routeName)) {
            $url = route(
                $routeName,
                $action['route_params'] ?? []
            );
        } elseif (! empty($action['url'])) {
            $url = $action['url'];
        }

        if (! $url) {
            return null;
        }

        $fragment = trim(
            (string) ($action['fragment'] ?? ''),
            '# '
        );

        if ($fragment !== '') {
            $url .= '#'.$fragment;
        }

        return [
            'url' => $url,
            'label' => $action['label']
                ?? 'Buka Menu',
            'icon' => $action['icon']
                ?? 'fa-arrow-up-right-from-square',
            'tone' => $action['tone']
                ?? 'primary',
            'manual_target' => $manualSlug
                ? 'manual-'.$manualSlug
                : null,
        ];
    }

    private function canUseManualAction(
        Request $request,
        array $action
    ): bool {
        $requiredRoles = collect(
            Arr::wrap($action['roles'] ?? [])
        )->map(
            fn (string $role): string =>
                mb_strtolower($role)
        );

        if ($requiredRoles->isNotEmpty()) {
            $userRoles = $request->user()
                ->getRoleNames()
                ->map(
                    fn (string $role): string =>
                        mb_strtolower($role)
                );

            if ($requiredRoles
                ->intersect($userRoles)
                ->isEmpty()
            ) {
                return false;
            }
        }

        $permissions = Arr::wrap(
            $action['permissions'] ?? []
        );

        if ($permissions === []) {
            return true;
        }

        return collect($permissions)->contains(
            fn (string $permission): bool =>
                Gate::allows($permission)
        );
    }

    private function manualItemsSearchText(
        array $items
    ): string {
        return collect($items)
            ->map(function (array $item): string {
                $actionLabels = collect(
                    $item['actions'] ?? []
                )->pluck('label')->implode(' ');

                return trim(
                    ($item['text'] ?? '')
                    .' '
                    .($item['context'] ?? '')
                    .' '
                    .$actionLabels
                );
            })
            ->implode(' ');
    }

    private function canView(
        Request $request,
        array $section
    ): bool {
        $requiredRoles = collect(
            Arr::wrap($section['roles'] ?? [])
        )->map(
            fn (string $role): string => mb_strtolower($role)
        );

        if ($requiredRoles->isNotEmpty()) {
            $userRoles = $request->user()
                ->getRoleNames()
                ->map(
                    fn (string $role): string => mb_strtolower($role)
                );

            if ($requiredRoles->intersect($userRoles)->isEmpty()) {
                return false;
            }
        }

        $permissions = Arr::wrap(
            $section['permissions'] ?? []
        );

        if ($permissions === []) {
            return true;
        }

        return collect($permissions)->contains(
            fn (string $permission): bool => Gate::allows(
                $permission
            )
        );
    }
}
