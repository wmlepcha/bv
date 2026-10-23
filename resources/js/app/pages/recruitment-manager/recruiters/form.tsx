import { Form, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/recruitment-manager/recruiters';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Recruiter = {
    id: number;
    name: string;
    email: string;
    whatsapp_number: string | null;
};

export default function RecruiterForm({
    recruiter,
}: {
    recruiter: Recruiter | null;
}) {
    const title = recruiter ? 'Edit recruiter' : 'Add recruiter';
    const action = recruiter ? update.form(recruiter.id) : store.form();

    return (
        <>
            <PrivateSeoHead title={title} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-2 sm:px-4">
                <Link
                    href={index()}
                    className="inline-flex h-9 w-fit items-center gap-2 rounded-full bg-muted/60 pr-4 pl-3 text-sm font-medium transition-colors active:bg-muted md:hover:bg-muted"
                >
                    <ArrowLeft className="size-4" />
                    All recruiters
                </Link>

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {recruiter
                            ? 'Leave the password empty to keep it.'
                            : 'The recruiter gets their login details by email.'}
                    </p>
                </div>

                <Form
                    {...action}
                    className="flex flex-col gap-5 rounded-3xl border border-border bg-card p-5 shadow-sm"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    className="h-11 rounded-xl"
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    defaultValue={recruiter?.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    className="h-11 rounded-xl"
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    defaultValue={recruiter?.email}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="whatsapp_number">
                                    WhatsApp number
                                </Label>
                                <Input
                                    className="h-11 rounded-xl"
                                    id="whatsapp_number"
                                    type="tel"
                                    name="whatsapp_number"
                                    placeholder="+91…"
                                    defaultValue={
                                        recruiter?.whatsapp_number ?? ''
                                    }
                                />
                                <InputError message={errors.whatsapp_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required={!recruiter}
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:gap-3">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="h-11 rounded-full"
                                >
                                    {processing && <Spinner />}
                                    {recruiter
                                        ? 'Save changes'
                                        : 'Create recruiter'}
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="h-11 rounded-full"
                                >
                                    <Link href={index()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
