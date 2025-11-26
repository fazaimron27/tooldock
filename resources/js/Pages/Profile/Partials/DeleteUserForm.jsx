import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { cn } from '@/lib/utils';

export default function DeleteUserForm({ className = '' }) {
    const [open, setOpen] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
            },
            onError: () => {
                passwordInput.current?.focus();
            },
            onFinish: () => {
                if (!errors.password) {
                    reset();
                }
            },
        });
    };

    const handleOpenChange = (isOpen) => {
        setOpen(isOpen);
        if (!isOpen) {
            clearErrors();
            reset();
        }
    };

    return (
        <Card className={cn('border-destructive/50', className)}>
            <CardHeader>
                <CardTitle className="text-destructive">Delete Account</CardTitle>
                <CardDescription>
                    Once your account is deleted, all of its resources and data will be
                    permanently deleted. Before deleting your account, please download any
                    data or information that you wish to retain.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <AlertDialog open={open} onOpenChange={handleOpenChange}>
                    <AlertDialogTrigger asChild>
                        <Button variant="destructive">Delete Account</Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be undone. This will permanently delete
                                your account and remove all your data from our servers.
                                Please enter your password to confirm you would like to
                                permanently delete your account.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <form onSubmit={deleteUser} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Enter your password"
                                    className={cn(errors.password && 'border-destructive')}
                                    autoFocus
                                />
                                {errors.password && (
                                    <p className="text-sm text-destructive">
                                        {errors.password}
                                    </p>
                                )}
                            </div>
                            <AlertDialogFooter>
                                <AlertDialogCancel type="button">Cancel</AlertDialogCancel>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {processing ? 'Deleting...' : 'Delete Account'}
                                </Button>
                            </AlertDialogFooter>
                        </form>
                    </AlertDialogContent>
                </AlertDialog>
            </CardContent>
        </Card>
    );
}
