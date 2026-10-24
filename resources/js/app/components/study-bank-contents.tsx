import { router } from '@inertiajs/react';
import { Bookmark } from 'lucide-react';
import { useState } from 'react';
import {
    destroy as destroyLearningItem,
    store as storeLearningItem,
} from '@/routes/student/my-learning';
import { download as studyBankDownload } from '@/routes/student/study-bank/files';
import { show as studyBankFolder } from '@/routes/student/study-bank/folders';
import { MaterialFileCard } from '@app/components/material-file-card';
import { MaterialFolderCard } from '@app/components/material-folder-card';
import { MaterialPreviewDialog } from '@app/components/material-preview-dialog';

export type StudyBankFolder = {
    id: number;
    name: string;
    type: string;
};

export type StudyBankFile = {
    id: number;
    name: string;
    extension: string;
    kind?: 'file' | 'youtube' | 'youtube_playlist' | 'link';
    url?: string | null;
    embed_url?: string | null;
    playlist_items?: Array<{ video_id: string; title: string }> | null;
    preview_url?: string | null;
    folder_name?: string | null;
    created_at: string | null;
    learning_item_id?: number | null;
    type: string;
};

export function StudyBankContents({
    folders,
    files,
}: {
    folders: StudyBankFolder[];
    files: StudyBankFile[];
}) {
    const [openId, setOpenId] = useState<number | null>(null);
    const openFile = files.find((file) => file.id === openId) ?? null;

    const toggleLearning = (file: StudyBankFile) => {
        if (file.learning_item_id != null) {
            router.delete(destroyLearningItem.url(file.learning_item_id), {
                preserveScroll: true,
                onSuccess: () => router.flushAll(),
            });

            return;
        }

        router.post(
            storeLearningItem.url(),
            { material_file_id: file.id },
            {
                preserveScroll: true,
                onSuccess: () => router.flushAll(),
            },
        );
    };

    return (
        <div className="space-y-8">
            {folders.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-sm font-medium text-muted-foreground">
                        Folders
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
                        {folders.map((folder) => (
                            <MaterialFolderCard
                                key={`folder-${folder.id}`}
                                name={folder.name}
                                href={studyBankFolder.url(folder.id)}
                                itemLabel="Folder"
                                colorIndex={folder.id}
                            />
                        ))}
                    </div>
                </section>
            )}

            {files.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-sm font-semibold tracking-tight">
                        Materials
                    </h2>
                    <p className="flex flex-wrap items-center gap-x-1 text-sm text-muted-foreground">
                        Tap
                        <Bookmark className="inline size-4 text-foreground/70" />
                        to save a material to My Learning. There you can open it
                        and write your own private notes.
                    </p>
                    <div className="rounded-2xl border border-border/60 bg-muted/20 p-2 sm:p-3">
                        {files.map((file) => (
                            <MaterialFileCard
                                key={`file-${file.id}`}
                                name={file.name}
                                extension={file.extension}
                                kind={file.kind ?? 'file'}
                                url={file.url}
                                embedUrl={file.embed_url}
                                playlistItems={file.playlist_items}
                                playlistItemsUrl={
                                    (file.kind ?? 'file') === 'youtube_playlist'
                                        ? `/student/study-bank/files/${file.id}/playlist-items`
                                        : null
                                }
                                uploadedAt={file.created_at}
                                categoryLabel={file.folder_name ?? undefined}
                                downloadHref={
                                    (file.kind ?? 'file') === 'file'
                                        ? studyBankDownload.url(file.id)
                                        : null
                                }
                                isSavedToLearning={
                                    file.learning_item_id != null
                                }
                                onToggleLearning={() => toggleLearning(file)}
                                learningItemId={file.learning_item_id}
                                onOpen={() => setOpenId(file.id)}
                            />
                        ))}
                    </div>
                </section>
            )}
            <MaterialPreviewDialog
                material={
                    openFile
                        ? {
                              name: openFile.name,
                              extension: openFile.extension,
                              kind:
                                  (openFile.kind ?? 'file') === 'link'
                                      ? 'link'
                                      : 'file',
                              url: openFile.url,
                              previewUrl: openFile.preview_url,
                              downloadHref:
                                  (openFile.kind ?? 'file') === 'file'
                                      ? studyBankDownload.url(openFile.id)
                                      : null,
                              saved: openFile.learning_item_id != null,
                          }
                        : null
                }
                onClose={() => setOpenId(null)}
                onToggleSaved={() => openFile && toggleLearning(openFile)}
            />
        </div>
    );
}
