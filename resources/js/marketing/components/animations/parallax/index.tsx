import { forwardRef, useRef } from 'react';
import type { AnimationComponentProps } from '@marketing/animations/types/animation-props';
import { useScrollAnimation } from '@marketing/hooks/use-gsap-context';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { mergeRefs } from '@marketing/utils/animation-helpers';
import { presets } from '@marketing/animations/gsap/timelines/presets';
import { cn } from '@/lib/utils';

interface ParallaxImageProps extends AnimationComponentProps {
    src: string;
    alt: string;
    speed?: number;
    imgClassName?: string;
}

export const ParallaxImage = forwardRef<HTMLDivElement, ParallaxImageProps>(
    (
        {
            src,
            alt,
            speed = 0.5,
            className,
            imgClassName,
            disabled,
            ...anim
        },
        ref,
    ) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const imageRef = useRef<HTMLImageElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (imageRef.current) {
                    presets.parallax(imageRef.current, speed, anim);
                }
            },
            [speed, anim, reduced],
            disabled || reduced,
        );

        return (
            <div
                ref={mergeRefs(ref, containerRef)}
                className={cn('overflow-hidden', className)}
            >
                <img
                    ref={imageRef}
                    src={src}
                    alt={alt}
                    className={cn('h-full w-full object-cover', imgClassName)}
                    loading="lazy"
                />
            </div>
        );
    },
);
ParallaxImage.displayName = 'ParallaxImage';

interface ParallaxBackgroundProps extends AnimationComponentProps {
    speed?: number;
    style?: React.CSSProperties;
}

export const ParallaxBackground = forwardRef<
    HTMLDivElement,
    ParallaxBackgroundProps
>(({ children, speed = 0.3, className, style, disabled, ...anim }, ref) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const bgRef = useRef<HTMLDivElement>(null);
    const reduced = useReducedMotion();

    useScrollAnimation(
        containerRef,
        () => {
            if (bgRef.current) {
                presets.parallax(bgRef.current, speed, anim);
            }
        },
        [speed, anim, reduced],
        disabled || reduced,
    );

    return (
        <div
            ref={mergeRefs(ref, containerRef)}
            className={cn('relative overflow-hidden', className)}
        >
            <div
                ref={bgRef}
                className="absolute inset-0 -z-10 scale-110"
                style={style}
                aria-hidden
            />
            {children}
        </div>
    );
});
ParallaxBackground.displayName = 'ParallaxBackground';

interface ParallaxTextProps extends AnimationComponentProps {
    speed?: number;
}

export const ParallaxText = forwardRef<HTMLDivElement, ParallaxTextProps>(
    ({ children, speed = 0.2, className, disabled, ...anim }, ref) => {
        const containerRef = useRef<HTMLDivElement>(null);
        const reduced = useReducedMotion();

        useScrollAnimation(
            containerRef,
            () => {
                if (containerRef.current) {
                    presets.parallax(containerRef.current, speed, anim);
                }
            },
            [speed, anim, reduced],
            disabled || reduced,
        );

        return (
            <div ref={mergeRefs(ref, containerRef)} className={className}>
                {children}
            </div>
        );
    },
);
ParallaxText.displayName = 'ParallaxText';

interface Layer {
    speed: number;
    content: React.ReactNode;
    className?: string;
}

interface MultiLayerParallaxProps extends AnimationComponentProps {
    layers: Layer[];
}

export const MultiLayerParallax = forwardRef<
    HTMLDivElement,
    MultiLayerParallaxProps
>(({ layers, className, disabled, ...anim }, ref) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const layerRefs = useRef<(HTMLDivElement | null)[]>([]);
    const reduced = useReducedMotion();

    useScrollAnimation(
        containerRef,
        () => {
            layerRefs.current.forEach((layer, index) => {
                if (layer) {
                    presets.parallax(layer, layers[index]?.speed ?? 0.5, anim);
                }
            });
        },
        [layers, anim, reduced],
        disabled || reduced,
    );

    return (
        <div
            ref={mergeRefs(ref, containerRef)}
            className={cn('relative', className)}
        >
            {layers.map((layer, index) => (
                <div
                    key={index}
                    ref={(el) => {
                        layerRefs.current[index] = el;
                    }}
                    className={cn('absolute inset-0', layer.className)}
                >
                    {layer.content}
                </div>
            ))}
        </div>
    );
});
MultiLayerParallax.displayName = 'MultiLayerParallax';
