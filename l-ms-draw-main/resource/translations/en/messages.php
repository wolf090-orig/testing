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
        "code" => "Code cannot exceed 4 digits"

    ]
];
