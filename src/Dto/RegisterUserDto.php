<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserDto
{
    #[Assert\NotBlank(message: 'Поле email не может быть пустым.')]
    #[Assert\Email(message: 'Неверный формат email.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Пароль не может быть пустым')]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен содержать не менее {{ limit }} символов.')]
    public ?string $password = null;
}
