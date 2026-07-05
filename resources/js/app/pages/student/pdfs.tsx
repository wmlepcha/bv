import PlaceholderPage from '@app/components/placeholder-page';
import { index as pdfsIndex } from '@/routes/student/pdfs';

export default function Pdfs() {
    return (
        <PlaceholderPage
            title="PDFs and resources"
            description="Download study materials, worksheets, and reference documents."
        />
    );
}

Pdfs.layout = {
    breadcrumbs: [
        {
            title: 'PDFs',
            href: pdfsIndex(),
        },
    ],
};
