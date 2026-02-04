<?php

namespace App\Exceptions;

use Throwable;

class PolicyException extends \Exception
{
    public function __construct(
        string $message,
        private string $policyCode = 'POLICY_VIOLATION'
    ) {
        parent::__construct($message);
    }

    public function getPolicyCode(): string
    {
        return $this->policyCode;
    }
}
