import { usePage } from '@inertiajs/react';
import { Image as ImageIcon, MessageCircle, Send, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import {
    read as markReadRoute,
    show as showRoute,
    store as storeRoute,
} from '@/routes/student/messages';
import type { Auth } from '@/types/auth';

type ChatMessage = {
    id: number;
    body: string | null;
    from_me: boolean;
    sender_name: string;
    attachment_url: string | null;
    created_at: string;
};

type ThreadPayload = {
    available: boolean;
    teacher_name: string | null;
    messages: ChatMessage[];
};

export const OPEN_CHAT_EVENT = 'student-chat:open';

const POLL_INTERVAL_MS = 4000;
const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export function StudentChatWidget() {
    const { auth, unread_messages_count: unreadMessagesCount } = usePage<{
        auth: Auth;
        unread_messages_count: number;
    }>().props;

    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);
    const [available, setAvailable] = useState(true);
    const [teacherName, setTeacherName] = useState<string | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [loading, setLoading] = useState(false);
    const [body, setBody] = useState('');
    const [attachment, setAttachment] = useState<File | null>(null);
    const [attachmentError, setAttachmentError] = useState<string | null>(null);
    const [sendError, setSendError] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const listRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const fetchThread = useCallback(() => {
        return fetch(showRoute.url(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload: ThreadPayload | null) => {
                if (!payload) {
                    return;
                }

                setAvailable(payload.available);
                setTeacherName(payload.teacher_name);
                setMessages(payload.messages);
            })
            .catch(() => {});
    }, []);

    useEffect(() => {
        const openChat = () => {
            setLoading(true);
            setOpen(true);
        };

        window.addEventListener(OPEN_CHAT_EVENT, openChat);

        return () => window.removeEventListener(OPEN_CHAT_EVENT, openChat);
    }, []);

    useEffect(() => {
        if (!open) {
            return;
        }

        fetchThread().finally(() => setLoading(false));

        const interval = window.setInterval(fetchThread, POLL_INTERVAL_MS);

        return () => window.clearInterval(interval);
    }, [open, fetchThread]);

    useEffect(() => {
        if (!open) {
            return;
        }

        return () => {
            fetch(markReadRoute.url(), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            }).catch(() => {});
        };
    }, [open]);

    useEffect(() => {
        const list = listRef.current;

        if (list) {
            list.scrollTop = list.scrollHeight;
        }
    }, [messages]);

    const canSend = !sending && (body.trim().length > 0 || attachment !== null);

    const send = (event: FormEvent) => {
        event.preventDefault();

        if (!canSend) {
            return;
        }

        setSending(true);
        setSendError(null);

        const formData = new FormData();

        if (body.trim().length > 0) {
            formData.append('body', body.trim());
        }

        if (attachment) {
            formData.append('attachment', attachment);
        }

        fetch(storeRoute.url(), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: formData,
        })
            .then(async (response) => {
                if (!response.ok) {
                    const errorPayload = (await response
                        .json()
                        .catch(() => null)) as { message?: string } | null;

                    throw new Error(
                        errorPayload?.message ??
                            'Could not send your message. Please try again.',
                    );
                }

                return response.json() as Promise<{ message: ChatMessage }>;
            })
            .then((payload) => {
                setMessages((previous) => [...previous, payload.message]);
                setBody('');
                setAttachment(null);
            })
            .catch((error: Error) => {
                setSendError(error.message || 'Could not send your message.');
            })
            .finally(() => setSending(false));
    };

    const chooseAttachment = (file: File | null) => {
        setAttachmentError(null);

        if (file && file.size > MAX_ATTACHMENT_BYTES) {
            setAttachmentError('Images must be 5 MB or smaller.');
            setAttachment(null);

            return;
        }

        setAttachment(file);
    };

    if (auth.user?.role !== 'student' || !auth.user.can_access_dashboard) {
        return null;
    }

    if (isMobile && !open) {
        return null;
    }

    return (
        <div className="fixed right-4 bottom-[calc(var(--app-bottom-h,0px)+1rem)] z-50 max-md:inset-0 md:right-6 md:bottom-6 print:hidden">
            {open ? (
                <div className="flex h-[70vh] max-h-[560px] w-[92vw] max-w-sm flex-col overflow-hidden rounded-2xl border border-border/60 bg-card shadow-2xl max-md:h-dvh max-md:max-h-none max-md:w-full max-md:max-w-none max-md:rounded-none max-md:border-0">
                    <div className="flex items-center justify-between gap-2 border-b border-border/60 bg-primary px-4 py-3 pt-[calc(0.75rem+env(safe-area-inset-top))] text-primary-foreground md:pt-3">
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold">
                                {teacherName ?? 'Messages'}
                            </p>
                            <p className="text-xs text-primary-foreground/80">
                                Your trainer
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-primary-foreground/90 transition-colors hover:bg-white/10"
                            aria-label="Close chat"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div
                        ref={listRef}
                        className="flex-1 space-y-3 overflow-y-auto p-4"
                    >
                        {!available && (
                            <p className="text-sm text-muted-foreground">
                                You&apos;ll be able to message your trainer once
                                you&apos;re enrolled in a batch.
                            </p>
                        )}

                        {available && loading && messages.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Loading…
                            </p>
                        )}

                        {available && !loading && messages.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Say hello to your trainer!
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
                                        'max-w-[80%] rounded-2xl px-3 py-2 text-sm',
                                        message.from_me
                                            ? 'rounded-br-sm bg-primary text-primary-foreground'
                                            : 'rounded-bl-sm bg-muted text-foreground',
                                    )}
                                >
                                    {message.attachment_url && (
                                        <img
                                            src={message.attachment_url}
                                            alt="Attachment"
                                            className="mb-1 max-h-48 rounded-lg object-cover"
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

                    {available && (
                        <div className="border-t border-border/60 p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] md:pb-3">
                            {attachment && (
                                <div className="mb-2 flex items-center justify-between rounded-lg bg-muted/60 px-3 py-1.5 text-xs">
                                    <span className="truncate">
                                        {attachment.name}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => chooseAttachment(null)}
                                        className="ml-2 shrink-0 text-muted-foreground hover:text-foreground"
                                    >
                                        Remove
                                    </button>
                                </div>
                            )}
                            {attachmentError && (
                                <p className="mb-2 text-xs text-destructive">
                                    {attachmentError}
                                </p>
                            )}
                            {sendError && (
                                <p className="mb-2 text-xs text-destructive">
                                    {sendError}
                                </p>
                            )}
                            <form
                                onSubmit={send}
                                className="flex items-end gap-2"
                            >
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
                                        chooseAttachment(
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                <Textarea
                                    value={body}
                                    onChange={(event) =>
                                        setBody(event.target.value)
                                    }
                                    onKeyDown={(event) => {
                                        if (
                                            event.key === 'Enter' &&
                                            !event.shiftKey
                                        ) {
                                            event.preventDefault();
                                            send(event);
                                        }
                                    }}
                                    rows={1}
                                    placeholder="Type a message…"
                                    className="max-h-24 min-h-9 flex-1 resize-none py-2"
                                />
                                <Button
                                    type="submit"
                                    size="icon"
                                    disabled={!canSend}
                                    aria-label="Send message"
                                >
                                    <Send className="size-4" />
                                </Button>
                            </form>
                        </div>
                    )}
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => {
                        setOpen(true);
                        setLoading(true);
                    }}
                    className="relative flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105"
                    aria-label="Message your trainer"
                >
                    <MessageCircle className="size-6" />
                    {unreadMessagesCount > 0 && (
                        <span className="absolute -top-1 -right-1 flex size-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-white">
                            {unreadMessagesCount > 9
                                ? '9+'
                                : unreadMessagesCount}
                        </span>
                    )}
                </button>
            )}
        </div>
    );
}
