<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nouveau reçu
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 py-8">
        <form action="{{ route('recus.store') }}" method="POST" class="bg-white shadow rounded-lg p-6">
            @csrf

            <div class="mb-6">
                <label for="texte_brut" class="block text-sm font-medium text-gray-700 mb-2">
                    Texte brut du reçu
                </label>
                <textarea
                    id="texte_brut"
                    name="texte_brut"
                    rows="15"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Collez ici le texte brut du reçu fournisseur...">{{ old('texte_brut') }}</textarea>
                @error('texte_brut')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Minimum 10 caractères, maximum 10000 caractères.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('recus.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Annuler</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Soumettre pour extraction</button>
            </div>
        </form>
    </div>
</x-app-layout>
