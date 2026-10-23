import { Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@app/components/passkey-verify';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import { PrivateSeoHead } from '@shared/components/seo-head';
import TextLink from '@shared/components/text-link';
import { BRAND_NAME_TM } from '@shared/lib/brand';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function TrainingManagerLogin({
    status,
    canResetPassword,
}: Props) {
    return (
        <>
            <PrivateSeoHead title="Training Manager login" />

            <PasskeyVerify />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600 lg:text-left">
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input
                            type="hidden"
                            name="portal"
                            value="training_manager"
                        />

                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="Enter your email"
                                    className="bg-muted/50"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Enter your password"
                                    className="bg-muted/50"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center justify-between gap-3">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                    />
                                    <Label htmlFor="remember">
                                        Remember me
                                    </Label>
                                </div>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-sm"
                                        tabIndex={5}
                                    >
                                        Forgot password?
                                    </TextLink>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="mt-1 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Sign in
                            </Button>
                        </div>

                        <div className="rounded-lg border border-border bg-muted/40 p-4 text-center text-sm text-muted-foreground lg:text-left">
                            <p>
                                Training manager accounts are created by{' '}
                                {BRAND_NAME_TM}. Contact your administrator if
                                you need access.
                            </p>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

TrainingManagerLogin.layout = {
    panel: 'training_manager',
    title: 'Welcome back',
    description: 'Enter your email and password to access your account',
};
