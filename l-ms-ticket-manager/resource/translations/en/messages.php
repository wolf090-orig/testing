<?php

return [
    "response" => [
        "success" => "Successfully",
        "sms_notified" => "A message with the confirmation code has been sent to the number :phone",
        "sms_error" => "Unable to send SMS to this number, please try again later"
    ],
    "validation" => [
        "phone" => "The given phone number is invalid. Please enter a valid Uzbekistan phone number in the format: 998991234567.",
        "refresh_token" => "Refresh token should be string and contain only characters and numbers",
        "code" => "Code cannot exceed 4 digits",
        "lottery_type" => "The field must be one of: daily_fixed, daily_dynamic, jackpot, supertour.",
        "ticket_status" => "The field must be one of: history, active, winner.",
        "status" => "The field must be one of: history, active, winner.",
        "lottery_id" => "The field must be a positive integer, not less than 1",
        "with_leaderboard" => "The field must be boolean",
        "page" => "The field must be a number",
        "page_size" => "The field must be a number",
    ]
];
