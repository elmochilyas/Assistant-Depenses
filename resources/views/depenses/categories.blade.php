<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Aperçu par catégorie') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Catégorie') }}</th>
                                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Nombre d\'articles') }}</th>
                                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total estimé') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($categories as $cat)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium">{{ $cat->categorie->label() }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ $cat->total_count }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ number_format($cat->total_amount, 2) }} MAD</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6">
                        <a href="{{ route('depenses.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm">
                            &larr; {{ __('Retour aux dépenses') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
