import {
    Bell,
    BookOpen,
    FileText,
    LayoutGrid,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as classesIndex } from '@/routes/student/classes';
import { index as notificationsIndex } from '@/routes/student/notifications';
import { index as pdfsIndex } from '@/routes/student/pdfs';
import { index as studentsIndex } from '@/routes/recruiter/students';
import type { NavItem } from '@/types';
import type { UserRole } from '@/types/auth';

export function getMainNavItems(role: UserRole): NavItem[] {
    if (role === 'student') {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Classes',
                href: classesIndex(),
                icon: BookOpen,
            },
            {
                title: 'PDFs',
                href: pdfsIndex(),
                icon: FileText,
            },
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
            },
        ];
    }

    if (role === 'recruiter' || role === 'employee' || role === 'admin') {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Students',
                href: studentsIndex(),
                icon: Users,
            },
        ];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
}
