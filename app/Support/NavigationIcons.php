<?php

namespace App\Support;

class NavigationIcons
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'home' => 'Home',
            'users' => 'About / Users',
            'briefcase' => 'Services',
            'folder-kanban' => 'Projects',
        ];
    }
}
