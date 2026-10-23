import { Smartphone } from 'lucide-react';

/** Desktop replacement for the scan button: attendance is marked from a phone camera. */
export default function PhoneHandoffCard() {
    return (
        <section className="flex flex-col gap-2 rounded-3xl border border-border bg-card/60 p-5 shadow-sm">
            <span className="flex size-10 items-center justify-center rounded-2xl bg-brand-cool/15 text-brand-cool">
                <Smartphone className="size-5" />
            </span>
            <h2 className="text-base font-semibold">
                Mark attendance from your phone
            </h2>
            <p className="text-sm text-muted-foreground">
                Log in from your mobile device, open{' '}
                <span className="font-medium text-foreground">Attendance</span>{' '}
                and scan the QR your trainer shows in class.
            </p>
        </section>
    );
}
