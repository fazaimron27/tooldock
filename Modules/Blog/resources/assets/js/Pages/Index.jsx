import { Button } from '@/Components/ui/button';

export default function Index() {
    return (
        <div className="min-h-screen bg-gray-100 py-12">
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6">
                        <h1 className="text-3xl font-bold text-blue-600">
                            Module Works!
                        </h1>
                        <p className="mt-4 text-gray-600">
                            This page is loaded from the Blog module using Inertia.js
                        </p>
                        <div className="mt-6 flex gap-4">
                            <Button>Default Button</Button>
                            <Button variant="outline">Outline Button</Button>
                            <Button variant="secondary">Secondary Button</Button>
                            <Button variant="destructive">Destructive Button</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

