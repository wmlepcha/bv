import { Form, Link } from '@inertiajs/react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { TrainerPhotoField } from './trainer-photo-field';

export default function CreateTrainer() {
    return (
        <>
            <PrivateSeoHead title="Add trainer" />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Add trainer
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Create a trainer account. They sign in through the
                        trainer/teacher portal.
                    </p>
                </div>

                <Form
                    action="/training-manager/trainers"
                    method="post"
                    encType="multipart/form-data"
                    className="flex flex-col gap-5 rounded-xl border border-border bg-card p-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <TrainerPhotoField error={errors.avatar} />
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
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
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Create trainer
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

CreateTrainer.layout = {
    breadcrumbs: [
        {
            title: 'Trainers',
            href: '/training-manager/trainers',
        },
        {
            title: 'Add',
            href: '/training-manager/trainers/create',
        },
    ],
};
