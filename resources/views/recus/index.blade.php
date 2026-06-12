<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Mes reçus') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 px-4 py-2 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <a href="{{ route('recus.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('Nouveau reçu') }}
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($recus->isEmpty())
                        <p>{{ __('Aucun reçu pour le moment.') }}</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Texte') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Statut') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Dépenses') }}</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($recus as $recu)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ Str::limit($recu->texte_brut, 80) }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full @if($recu->statut === 'en_attente') bg-yellow-100 text-yellow-800 @elseif($recu->statut === 'traite') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                                {{ $recu->statut->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">{{ $recu->depenses_count }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $recu->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <a href="{{ route('recus.show', $recu) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Voir') }}</a>
                                            <form action="{{ route('recus.destroy', $recu) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Supprimer ce reçu ?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-2 text-red-600 dark:text-red-400 hover:underline">{{ __('Supprimer') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
