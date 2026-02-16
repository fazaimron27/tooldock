/**
 * MultiFilePicker component for uploading multiple files
 * Supports drag-and-drop and shows existing files with ability to remove
 */
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';
import { FileIcon, ImageIcon, Plus, Trash2, Upload, X } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/Components/ui/button';

export default function MultiFilePicker({
  value = [],
  onChange,
  existingFiles = [],
  onRemoveExisting,
  accept = 'image/*',
  directory = 'temp',
  maxFiles = 5,
  className,
  ...props
}) {
  const [uploading, setUploading] = useState(false);
  const [uploadedFiles, setUploadedFiles] = useState([]);
  const [removedExistingIds, setRemovedExistingIds] = useState([]);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);
  const page = usePage();

  const totalFiles =
    uploadedFiles.length + existingFiles.filter((f) => !removedExistingIds.includes(f.id)).length;
  const canAddMore = totalFiles < maxFiles;

  const handleFileSelect = useCallback(
    async (files) => {
      if (!files || files.length === 0) return;

      const filesToUpload = Array.from(files).slice(0, maxFiles - totalFiles);
      if (filesToUpload.length === 0) {
        toast.warning(`Maximum ${maxFiles} files allowed`);
        return;
      }

      const maxFileSizeKB = page.props.media?.max_file_size_kb || 10240;
      const maxFileSizeBytes = maxFileSizeKB * 1024;
      const maxFileSizeMB = page.props.media?.max_file_size_mb || maxFileSizeKB / 1024;

      for (const file of filesToUpload) {
        if (file.size > maxFileSizeBytes) {
          toast.error('File too large', {
            description: `${file.name} exceeds the maximum size of ${maxFileSizeMB}MB`,
          });
          return;
        }
      }

      setUploading(true);

      try {
        const csrfToken =
          page.props.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';

        const uploadPromises = filesToUpload.map(async (file) => {
          const formData = new FormData();
          formData.append('file', file);
          formData.append('directory', directory);

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
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `Failed to upload ${file.name}`);
          }

          const data = await response.json();
          return {
            id: data.id,
            filename: data.filename || file.name,
            url: data.url,
            mime_type: data.mime_type,
            size: data.size,
          };
        });

        const results = await Promise.all(uploadPromises);
        const newUploadedFiles = [...uploadedFiles, ...results];
        setUploadedFiles(newUploadedFiles);
        onChange?.([...value, ...results.map((f) => f.id)]);
        toast.success(`${results.length} file(s) uploaded`);
      } catch (err) {
        toast.error('Upload failed', {
          description: err.message || 'An error occurred during upload',
        });
      } finally {
        setUploading(false);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      }
    },
    [directory, onChange, page.props, value, uploadedFiles, totalFiles, maxFiles]
  );

  const handleDrop = useCallback(
    (e) => {
      e.preventDefault();
      e.stopPropagation();
      setDragActive(false);
      handleFileSelect(e.dataTransfer.files);
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

  const handleRemoveUploaded = useCallback(
    (fileId) => {
      const newUploadedFiles = uploadedFiles.filter((f) => f.id !== fileId);
      setUploadedFiles(newUploadedFiles);
      onChange?.(value.filter((id) => id !== fileId));
    },
    [uploadedFiles, onChange, value]
  );

  const handleRemoveExisting = useCallback(
    (fileId) => {
      setRemovedExistingIds((prev) => [...prev, fileId]);
      onRemoveExisting?.(fileId);
    },
    [onRemoveExisting]
  );

  const isImage = (file) => {
    return file.mime_type?.startsWith('image/') || file.url?.match(/\.(jpg|jpeg|png|gif|webp)$/i);
  };

  const formatFileSize = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  const visibleExistingFiles = existingFiles.filter((f) => !removedExistingIds.includes(f.id));
  const allFiles = [...visibleExistingFiles, ...uploadedFiles];

  return (
    <div className={cn('space-y-3', className)} {...props}>
      {/* File List */}
      {allFiles.length > 0 && (
        <div className="space-y-2">
          {allFiles.map((file) => {
            const isExisting = existingFiles.some((f) => f.id === file.id);
            return (
              <div
                key={file.id}
                className="flex items-center gap-3 p-3 border rounded-lg bg-muted/30"
              >
                {isImage(file) ? (
                  <div className="w-10 h-10 rounded overflow-hidden shrink-0">
                    <img
                      src={file.url}
                      alt={file.filename}
                      className="w-full h-full object-cover"
                    />
                  </div>
                ) : (
                  <div className="w-10 h-10 rounded bg-muted flex items-center justify-center shrink-0">
                    <FileIcon className="w-5 h-5 text-muted-foreground" />
                  </div>
                )}
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium truncate">{file.filename}</p>
                  <p className="text-xs text-muted-foreground">
                    {formatFileSize(file.size)}
                    {isExisting && <span className="ml-2 text-primary">• Saved</span>}
                  </p>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="shrink-0 text-muted-foreground hover:text-destructive"
                  onClick={() =>
                    isExisting ? handleRemoveExisting(file.id) : handleRemoveUploaded(file.id)
                  }
                >
                  <Trash2 className="w-4 h-4" />
                </Button>
              </div>
            );
          })}
        </div>
      )}

      {/* Upload Area */}
      {canAddMore && (
        <div
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          className={cn(
            'relative border-2 border-dashed rounded-lg p-4 transition-colors cursor-pointer',
            dragActive
              ? 'border-primary bg-primary/5'
              : 'border-muted-foreground/25 hover:border-muted-foreground/50',
            uploading && 'opacity-50 pointer-events-none'
          )}
          onClick={() => fileInputRef.current?.click()}
        >
          <input
            ref={fileInputRef}
            type="file"
            accept={accept}
            multiple
            onChange={(e) => handleFileSelect(e.target.files)}
            className="hidden"
            disabled={uploading}
          />

          <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
            {uploading ? (
              <>
                <Upload className="h-6 w-6 animate-pulse" />
                <p className="text-sm">Uploading...</p>
              </>
            ) : (
              <>
                <Plus className="h-6 w-6" />
                <p className="text-sm">
                  Drop files here or <span className="text-primary">browse</span>
                </p>
                <p className="text-xs">
                  {maxFiles - totalFiles} of {maxFiles} remaining
                </p>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
