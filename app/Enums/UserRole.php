<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Recruiter = 'recruiter';
    case Employee = 'employee';
    case Admin = 'admin';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function staffRoles(): array
    {
        return [
            self::Recruiter,
            self::Employee,
            self::Admin,
        ];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staffRoles(), true);
    }
}
