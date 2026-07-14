import { useRef } from 'react';
import { Loader2, Upload } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useUploadEmployeeAvatar } from '@/modules/employee/hooks/useEmployees';
import type { EmployeeResource } from '@/modules/employee/types';

export function EmployeeAvatarUploader({
  employee,
  canEdit,
}: {
  employee: EmployeeResource;
  canEdit: boolean;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const upload = useUploadEmployeeAvatar(employee.id);

  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;

    upload.mutate(file, {
      onSuccess: () => toast.success('Avatar updated.'),
      onError: (error) =>
        toast.error(isApiError(error) ? error.message : 'Unable to upload avatar (JPG/PNG/WebP, max 2MB).'),
    });
  }

  return (
    <div className="flex items-center gap-4">
      <Avatar className="h-16 w-16">
        <AvatarImage src={employee.attributes.avatar_url ?? undefined} />
        <AvatarFallback className="text-lg">{employee.attributes.first_name.charAt(0)}</AvatarFallback>
      </Avatar>
      {canEdit && (
        <>
          <input
            ref={inputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            className="hidden"
            onChange={handleFileChange}
          />
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={upload.isPending}
            onClick={() => inputRef.current?.click()}
          >
            {upload.isPending ? <Loader2 className="animate-spin" /> : <Upload className="h-4 w-4" />}
            Change photo
          </Button>
        </>
      )}
    </div>
  );
}
