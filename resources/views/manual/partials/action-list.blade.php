@if($ordered)
    <ol class="manual-action-list is-ordered">
@else
    <ul class="manual-action-list">
@endif

    @foreach($items as $item)
        <li
            id="{{ $item['anchor'] }}"
            class="manual-action-item"
            data-manual-item="{{ $group ?? 'items' }}"
        >
            <div class="manual-action-copy">
                <span>{{ $item['text'] }}</span>

                @if($item['context'] ?? null)
                    <em class="manual-context-badge">
                        <i class="fa-solid fa-circle-info"></i>
                        {{ $item['context'] }}
                    </em>
                @endif
            </div>

            @if(($item['actions'] ?? []) !== [])
                <div class="manual-route-tabs no-print">
                    <span class="manual-route-tabs-label">
                        <i class="fa-solid fa-location-arrow"></i>
                        Menu terkait
                    </span>

                    <div class="manual-item-actions">
                        @foreach($item['actions'] as $action)
                            <a
                                href="{{ $action['url'] }}"
                                class="manual-item-action is-{{ $action['tone'] }}"
                                @if($action['manual_target'])
                                    data-manual-anchor="{{ $action['manual_target'] }}"
                                @endif
                            >
                                <i class="fa-solid {{ $action['icon'] }}"></i>
                                <span>{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </li>
    @endforeach

@if($ordered)
    </ol>
@else
    </ul>
@endif
