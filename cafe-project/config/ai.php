<?php

return [
    'openrouter_key' => env('GEMINI_API_KEY'),
    'openrouter_url' => env('GEMINI_API_URL'),

    'models' => [
        'primary'   => env('GEMINI_AI_MODEL'),
        'secondary' => env('AI_SECONDARY_MODEL'),
        'tertiary'  => env('AI_TERTIARY_MODEL'),
    ],

    'summary_threshold' => 6,   // Trigger summary sau mỗi N user messages
    'history_limit'     => 20,  // Số messages gần nhất gửi lên AI
    'max_tool_rounds'   => 3,   // Vòng lặp tool calling tối đa / request
    'max_tokens'        => 1024,
    'temperature'       => 0.7,
];