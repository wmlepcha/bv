import { CheckIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { RegistrationPlan } from '@shared/types/registration-plan';

function formatPlanPrice(priceRupees: number): string {
    return priceRupees.toLocaleString('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    });
}

function durationLabel(months: number): string {
    if (months === 12) {
        return '/ year';
    }

    if (months === 24) {
        return '/ 2 years';
    }

    return `/ ${months} months`;
}

type Props = {
    plans: RegistrationPlan[];
    agreedToTerms: boolean;
    isPaying: boolean;
    activePlanId: number | null;
    cashfreeAppId: string | null;
    onSelectPlan: (planId: number) => void;
    mode?: 'unlock' | 'renew';
    surface?: 'dark' | 'light';
};

export default function RegistrationPlanPicker({
    plans,
    agreedToTerms,
    isPaying,
    activePlanId,
    cashfreeAppId,
    onSelectPlan,
    surface = 'dark',
}: Props) {
    const isDisabled = !agreedToTerms || isPaying || !cashfreeAppId;

    return (
        <div
            className={cn(
                'flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-none pb-1',
                'md:mx-0 md:grid md:grid-cols-2 md:gap-4 md:overflow-visible md:px-0 md:pb-0',
            )}
        >
            {plans.map((plan) => {
                const isRecommended = plan.is_recommended;
                const isLoadingThisPlan = isPaying && activePlanId === plan.id;

                return (
                    <article
                        key={plan.id}
                        className={cn(
                            'relative flex shrink-0 snap-start flex-col overflow-hidden rounded-2xl border bg-white p-3.5 text-[#031835]',
                            'w-[82%] max-w-[300px] sm:w-[78%] sm:max-w-[320px]',
                            'md:w-auto md:max-w-none md:snap-align-none md:p-5',
                            isRecommended
                                ? 'border-primary/40 shadow-xl shadow-primary/15 ring-1 ring-primary/20'
                                : 'border-border/60 shadow-lg shadow-black/5',
                            surface === 'dark' &&
                                !isRecommended &&
                                'shadow-black/15',
                        )}
                    >
                        {isRecommended && (
                            <div
                                className="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary via-brand-warm-light to-brand-cool-light"
                                aria-hidden
                            />
                        )}

                        {plan.badge_text && (
                            <span className="absolute right-3 top-3 rounded-full bg-gradient-to-r from-brand-warm-light to-primary px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-white shadow-sm shadow-primary/30 md:text-[10px]">
                                {plan.badge_text}
                            </span>
                        )}

                        <div className="pr-14 md:pr-16">
                            <h3
                                className={cn(
                                    'text-base font-semibold tracking-tight md:text-xl',
                                    isRecommended
                                        ? 'text-primary'
                                        : 'text-secondary',
                                )}
                            >
                                {plan.name}
                            </h3>
                            {plan.description && (
                                <p className="mt-0.5 hidden text-sm text-[#6b7280] sm:block md:text-[15px]">
                                    {plan.description}
                                </p>
                            )}
                        </div>

                        <div className="mt-2.5 flex items-end gap-1.5 md:mt-3 md:gap-2">
                            <span className="text-2xl font-bold leading-none tracking-tight text-[#031835] md:text-[1.75rem]">
                                {formatPlanPrice(plan.price_rupees)}
                            </span>
                            <span className="pb-0.5 text-xs font-medium text-[#6b7280] md:text-sm">
                                {durationLabel(plan.duration_months)}
                            </span>
                        </div>
                        <p className="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-[#6b7280]/80 md:mt-1 md:text-[11px]">
                            incl. GST
                        </p>

                        <ul className="mt-3 space-y-1.5 border-t border-border/70 pt-3 md:mt-4 md:space-y-2 md:pt-4">
                            {plan.features.map((feature) => (
                                <li
                                    key={feature}
                                    className="flex items-start gap-2 text-xs leading-snug text-[#031835]/85 md:text-sm"
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full md:size-5',
                                            isRecommended
                                                ? 'bg-primary/12 text-primary'
                                                : 'bg-secondary/10 text-secondary',
                                        )}
                                        aria-hidden
                                    >
                                        <CheckIcon className="size-2.5 stroke-[2.5] md:size-3" />
                                    </span>
                                    <span>{feature}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-3 border-t border-border/50 pt-3 md:mt-4 md:pt-4">
                            <Button
                                type="button"
                                size="lg"
                                className={cn(
                                    'h-9 w-full rounded-full text-xs font-semibold shadow-sm md:h-10 md:text-sm',
                                    isRecommended
                                        ? 'bg-[#031835] text-white shadow-md shadow-[#031835]/20 hover:bg-[#031835]/90'
                                        : 'border-2 border-[#031835] bg-white text-[#031835] hover:bg-[#031835] hover:text-white',
                                    isDisabled &&
                                        '!border-[#e5e7eb] !bg-[#e5e7eb] !text-[#6b7280] shadow-none hover:!bg-[#e5e7eb] hover:!text-[#6b7280]',
                                )}
                                variant="default"
                                disabled={isDisabled}
                                onClick={() => onSelectPlan(plan.id)}
                            >
                                {isLoadingThisPlan
                                    ? 'Opening payment...'
                                    : 'Select this plan'}
                            </Button>
                        </div>
                    </article>
                );
            })}
        </div>
    );
}
