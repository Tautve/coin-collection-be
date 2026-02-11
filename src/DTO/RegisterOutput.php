<?php

declare(strict_types=1);

namespace App\DTO;

final class RegisterOutput
{
    public string $id;
    public string $email;
    public string $message = 'User registered successfully';
}
