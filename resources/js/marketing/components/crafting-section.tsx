import { Link } from '@inertiajs/react';
import { SequentialColumnSection } from '@marketing/components/sequential-column-section';
import { FloatingReviewCard } from '@marketing/components/floating-review-card';
import { MagneticButton } from '@marketing/components/magnetic-button';
import type { HomePageCraftingSection } from '@marketing/types/home-page';
import { cn } from '@/lib/utils';

interface CraftingSectionProps {
    crafting: HomePageCraftingSection;
    onRegisterNow?: () => void;
}

export function CraftingSection({ crafting, onRegisterNow }: CraftingSectionProps) {
    const testimonial = crafting.testimonial;

    return (
        <SequentialColumnSection
            className="overflow-hidden px-4 py-16 sm:px-6 sm:py-24 md:py-32"
            leading={{
                className: 'order-2 lg:order-1',
                content: (
                    <>
                        <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                            {crafting.heading}
                        </h2>

                        <p className="mt-6 text-base leading-relaxed text-muted-foreground md:text-lg">
                            {crafting.body}
                        </p>

                        <div className="mt-8 grid gap-3 sm:grid-cols-2">
                            {crafting.pillars.map((pillar) => (
                                <div
                                    key={pillar.label}
                                    className="flex items-center gap-3 rounded-xl border marketing-panel px-4 py-3"
                                >
                                    <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-cool-light" />
                                    <span className="text-sm font-medium text-foreground/85">{pillar.label}</span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-10 flex flex-wrap items-center gap-3">
                            <MagneticButton type="button" onClick={onRegisterNow}>
                                Register Now
                            </MagneticButton>
                            <Link
                                href="/about"
                                className={cn(
                                    'inline-flex min-h-11 min-w-[9.5rem] items-center justify-center rounded-full border border-foreground/20 bg-transparent px-8 py-3 text-sm font-medium text-foreground/90 transition-colors hover:border-primary hover:text-primary',
                                )}
                            >
                                Learn More
                            </Link>
                        </div>
                    </>
                ),
            }}
            trailing={{
                className: 'relative order-1 lg:order-2',
                content: (
                    <div className="flex flex-col gap-4">
                        <div className="relative overflow-hidden rounded-3xl border marketing-hairline shadow-2xl shadow-black/20">
                            <img
                                src={crafting.imageUrl}
                                alt={crafting.imageAlt}
                                className="aspect-[9/16] w-full object-cover object-center sm:aspect-[4/5]"
                                loading="lazy"
                            />
                            <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />
                            {testimonial && (
                                <FloatingReviewCard
                                    disableAnimation
                                    quote={testimonial.quote}
                                    author={testimonial.author}
                                    role={testimonial.role}
                                    className="hidden sm:block"
                                />
                            )}
                        </div>
                        {testimonial && (
                            <FloatingReviewCard
                                disableAnimation
                                inline
                                quote={testimonial.quote}
                                author={testimonial.author}
                                role={testimonial.role}
                                className="sm:hidden"
                            />
                        )}
                    </div>
                ),
            }}
        />
    );
}
