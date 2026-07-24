<?php

return [
    'ai_key' => env('GEMINI_API_KEY'),
    'ai_url' => env('GEMINI_API_URL'),

    'models' => [
        'primary'   => env('GEMINI_AI_MODEL'),
        'secondary' => env('GEMINI_AI_MODEL_2'),
        'tertiary'  => env('GEMINI_AI_MODEL_3'),
    ],

    'summary_threshold' => 6,   // Trigger summary sau mỗi N user messages
    'history_limit'     => 20,  // Số messages gần nhất gửi lên AI
    'max_tool_rounds'   => 3,   // Vòng lặp tool calling tối đa / request
    'max_tokens'        => 1024,
    'temperature'       => 0.7,
];