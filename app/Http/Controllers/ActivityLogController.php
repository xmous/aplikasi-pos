<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'user_id' => (string) $request->query('user_id', ''),
            'action_group' => trim((string) $request->query('action_group', '')),
            'subject_type' => trim((string) $request->query('subject_type', '')),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'per_page' => (int) $request->query('per_page', 30),
        ];

        if (! in_array($filters['per_page'], [20, 30, 50, 100], true)) {
            $filters['per_page'] = 30;
        }

        $query = ActivityLog::query()->with('user');

        if ($filters['q'] !== '') {
            $terms = preg_split('/\s+/', $filters['q']) ?: [];

            foreach (array_filter($terms) as $term) {
                $like = '%'.$term.'%';

                $query->where(function (Builder $builder) use ($like, $term): void {
                    $builder
                        ->where('action', 'like', $like)
                        ->orWhere('subject_type', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhere('user_agent', 'like', $like)
                        ->orWhereHas('user', function (Builder $user) use ($like): void {
                            $user
                                ->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });

                    if (ctype_digit($term)) {
                        $builder->orWhere('subject_id', (int) $term);
                    }
                });
            }
        }

        $query
            ->when(
                $filters['user_id'] !== '',
                fn (Builder $builder) => $builder->where(
                    'user_id',
                    (int) $filters['user_id']
                )
            )
            ->when(
                $filters['action_group'] !== '',
                fn (Builder $builder) => $builder->where(
                    'action',
                    'like',
                    $filters['action_group'].'.%'
                )
            )
            ->when(
                $filters['subject_type'] !== '',
                fn (Builder $builder) => $builder->where(
                    'subject_type',
                    $filters['subject_type']
                )
            )
            ->when(
                $filters['date_from'] !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'created_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                $filters['date_to'] !== '',
                fn (Builder $builder) => $builder->whereDate(
                    'created_at',
                    '<=',
                    $filters['date_to']
                )
            );

        $actions = ActivityLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $actionGroups = $actions
            ->map(fn (string $action): string => str($action)->before('.')->toString())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $subjectTypes = ActivityLog::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type');

        $stats = [
            'total' => ActivityLog::query()->count(),
            'today' => ActivityLog::query()
                ->whereDate('created_at', today())
                ->count(),
            'actors' => ActivityLog::query()
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'system' => ActivityLog::query()
                ->whereNull('user_id')
                ->count(),
        ];

        return view('activity.index', [
            'logs' => $query
                ->latest('created_at')
                ->paginate($filters['per_page'])
                ->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'actionGroups' => $actionGroups,
            'subjectTypes' => $subjectTypes,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }
}
