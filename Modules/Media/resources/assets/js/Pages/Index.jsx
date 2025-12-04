/**
 * Media files listing page with server-side pagination
 * Displays media files with preview, metadata, and actions
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { useDisclosure } from '@/Hooks/useDisclosure';
import { router } from '@inertiajs/react';
import { Download, Image as ImageIcon, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DataTable from '@/Components/DataDisplay/DataTable';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ mediaFiles, defaultPerPage = 20 }) {
  const deleteDialog = useDisclosure();
  const imagePreviewDialog = useDisclosure();
  const [fileToDelete, setFileToDelete] = useState(null);
  const [previewImage, setPreviewImage] = useState(null);

  const handleDeleteClick = useCallback(
    (file) => {
      setFileToDelete(file);
      deleteDialog.onOpen();
    },
    [deleteDialog]
  );

  const handleDeleteConfirm = () => {
    if (fileToDelete) {
      router.delete(route('media.destroy', { medium: fileToDelete.id }), {
        onSuccess: () => {
          deleteDialog.onClose();
          setFileToDelete(null);
        },
      });
    }
  };

  const handleImageClick = useCallback(
    (file) => {
      if (file.mime_type?.startsWith('image/')) {
        setPreviewImage(file);
        imagePreviewDialog.onOpen();
      }
    },
    [imagePreviewDialog]
  );

  const formatFileSize = (bytes) => {
    if (bytes === 0) {
      return '0 Bytes';
    }
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
  };

  const columns = useMemo(
    () => [
      {
        accessorKey: 'filename',
        header: 'File',
        cell: (info) => {
          const file = info.row.original;
          const isImage = file.mime_type?.startsWith('image/');
          return (
            <div className="flex items-center gap-3">
              {isImage ? (
                <button
                  type="button"
                  onClick={() => handleImageClick(file)}
                  className="cursor-pointer hover:opacity-80 transition-opacity"
                >
                  <img
                    src={file.url}
                    alt={file.filename}
                    className="h-10 w-10 rounded object-cover"
                  />
                </button>
              ) : (
                <div className="flex h-10 w-10 items-center justify-center rounded bg-muted">
                  <ImageIcon className="h-5 w-5 text-muted-foreground" />
                </div>
              )}
              <div className="flex flex-col">
                <span className="font-medium">{file.filename}</span>
                <span className="text-xs text-muted-foreground">{file.mime_type}</span>
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'size',
        header: 'Size',
        cell: (info) => {
          return <span>{formatFileSize(info.getValue())}</span>;
        },
      },
      {
        accessorKey: 'model_type',
        header: 'Attached To',
        cell: (info) => {
          const file = info.row.original;
          if (file.model) {
            const modelType = file.model_type?.split('\\').pop() || 'Unknown';
            // If it's a User model, show the user's name
            if (modelType === 'User' && file.model.name) {
              return (
                <span className="text-sm">
                  {file.model.name} ({file.model.email})
                </span>
              );
            }
            // For other models, show type and ID
            return (
              <span className="text-sm">
                {modelType} #{file.model_id}
              </span>
            );
          }
          return <span className="text-muted-foreground text-sm">Not attached</span>;
        },
      },
      {
        accessorKey: 'created_at',
        header: 'Uploaded',
        cell: (info) => {
          const file = info.row.original;
          return <span>{new Date(file.created_at).toLocaleDateString()}</span>;
        },
      },
      {
        id: 'actions',
        header: 'Actions',
        cell: (info) => {
          const file = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                <a href={file.url} target="_blank" rel="noopener noreferrer">
                  <Download className="h-4 w-4" />
                  <span className="sr-only">Download file</span>
                </a>
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                onClick={() => handleDeleteClick(file)}
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">Delete file</span>
              </Button>
            </div>
          );
        },
      },
    ],
    [handleDeleteClick, handleImageClick]
  );

  const pageCount = useMemo(() => {
    if (mediaFiles.total !== undefined && mediaFiles.per_page) {
      return Math.ceil(mediaFiles.total / mediaFiles.per_page);
    }
    return undefined;
  }, [mediaFiles.total, mediaFiles.per_page]);

  const { tableProps } = useDatatable({
    data: mediaFiles.data || [],
    columns,
    route: route('media.index'),
    serverSide: true,
    pageSize: mediaFiles.per_page || defaultPerPage,
    initialSorting: [{ id: 'created_at', desc: true }],
    pageCount: pageCount,
    only: ['mediaFiles'],
  });

  useEffect(() => {
    if (!tableProps.table || mediaFiles.current_page === undefined) {
      return;
    }

    const currentPageIndex = mediaFiles.current_page - 1;
    const currentPagination = tableProps.table.getState().pagination;
    const serverPageSize = mediaFiles.per_page || defaultPerPage;

    if (
      currentPagination.pageIndex !== currentPageIndex ||
      currentPagination.pageSize !== serverPageSize
    ) {
      window.requestAnimationFrame(() => {
        tableProps.table.setPagination({
          pageIndex: currentPageIndex,
          pageSize: serverPageSize,
        });
      });
    }
  }, [tableProps.table, mediaFiles.current_page, mediaFiles.per_page, defaultPerPage]);

  return (
    <DashboardLayout header="Media">
      <PageShell title="Media Files">
        <DataTable
          {...tableProps}
          title="Media Files"
          description="A list of all uploaded media files in the system"
          showCard={true}
        />
      </PageShell>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDeleteConfirm}
        onCancel={() => {
          deleteDialog.onClose();
          setFileToDelete(null);
        }}
        title="Delete Media File"
        message={
          fileToDelete
            ? `Are you sure you want to delete "${fileToDelete.filename}"? This action cannot be undone and will permanently remove the file from storage.`
            : 'Are you sure you want to delete this file?'
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />

      <Dialog
        open={imagePreviewDialog.isOpen}
        onOpenChange={(open) => {
          if (open) {
            imagePreviewDialog.onOpen();
          } else {
            imagePreviewDialog.onClose();
            setPreviewImage(null);
          }
        }}
      >
        <DialogContent className="max-w-4xl max-h-[90vh] p-0">
          {previewImage && (
            <>
              <DialogHeader className="px-6 pt-6 pb-4">
                <DialogTitle>{previewImage.filename}</DialogTitle>
                <DialogDescription>
                  {previewImage.mime_type} • {formatFileSize(previewImage.size)}
                </DialogDescription>
              </DialogHeader>
              <div className="relative flex items-center justify-center bg-muted/50 p-6 overflow-auto">
                <img
                  src={previewImage.url}
                  alt={previewImage.filename}
                  className="max-h-[70vh] max-w-full object-contain rounded-lg"
                />
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  );
}
