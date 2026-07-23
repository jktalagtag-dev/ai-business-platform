/** First letter of up to the first two words of a name, upper-cased —
 * used for avatar fallbacks wherever a user's name is shown (Topbar's
 * account menu, Sidebar's profile footer). */
export function initials(name: string): string {
  return name
    .split(' ')
    .map((part) => part[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();
}
