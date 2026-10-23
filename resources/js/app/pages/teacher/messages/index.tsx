import { Link, router, useForm } from '@inertiajs/react';
import { Image as ImageIcon, Send } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    index as messagesIndex,
    store as storeRoute,
} from '@/routes/teacher/messages';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Conversation = {
    id: number;
    student_name: string;
    unread_count: number;
    last_message_preview: string | null;
    last_message_at: string | null;
};

type ChatMessage = {
    id: number;
    body: string | null;
    from_me: boolean;
    sender_name: string;
    attachment_url: string | null;
    created_at: string;
};

type Props = {
    conversations: Conversation[];
    selectedConversationId: number | null;
    messages: ChatMessage[];
};

const POLL_INTERVAL_MS = 5000;

function relativeTime(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const diffMs = Date.now() - date.getTime();
    const diffMinutes = Math.round(diffMs / 60000);

    if (diffMinutes < 1) {
        return 'Just now';
    }

    if (diffMinutes < 60) {
        return `${diffMinutes}m ago`;
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

export default function TeacherMessagesIndex({
    conversations,
    selectedConversationId,
    messages,
}: Props) {
    const listRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, reset, processing } = useForm<{
        body: string;
        attachment: File | null;
    }>({
        body: '',
        attachment: null,
    });

    useEffect(() => {
        const interval = window.setInterval(() => {
            router.reload({
                only: ['conversations', 'messages'],
            });
        }, POLL_INTERVAL_MS);

        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        const list = listRef.current;

        if (list) {
            list.scrollTop = list.scrollHeight;
        }
    }, [messages]);

    const selectConversation = (id: number) => {
        router.get(
            messagesIndex.url({ query: { conversation: id } }),
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    const send = () => {
        if (
            selectedConversationId === null ||
            (!data.body.trim() && !data.attachment)
        ) {
            return;
        }

        post(storeRoute.url(selectedConversationId), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const selectedConversation = conversations.find(
        (conversation) => conversation.id === selectedConversationId,
    );

    return (
        <>
            <PrivateSeoHead title="Messages" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Messages
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Direct messages from students in your batches.
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-[minmax(280px,320px)_1fr]">
                    <aside
                        className={cn(
                            'flex flex-col gap-2 rounded-2xl border border-border/70 bg-card p-3 shadow-[0_12px_40px_-18px_rgba(3,24,53,0.35)]',
                            selectedConversationId && 'hidden lg:flex',
                        )}
                    >
                        {conversations.length === 0 ? (
                            <p className="p-3 text-sm text-muted-foreground">
                                No students have messaged you yet.
                            </p>
                        ) : (
                            <div className="flex flex-col gap-1 overflow-y-auto">
                                {conversations.map((conversation) => (
                                    <button
                                        key={conversation.id}
                                        type="button"
                                        onClick={() =>
                                            selectConversation(conversation.id)
                                        }
                                        className={cn(
                                            'flex flex-col gap-0.5 rounded-xl px-3 py-2.5 text-left transition-colors',
                                            conversation.id ===
                                                selectedConversationId
                                                ? 'bg-primary/10'
                                                : 'hover:bg-muted',
                                        )}
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="truncate text-sm font-semibold">
                                                {conversation.student_name}
                                            </p>
                                            {conversation.unread_count > 0 && (
                                                <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">
                                                    {conversation.unread_count}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="truncate text-xs text-muted-foreground">
                                                {conversation.last_message_preview ??
                                                    'No messages yet'}
                                            </p>
                                            <span className="shrink-0 text-[11px] text-muted-foreground">
                                                {relativeTime(
                                                    conversation.last_message_at,
                                                )}
                                            </span>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        )}
                    </aside>

                    <section
                        className={cn(
                            'flex min-h-[60vh] flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-[0_12px_40px_-18px_rgba(3,24,53,0.35)]',
                            !selectedConversationId && 'hidden lg:flex',
                        )}
                    >
                        {selectedConversation ? (
                            <>
                                <div className="flex items-center gap-2 border-b border-border/60 px-4 py-3">
                                    <Link
                                        href={messagesIndex.url()}
                                        className="text-sm text-muted-foreground hover:text-foreground lg:hidden"
                                    >
                                        ← Back
                                    </Link>
                                    <p className="text-sm font-semibold">
                                        {selectedConversation.student_name}
                                    </p>
                                </div>

                                <div
                                    ref={listRef}
                                    className="flex-1 space-y-3 overflow-y-auto p-4"
                                >
                                    {messages.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            No messages yet.
                                        </p>
                                    )}
                                    {messages.map((message) => (
                                        <div
                                            key={message.id}
                                            className={cn(
                                                'flex',
                                                message.from_me
                                                    ? 'justify-end'
                                                    : 'justify-start',
                                            )}
                                        >
                                            <div
                                                className={cn(
                                                    'max-w-[70%] rounded-2xl px-3 py-2 text-sm',
                                                    message.from_me
                                                        ? 'rounded-br-sm bg-primary text-primary-foreground'
                                                        : 'rounded-bl-sm bg-muted text-foreground',
                                                )}
                                            >
                                                {message.attachment_url && (
                                                    <img
                                                        src={
                                                            message.attachment_url
                                                        }
                                                        alt="Attachment"
                                                        className="mb-1 max-h-64 rounded-lg object-cover"
                                                    />
                                                )}
                                                {message.body && (
                                                    <p className="break-words whitespace-pre-wrap">
                                                        {message.body}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="border-t border-border/60 p-3">
                                    {data.attachment && (
                                        <div className="mb-2 flex items-center justify-between rounded-lg bg-muted/60 px-3 py-1.5 text-xs">
                                            <span className="truncate">
                                                {data.attachment.name}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setData('attachment', null)
                                                }
                                                className="ml-2 shrink-0 text-muted-foreground hover:text-foreground"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    )}
                                    <div className="flex items-end gap-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                fileInputRef.current?.click()
                                            }
                                            className="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            aria-label="Attach an image"
                                        >
                                            <ImageIcon className="size-5" />
                                        </button>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            className="hidden"
                                            onChange={(event) =>
                                                setData(
                                                    'attachment',
                                                    event.target.files?.[0] ??
                                                        null,
                                                )
                                            }
                                        />
                                        <Textarea
                                            value={data.body}
                                            onChange={(event) =>
                                                setData(
                                                    'body',
                                                    event.target.value,
                                                )
                                            }
                                            onKeyDown={(event) => {
                                                if (
                                                    event.key === 'Enter' &&
                                                    !event.shiftKey
                                                ) {
                                                    event.preventDefault();
                                                    send();
                                                }
                                            }}
                                            rows={1}
                                            placeholder="Type a reply…"
                                            className="max-h-24 min-h-9 flex-1 resize-none py-2"
                                        />
                                        <Button
                                            type="button"
                                            size="icon"
                                            disabled={
                                                processing ||
                                                (!data.body.trim() &&
                                                    !data.attachment)
                                            }
                                            onClick={send}
                                            aria-label="Send reply"
                                        >
                                            <Send className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-1 items-center justify-center p-10 text-center text-sm text-muted-foreground">
                                Select a student to view the conversation.
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}

TeacherMessagesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Messages',
            href: messagesIndex(),
        },
    ],
};
