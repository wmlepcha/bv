import {
    Briefcase,
    FolderKanban,
    Home,
    Users,
    type LucideIcon,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    home: Home,
    users: Users,
    briefcase: Briefcase,
    'folder-kanban': FolderKanban,
};

export function resolveNavIcon(key: string): LucideIcon {
    return iconMap[key] ?? Home;
}
