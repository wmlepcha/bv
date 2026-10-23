import { Shuffle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { AnimalAvatar, animalLabel } from '@shared/components/animal-avatar';

export function AvatarPicker({
    animals,
    initial,
    hasPhoto,
}: {
    animals: string[];
    initial: string;
    hasPhoto: boolean;
}) {
    const [selected, setSelected] = useState(initial);

    return (
        <div className="grid gap-3">
            <div className="flex items-center justify-between gap-3">
                <Label>Cartoon avatar</Label>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="rounded-full"
                    onClick={() =>
                        setSelected(
                            animals[Math.floor(Math.random() * animals.length)],
                        )
                    }
                >
                    <Shuffle className="size-4" />
                    Shuffle
                </Button>
            </div>

            <input type="hidden" name="avatar_animal" value={selected} />

            <div
                role="radiogroup"
                aria-label="Cartoon avatar"
                className="grid grid-cols-5 gap-3"
            >
                {animals.map((animal) => {
                    const active = animal === selected;

                    return (
                        <button
                            key={animal}
                            type="button"
                            role="radio"
                            aria-checked={active}
                            aria-label={animalLabel(animal)}
                            title={animalLabel(animal)}
                            onClick={() => setSelected(animal)}
                            className={cn(
                                'aspect-square overflow-hidden rounded-full ring-2 ring-offset-2 ring-offset-background transition-all active:scale-90',
                                active
                                    ? 'ring-primary'
                                    : 'ring-transparent hover:ring-border',
                            )}
                        >
                            <AnimalAvatar animal={animal} />
                        </button>
                    );
                })}
            </div>

            {hasPhoto && (
                <p className="text-xs text-muted-foreground">
                    Your uploaded photo is shown instead. Remove it to use the
                    cartoon avatar.
                </p>
            )}
        </div>
    );
}
