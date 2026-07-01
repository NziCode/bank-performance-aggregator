<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    'show_warnings' => false,
    'public_path'   => null,
    'convert_entities' => true,

    'options' => [
        'font_dir'             => storage_path('fonts/'),
        'font_cache'           => storage_path('fonts/'),
        'temp_dir'             => sys_get_temp_dir(),
        'chroot'               => realpath(base_path()),
        'allowed_protocols'    => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
        ],
        'log_output_file'      => null,
        'default_media_type'   => 'screen',
        'default_paper_size'   => 'a4',
        'default_paper_orientation' => 'portrait',
        'default_font'         => 'vazirmatn',
        'dpi'                  => 96,
        'font_height_ratio'    => 1.1,
        'enable_php'           => false,
        'enable_javascript'    => true,
        'enable_remote'        => false,
        'allowed_remote_hosts' => null,
        'debugPng'             => false,
        'debugKeepTemp'        => false,
        'debugCss'             => false,
        'debugLayout'          => false,
        'debugLayoutLines'     => true,
        'debugLayoutBlocks'    => true,
        'debugLayoutInline'    => true,
        'debugLayoutPaddingBox' => true,
        'pdf_backend'          => 'CPDF',
        'pdffontprotection'    => [],
        'unicode_enabled'      => true,
        'isFontSubsettingEnabled' => true,
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => false,
        'isJavascriptEnabled'  => true,
        'isPhpEnabled'         => false,
    ],
];
