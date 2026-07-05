import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import InputError from '@shared/components/input-error';
import PasskeyVerify from '@app/components/passkey-verify';
import PasswordInput from '@shared/components/password-input';
import TextLink from '@shared/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AuthRecruiterInquiryModal } from '@auth/components/auth-recruiter-inquiry-modal';
import { login } from '@/routes';
import { create as studentRegister } from '@/routes/student/register';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    portal: 'student' | 'staff';
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ portal, status, canResetPassword }: Props) {
    const isStudentPortal = portal === 'student';
    const [recruiterInquiryOpen, setRecruiterInquiryOpen] = useState(false);

    return (
        <>
            <PrivateSeoHead title="Log in" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="portal" value={portal} />

                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        {isStudentPortal ? (
                            <div className="text-center text-sm text-muted-foreground">
                                Don&apos;t have an account?{' '}
                                <TextLink
                                    href={studentRegister()}
                                    tabIndex={5}
                                >
                                    Sign up
                                </TextLink>
                            </div>
                        ) : (
                            <div className="rounded-lg border border-border bg-muted/40 p-4 text-center text-sm text-muted-foreground">
                                <p>
                                    Recruiter and employee accounts are created
                                    by Bilingual Vitae after you apply.
                                </p>
                                <button
                                    type="button"
                                    onClick={() => setRecruiterInquiryOpen(true)}
                                    className="mt-2 text-sm font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    Submit a recruiter inquiry
                                </button>
                            </div>
                        )}

                        <div className="text-center text-sm">
                            <Link
                                href={login()}
                                className="text-muted-foreground underline-offset-4 hover:underline"
                            >
                                Choose a different account type
                            </Link>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            {!isStudentPortal && (
                <AuthRecruiterInquiryModal
                    open={recruiterInquiryOpen}
                    onOpenChange={setRecruiterInquiryOpen}
                />
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
