import { Form, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { TrainerPhotoField } from './trainer-photo-field';

type Props = {
    trainer: {
        id: number;
        name: string;
        email: string;
        photo_url: string | null;
    };
};

export default function EditTrainer({ trainer }: Props) {
    return (
        <>
            <PrivateSeoHead title={`Edit ${trainer.name}`} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Edit trainer
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Update account details. Leave password blank to keep the
                        current one.
                    </p>
                </div>

                <Form
                    action={`/training-manager/trainers/${trainer.id}`}
                    method="put"
                    encType="multipart/form-data"
                    className="flex flex-col gap-5 rounded-xl border border-border bg-card p-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <TrainerPhotoField
                                error={errors.avatar}
                                existingUrl={trainer.photo_url}
                                allowRemove
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={trainer.name}
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    defaultValue={trainer.email}
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    New password (optional)
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save changes
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/training-manager/trainers">
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditTrainer.layout = {
    breadcrumbs: [
        {
            title: 'Trainers',
            href: '/training-manager/trainers',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
