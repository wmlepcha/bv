interface ServiceSpecRowProps {
    label: string;
    value: string;
}

export function ServiceSpecRow({ label, value }: ServiceSpecRowProps) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:gap-3">
            <dt className="shrink-0 text-sm font-medium text-muted-foreground sm:w-28">{label}</dt>
            <dd className="text-sm leading-relaxed">{value}</dd>
        </div>
    );
}
