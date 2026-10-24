import { ListVideo } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/responsive-select';

type PlaylistItem = { video_id: string; title: string };

/** Playlist video picker: a bottom sheet on phones, a styled menu on desktop. */
export function PlaylistVideoSelect({
    items,
    value,
    onChange,
    loading,
}: {
    items: PlaylistItem[];
    value: string | null;
    onChange: (videoId: string) => void;
    loading: boolean;
}) {
    const disabled = loading || items.length === 0;

    return (
        <Select
            value={value ?? undefined}
            onValueChange={onChange}
            disabled={disabled}
        >
            <SelectTrigger className="h-12 w-full gap-2 rounded-2xl border-border/70 bg-card px-3.5 text-left text-base font-medium shadow-sm">
                <ListVideo className="size-5 shrink-0 text-primary" />
                <SelectValue
                    placeholder={
                        loading ? 'Loading videos…' : 'No videos available'
                    }
                />
            </SelectTrigger>
            <SelectContent>
                {items.map((item, index) => (
                    <SelectItem key={item.video_id} value={item.video_id}>
                        {index + 1}. {item.title}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
