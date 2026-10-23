import {
    Briefcase,
    FolderKanban,
    Home,
    LogIn,
    Users
    
} from 'lucide-react';
import type {LucideIcon} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    home: Home,
    users: Users,
    briefcase: Briefcase,
    'folder-kanban': FolderKanban,
    'log-in': LogIn,
};

export function resolveNavIcon(key: string): LucideIcon {
    return iconMap[key] ?? Home;
}
