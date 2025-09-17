<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Loan API',
    
    // Loan processing settings
    'loan' => [
        'approvalProbability' => 0.1, // 10% chance of approval
        'maxApprovedLoansPerUser' => 1,
    ],
];