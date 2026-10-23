import { Form, Link, router, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/profile';
import { store as requestPlacement } from '@/routes/student/placement-request';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';
import { AvatarPicker } from '@app/components/avatar-picker';
import DeleteUser from '@app/components/delete-user';
import { ActionBar, FormGroup, InputRow } from '@app/components/native/form';
import { TrainerPhotoField } from '@app/pages/training-manager/trainers/trainer-photo-field';
import { PrivateSeoHead } from '@shared/components/seo-head';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
    photo_url,
    avatar_animal,
    avatar_animals,
    placement,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    photo_url: string | null;
    avatar_animal: string;
    avatar_animals: string[];
    placement: {
        purpose_label: string;
        wants_placement: boolean;
        requested: boolean;
    } | null;
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <PrivateSeoHead title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <Form
                {...ProfileController.update.form()}
                encType="multipart/form-data"
                options={{
                    preserveScroll: true,
                }}
                className="space-y-4"
            >
                {({ processing, errors }) => (
                    <>
                        <FormGroup title="Photo">
                            <div className="space-y-6 p-4">
                                <TrainerPhotoField
                                    error={errors.avatar}
                                    existingUrl={photo_url}
                                    allowRemove
                                    description="JPG or PNG, up to 2MB. This photo appears on your trainer profile."
                                />
                                <AvatarPicker
                                    animals={avatar_animals}
                                    initial={avatar_animal}
                                    hasPhoto={Boolean(photo_url)}
                                />
                            </div>
                        </FormGroup>

                        <FormGroup
                            title="Account"
                            footer={
                                mustVerifyEmail &&
                                auth.user?.email_verified_at === null ? (
                                    <>
                                        Your email address is unverified.{' '}
                                        <Link
                                            href={send()}
                                            as="button"
                                            className="font-medium text-foreground underline underline-offset-4"
                                        >
                                            Re-send the verification email.
                                        </Link>
                                        {status ===
                                            'verification-link-sent' && (
                                            <span className="mt-1 block font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </span>
                                        )}
                                    </>
                                ) : undefined
                            }
                        >
                            <InputRow
                                label="Name"
                                name="name"
                                defaultValue={auth.user?.name}
                                required
                                autoComplete="name"
                                enterKeyHint="next"
                                placeholder="Full name"
                                error={errors.name}
                            />
                            <InputRow
                                label="Email"
                                name="email"
                                type="email"
                                inputMode="email"
                                defaultValue={auth.user?.email}
                                required
                                autoComplete="username"
                                enterKeyHint="done"
                                placeholder="Email address"
                                error={errors.email}
                            />
                        </FormGroup>

                        <ActionBar>
                            <Button
                                disabled={processing}
                                className="h-12 w-full rounded-full text-base"
                                data-test="update-profile-button"
                            >
                                Save changes
                            </Button>
                        </ActionBar>
                    </>
                )}
            </Form>

            {placement && (
                <FormGroup
                    title="Registered for"
                    footer={
                        placement.wants_placement
                            ? 'Recruiters can see your profile.'
                            : 'Want to be considered by recruiters? Ask BV to switch you to Training + Placement.'
                    }
                    className="mt-4"
                >
                    <div className="flex items-center justify-between gap-3 p-4">
                        <span className="text-sm font-medium">
                            {placement.purpose_label}
                        </span>
                        {!placement.wants_placement && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                                disabled={placement.requested}
                                onClick={() =>
                                    router.post(
                                        requestPlacement.url(),
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                {placement.requested
                                    ? 'Request sent'
                                    : 'Request placement'}
                            </Button>
                        )}
                    </div>
                </FormGroup>
            )}

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
