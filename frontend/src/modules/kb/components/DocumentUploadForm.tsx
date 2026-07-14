import { useRef, useState, type FormEvent } from 'react';
import { Loader2, Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useUploadDocument } from '@/modules/kb/hooks/useDocuments';

// Matches the backend's default KB_MAX_UPLOAD_SIZE_KB (20480 KB = 20 MB) —
// a soft client-side check only; the server's actual configured limit is
// the source of truth and will reject anything it doesn't accept regardless.
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

export function DocumentUploadForm() {
  const upload = useUploadDocument();
  const [title, setTitle] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (!file) {
      toast.error('Choose a PDF file first.');
      return;
    }
    if (file.size > MAX_UPLOAD_BYTES) {
      toast.error('File is too large (max 20 MB).');
      return;
    }

    upload.mutate(
      { file, title: title.trim() || undefined },
      {
        onSuccess: () => {
          toast.success('Document uploaded — processing in the background.');
          setTitle('');
          setFile(null);
          if (inputRef.current) inputRef.current.value = '';
        },
        onError: (error) =>
          toast.error(isApiError(error) ? error.message : 'Unable to upload document.'),
      }
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
      <div className="min-w-[200px] flex-1 space-y-1">
        <Label htmlFor="kb-title">Title (optional)</Label>
        <Input
          id="kb-title"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Defaults to the file name"
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="kb-file">PDF file</Label>
        <input
          id="kb-file"
          ref={inputRef}
          type="file"
          accept="application/pdf,.pdf"
          onChange={(e) => setFile(e.target.files?.[0] ?? null)}
          className="block text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium"
        />
      </div>
      <Button type="submit" disabled={upload.isPending}>
        {upload.isPending ? <Loader2 className="animate-spin" /> : <Upload className="h-4 w-4" />}
        Upload
      </Button>
    </form>
  );
}
