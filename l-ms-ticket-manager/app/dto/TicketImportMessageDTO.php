<?php

namespace app\dto;

class TicketImportMessageDTO
{
    public array $body;
    public array $headers;

    public function __construct(array $body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
