import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/security';
import type { Props as ManagePasskeysProps } from '@app/components/manage-passkeys';
import ManagePasskeys from '@app/components/manage-passkeys';
import type { Props as ManageTwoFactorProps } from '@app/components/manage-two-factor';
import ManageTwoFactor from '@app/components/manage-two-factor';
import { ActionBar, FormGroup, PasswordRow } from '@app/components/native/form';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <PrivateSeoHead title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <div className="space-y-6">
                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <FormGroup
                                title="Update password"
                                footer="Use a long, random password to keep your account secure."
                            >
                                <PasswordRow
                                    label="Current"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    autoComplete="current-password"
                                    placeholder="Required"
                                    error={errors.current_password}
                                />
                                <PasswordRow
                                    label="New"
                                    ref={passwordInput}
                                    name="password"
                                    autoComplete="new-password"
                                    placeholder="Required"
                                    passwordrules={props.passwordRules}
                                    error={errors.password}
                                />
                                <PasswordRow
                                    label="Confirm"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    placeholder="Required"
                                    passwordrules={props.passwordRules}
                                    error={errors.password_confirmation}
                                />
                            </FormGroup>

                            <ActionBar>
                                <Button
                                    disabled={processing}
                                    className="h-12 w-full rounded-full text-base"
                                    data-test="update-password-button"
                                >
                                    Save password
                                </Button>
                            </ActionBar>
                        </>
                    )}
                </Form>
            </div>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />

            <ManagePasskeys
                canManagePasskeys={props.canManagePasskeys}
                passkeys={props.passkeys}
            />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
