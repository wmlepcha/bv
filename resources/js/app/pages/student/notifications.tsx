import PlaceholderPage from '@app/components/placeholder-page';
import { index as notificationsIndex } from '@/routes/student/notifications';

export default function Notifications() {
    return (
        <PlaceholderPage
            title="Notifications"
            description="Stay up to date with announcements and reminders from Bilingual Vitae."
        />
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: notificationsIndex(),
        },
    ],
};
