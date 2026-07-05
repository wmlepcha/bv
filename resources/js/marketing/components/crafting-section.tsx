import { SequentialColumnSection } from '@marketing/components/sequential-column-section';
import { FloatingReviewCard } from '@marketing/components/floating-review-card';
import { MagneticButton } from '@marketing/components/magnetic-button';
import type { HomePageCraftingSection } from '@marketing/types/home-page';

interface CraftingSectionProps {
    crafting: HomePageCraftingSection;
    onLearnMore?: () => void;
}

export function CraftingSection({ crafting, onLearnMore }: CraftingSectionProps) {
    const testimonial = crafting.testimonial;

    return (
        <SequentialColumnSection
            className="overflow-hidden px-4 py-16 sm:px-6 sm:py-24 md:py-32"
            leading={{
                className: 'order-2 lg:order-1',
                content: (
                    <>
                        <p className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-medium tracking-wide text-foreground/80">
                            <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                            {crafting.badge}
                        </p>

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
                                    className="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3"
                                >
                                    <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-cool-light" />
                                    <span className="text-sm font-medium text-foreground/85">{pillar.label}</span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-10">
                            <MagneticButton type="button" onClick={onLearnMore}>
                                Learn more
                            </MagneticButton>
                        </div>
                    </>
                ),
            }}
            trailing={{
                className: 'relative order-1 lg:order-2',
                content: (
                    <div className="flex flex-col gap-4">
                        <div className="relative overflow-hidden rounded-3xl border border-white/10 shadow-2xl shadow-black/20">
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
