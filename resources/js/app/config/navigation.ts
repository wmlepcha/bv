import {
    Bell,
    CalendarCheck,
    BookOpen,
    Briefcase,
    ClipboardList,
    FileUser,
    FolderOpen,
    Inbox,
    LayoutGrid,
    LifeBuoy,
    MessageCircle,
    MessageSquareText,
    Presentation,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/recruiter/projects';
import { index as studentsIndex } from '@/routes/recruiter/students';
import { index as studentAttendanceIndex } from '@/routes/student/attendance';
import { edit as cvEdit } from '@/routes/student/cv';
import { index as helpCenterIndex } from '@/routes/student/help-center';
import { index as myLearningIndex } from '@/routes/student/my-learning';
import { index as notificationsIndex } from '@/routes/student/notifications';
import { index as studyBankIndex } from '@/routes/student/study-bank';
import { index as teacherAttendanceIndex } from '@/routes/teacher/attendance';
import { index as teacherMessagesIndex } from '@/routes/teacher/messages';
import type { NavItem } from '@/types';
import type { UserRole } from '@/types/auth';

export function getMainNavItems(
    role: UserRole,
    unreadMessagesCount = 0,
    unreadNotificationsCount = 0,
): NavItem[] {
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
                title: 'My Learning',
                href: myLearningIndex(),
                icon: BookOpen,
            },
            {
                title: 'Assignments',
                href: '/student/assignments',
                icon: ClipboardList,
            },
            {
                title: 'Attendance',
                href: studentAttendanceIndex(),
                icon: CalendarCheck,
            },
            {
                title: 'Study Bank',
                href: studyBankIndex(),
                icon: FolderOpen,
            },
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
                badge: unreadNotificationsCount || undefined,
            },
            {
                title: 'Help Center',
                href: helpCenterIndex(),
                icon: LifeBuoy,
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
                title: 'Assignments',
                href: '/training-manager/assignments',
                icon: ClipboardList,
            },
            {
                title: 'Library',
                href: '/training-manager/library',
                icon: FolderOpen,
            },
            {
                title: 'Feedback',
                href: '/training-manager/feedback',
                icon: MessageSquareText,
            },
        ];
    }

    if (role === 'recruitment_manager') {
        return [
            {
                title: 'Dashboard',
                href: '/recruitment-manager/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Candidates',
                href: '/recruitment-manager/candidates',
                icon: Users,
            },
            {
                title: 'Submissions',
                href: '/recruitment-manager/projects',
                icon: Briefcase,
            },
            {
                title: 'Inquiries',
                href: '/recruitment-manager/inquiries',
                icon: Inbox,
            },
            {
                title: 'Recruiters',
                href: '/recruitment-manager/recruiters',
                icon: FileUser,
            },
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
                badge: unreadNotificationsCount || undefined,
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
                title: 'Assignments',
                href: '/teacher/assignments',
                icon: ClipboardList,
            },
            {
                title: 'Attendance',
                href: teacherAttendanceIndex(),
                icon: CalendarCheck,
            },
            {
                title: 'My Materials',
                href: '/teacher/materials',
                icon: FolderOpen,
            },
            {
                title: 'Messages',
                href: teacherMessagesIndex(),
                icon: MessageCircle,
                badge: unreadMessagesCount || undefined,
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
            {
                title: 'Projects',
                href: projectsIndex(),
                icon: Briefcase,
            },
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
                badge: unreadNotificationsCount || undefined,
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
