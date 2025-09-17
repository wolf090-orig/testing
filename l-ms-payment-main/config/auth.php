<?php

return [
    'jwt_secret' => getenv('JWT_SECRET', '123'),
    'refresh_token_expire_days' => getenv("REFRESH_TOKEN_EXPIRE_DAYS", 7),
    'verification_timeoute_minutes' => getenv('VERIFICATION_TIMEOUT_MINUTES', 15),
    'max_code_tries' => getenv('MAX_CODE_TRIES', 5),
];
