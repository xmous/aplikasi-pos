<?php

return [
    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],

    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => resource_path('views/pages'),
    ],

    'component_layout' => 'layouts::app',
    'component_placeholder' => null,

    'make_command' => [
        'type' => 'class',
        'emoji' => false,
        'with' => [
            'js' => false,
            'css' => false,
            'test' => true,
        ],
    ],

    'class_namespace' => 'App\\Livewire',
    'class_path' => app_path('Livewire'),
    'view_path' => resource_path('views/livewire'),

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:2048'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:30,1',
        'preview_mimes' => ['png', 'jpg', 'jpeg', 'webp'],
        'max_upload_time' => 5,
        'cleanup' => true,
        'chunking' => true,
        'chunk_size' => 1024 * 1024,
        'chunk_threshold' => 1024 * 1024,
    ],

    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#0d6efd',
    ],

    'inject_morph_markers' => true,
    'smart_wire_keys' => true,
    'pagination_theme' => 'bootstrap',
    'release_token' => env('LIVEWIRE_RELEASE_TOKEN', 'pos-secure-v1'),

    // Memakai evaluator Alpine/Livewire yang tidak memerlukan unsafe-eval.
    'csp_safe' => true,

    'payload' => [
        'max_size' => 1024 * 1024,
        'max_nesting_depth' => 10,
        'max_calls' => 50,
        'max_components' => 40,
    ],
];
