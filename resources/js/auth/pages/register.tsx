import { Form, Link } from '@inertiajs/react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import TextLink from '@shared/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/student/register';

type SpecialIdOption = {
    value: string;
    label: string;
};

type Props = {
    passwordRules: string;
    portal: 'student';
    states: string[];
    specialIds: SpecialIdOption[];
};

export default function Register({
    passwordRules,
    states,
    specialIds,
}: Props) {
    return (
        <>
            <PrivateSeoHead title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">First name</Label>
                                    <Input
                                        id="first_name"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="given-name"
                                        name="first_name"
                                        placeholder="First name"
                                    />
                                    <InputError message={errors.first_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">Last name</Label>
                                    <Input
                                        id="last_name"
                                        type="text"
                                        required
                                        tabIndex={2}
                                        autoComplete="family-name"
                                        name="last_name"
                                        placeholder="Last name"
                                    />
                                    <InputError message={errors.last_name} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="state">State</Label>
                                <select
                                    id="state"
                                    name="state"
                                    required
                                    tabIndex={4}
                                    defaultValue=""
                                    className="border-input bg-transparent dark:bg-input/30 flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                >
                                    <option value="" disabled>
                                        Select your state
                                    </option>
                                    {states.map((state) => (
                                        <option key={state} value={state}>
                                            {state}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.state} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="special_id">Special ID</Label>
                                <select
                                    id="special_id"
                                    name="special_id"
                                    required
                                    tabIndex={5}
                                    defaultValue=""
                                    className="border-input bg-transparent dark:bg-input/30 flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                >
                                    <option value="" disabled>
                                        Select special ID type
                                    </option>
                                    {specialIds.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.special_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={6}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={7}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={8}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login({ query: { portal: 'student' } })}
                                tabIndex={9}
                            >
                                Log in
                            </TextLink>
                        </div>

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
        </>
    );
}

Register.layout = {
    title: 'Create your candidate account',
    description:
        'Sign up as a candidate. You will need to pay the registration fee to unlock your dashboard.',
};
