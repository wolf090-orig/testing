<?php

namespace app\libraries\kafka\config;

class Sasl
{
    private string $username;
    private string $password;
    private string $mechanisms;
    private string $securityProtocol;

    public function __construct(
        string $username,
        string $password,
        string $mechanisms,
        string $securityProtocol = 'SASL_PLAINTEXT'
    ) {
        $this->securityProtocol = $securityProtocol;
        $this->mechanisms = $mechanisms;
        $this->password = $password;
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getMechanisms(): string
    {
        return $this->mechanisms;
    }

    public function getSecurityProtocol(): string
    {
        return $this->securityProtocol;
    }
}
