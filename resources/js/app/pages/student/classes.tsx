import PlaceholderPage from '@app/components/placeholder-page';
import { index as classesIndex } from '@/routes/student/classes';

export default function Classes() {
    return (
        <PlaceholderPage
            title="Pre-recorded classes"
            description="Watch your language training classes and replays here."
        />
    );
}

Classes.layout = {
    breadcrumbs: [
        {
            title: 'Classes',
            href: classesIndex(),
        },
    ],
};
