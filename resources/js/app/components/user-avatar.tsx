import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User } from '@/types';
import { AnimalAvatar } from '@shared/components/animal-avatar';

/** Uploaded photo when there is one, otherwise the user's cartoon animal. */
export function UserAvatar({
    user,
    className,
}: {
    user: User;
    className?: string;
}) {
    const photo = user.avatar_url ?? user.avatar;
    const animal = String(user.avatar_animal ?? 'fox');

    return (
        <Avatar className={className}>
            {photo && <AvatarImage src={photo} alt={user.name} />}
            <AvatarFallback className="bg-transparent">
                <AnimalAvatar animal={animal} />
            </AvatarFallback>
        </Avatar>
    );
}
