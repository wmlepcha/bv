import {
    Bell,
    BookOpen,
    FileText,
    FileUser,
    FolderOpen,
    LayoutGrid,
    Presentation,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { edit as cvEdit } from '@/routes/student/cv';
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
                title: 'Your CV',
                href: cvEdit(),
                icon: FileUser,
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

    if (role === 'training_manager') {
        return [
            {
                title: 'Dashboard',
                href: '/training-manager/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Trainers',
                href: '/training-manager/trainers',
                icon: Presentation,
            },
            {
                title: 'Batches',
                href: '/training-manager/batches',
                icon: BookOpen,
            },
            {
                title: 'Library',
                href: '/training-manager/library',
                icon: FolderOpen,
            },
        ];
    }

    if (role === 'teacher') {
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
            {
                title: 'My Materials',
                href: '/teacher/materials',
                icon: FolderOpen,
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
