import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { FloatingReviewCard } from '@marketing/components/floating-review-card';
import { MagneticButton } from '@marketing/components/magnetic-button';
import { SequentialColumnSection } from '@marketing/components/sequential-column-section';
import type { HomePageRecruitersSection } from '@marketing/types/home-page';

interface RecruitersSectionProps {
    recruiters: HomePageRecruitersSection;
    onRegisterNow?: () => void;
}

export function RecruitersSection({ recruiters, onRegisterNow }: RecruitersSectionProps) {
    const testimonial = recruiters.testimonial;

    return (
        <SequentialColumnSection
            className="overflow-hidden border-y marketing-hairline-soft bg-muted/20 px-4 py-16 sm:px-6 sm:py-24 md:py-32"
            leading={{
                className: 'relative order-2 lg:order-1',
                content: (
                    <div className="flex flex-col gap-4">
                        <div className="relative overflow-hidden rounded-3xl border marketing-hairline shadow-2xl shadow-black/20">
                            <img
                                src={recruiters.imageUrl}
                                alt={recruiters.imageAlt}
                                className="aspect-[4/3] w-full object-cover object-center"
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
            trailing={{
                className: 'order-1 lg:order-2',
                content: (
                    <>
                        <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                            {recruiters.heading}
                        </h2>

                        <p className="mt-6 text-base leading-relaxed text-muted-foreground md:text-lg">
                            {recruiters.body}
                        </p>

                        <div className="mt-8 space-y-3">
                            {recruiters.benefits.map((benefit) => (
                                <div
                                    key={benefit.label}
                                    className="flex items-center gap-3 rounded-xl border marketing-panel px-4 py-3"
                                >
                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/15 text-xs font-bold text-primary">
                                        ✓
                                    </span>
                                    <span className="text-sm font-medium text-foreground/85">{benefit.label}</span>
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
        />
    );
}
