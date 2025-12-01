/**
 * FilePicker component for file uploads with drag-and-drop support
 * Uploads files immediately to temporary endpoint and returns file ID/path
 */
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';
import { FileIcon, ImageIcon, Upload, X } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';

export default function FilePicker({
  value,
  onChange,
  accept = 'image/*',
  label,
  error,
  directory = 'temp',
  className,
  ...props
}) {
  const [uploading, setUploading] = useState(false);
  const [preview, setPreview] = useState(value || null);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);
  const page = usePage();

  const handleFileSelect = useCallback(
    async (file) => {
      if (!file) {
        return;
      }

      const maxFileSizeKB = page.props.media?.max_file_size_kb || 10240;
      const maxFileSizeBytes = maxFileSizeKB * 1024;
      const maxFileSizeMB = page.props.media?.max_file_size_mb || maxFileSizeKB / 1024;
      const isPhpLimit = page.props.media?.is_php_limit || false;

      if (file.size > maxFileSizeBytes) {
        let errorMessage = `File size exceeds the maximum allowed size of ${maxFileSizeMB}MB.`;

        if (isPhpLimit) {
          const phpUploadMax = page.props.media?.php_upload_max_filesize_kb || 0;
          const phpPostMax = page.props.media?.php_post_max_size_kb || 0;
          const phpLimitMB = Math.min(phpUploadMax, phpPostMax) / 1024;
          errorMessage = `File size exceeds the server limit of ${phpLimitMB.toFixed(1)}MB. Please contact your administrator to increase PHP upload limits.`;
        }

        toast.error('File too large', {
          description: errorMessage,
        });
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        return;
      }

      setUploading(true);

      try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('directory', directory);

        const csrfToken =
          page.props.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';

        const response = await fetch(route('api.media.upload-temporary'), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
          },
          body: formData,
          credentials: 'same-origin',
        });

        if (!response.ok) {
          let errorMessage = 'Upload failed. Please try again.';
          let errorTitle = 'Upload failed';

          try {
            const errorData = await response.json();
            if (errorData.message) {
              errorMessage = errorData.message;
            }
            if (errorData.error) {
              errorTitle = errorData.error;
            }
            if (errorData.errors && typeof errorData.errors === 'object') {
              const firstError = Object.values(errorData.errors)[0];
              errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
            }
          } catch {
            errorMessage = `Upload failed with status ${response.status}. Please try again.`;
          }

          toast.error(errorTitle, {
            description: errorMessage,
          });
          if (fileInputRef.current) {
            fileInputRef.current.value = '';
          }
          return;
        }

        let data;
        try {
          data = await response.json();
        } catch {
          toast.error('Upload failed', {
            description: 'Invalid response from server. Please try again.',
          });
          if (fileInputRef.current) {
            fileInputRef.current.value = '';
          }
          return;
        }

        if (!data || (!data.url && !data.path && !data.id)) {
          toast.error('Upload failed', {
            description: 'Invalid response data. Please try again.',
          });
          if (fileInputRef.current) {
            fileInputRef.current.value = '';
          }
          return;
        }

        setPreview(data.url || data.path);
        onChange?.(data.id || data.path);
        toast.success('File uploaded successfully');
      } catch (err) {
        let errorMessage = 'An unexpected error occurred. Please try again.';

        if (err instanceof TypeError && err.message.includes('fetch')) {
          errorMessage = 'Network error. Please check your internet connection and try again.';
        } else if (err instanceof Error) {
          errorMessage = err.message || errorMessage;
        } else if (typeof err === 'string') {
          errorMessage = err;
        }

        toast.error('Upload failed', {
          description: errorMessage,
        });
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      } finally {
        setUploading(false);
      }
    },
    [directory, onChange, page.props]
  );

  const handleDrop = useCallback(
    (e) => {
      e.preventDefault();
      e.stopPropagation();
      setDragActive(false);

      const file = e.dataTransfer.files?.[0];
      if (file) {
        handleFileSelect(file);
      }
    },
    [handleFileSelect]
  );

  const handleDragOver = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(true);
  }, []);

  const handleDragLeave = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
  }, []);

  const handleInputClick = useCallback(() => {
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, []);

  const handleInputChange = useCallback(
    (e) => {
      const file = e.target.files?.[0];
      if (file) {
        handleFileSelect(file);
      }
    },
    [handleFileSelect]
  );

  const handleRemove = useCallback(() => {
    setPreview(null);
    onChange?.('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, [onChange]);

  const isImage =
    preview && (preview.includes('image') || preview.match(/\.(jpg|jpeg|png|gif|webp)$/i));

  return (
    <div className={cn('space-y-2', className)}>
      {label && <Label>{label}</Label>}
      <div
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        className={cn(
          'relative border-2 border-dashed rounded-lg p-6 transition-colors',
          dragActive ? 'border-primary bg-primary/5' : 'border-muted-foreground/25',
          error && 'border-destructive',
          uploading && 'opacity-50 pointer-events-none'
        )}
        {...props}
      >
        <input
          ref={fileInputRef}
          type="file"
          accept={accept}
          onClick={handleInputClick}
          onChange={handleInputChange}
          className="hidden"
          id={`file-picker-${label || 'input'}`}
          disabled={uploading}
        />

        {uploading ? (
          <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
            <Upload className="h-8 w-8 animate-pulse" />
            <p className="text-sm">Uploading...</p>
          </div>
        ) : preview ? (
          <div className="relative">
            {isImage ? (
              <div className="relative group">
                <img src={preview} alt="Preview" className="w-full h-48 object-cover rounded-md" />
                <Button
                  type="button"
                  variant="destructive"
                  size="icon"
                  className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                  onClick={handleRemove}
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            ) : (
              <div className="flex items-center gap-3 p-4 border rounded-md bg-muted/50">
                <FileIcon className="h-8 w-8 text-muted-foreground" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium truncate">{value || 'File uploaded'}</p>
                  <p className="text-xs text-muted-foreground">Click to change</p>
                </div>
                <Button type="button" variant="ghost" size="icon" onClick={handleRemove}>
                  <X className="h-4 w-4" />
                </Button>
              </div>
            )}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center gap-4">
            <div className="flex items-center justify-center w-16 h-16 rounded-full bg-muted">
              <Upload className="h-8 w-8 text-muted-foreground" />
            </div>
            <div className="text-center">
              <p className="text-sm font-medium">
                Drag and drop a file here, or{' '}
                <label
                  htmlFor={`file-picker-${label || 'input'}`}
                  className="text-primary cursor-pointer hover:underline"
                >
                  browse
                </label>
              </p>
              <p className="text-xs text-muted-foreground mt-1">
                {accept === 'image/*' ? 'Images only' : 'Select a file'}
              </p>
            </div>
          </div>
        )}
      </div>
      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
}
