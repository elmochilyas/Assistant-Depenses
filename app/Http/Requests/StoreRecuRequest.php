<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'texte_brut' => ['required', 'string', 'min:10', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'texte_brut.required' => 'Le texte du reçu est obligatoire.',
            'texte_brut.min' => 'Le texte doit contenir au moins 10 caractères.',
            'texte_brut.max' => 'Le texte ne peut pas dépasser 10 000 caractères.',
        ];
    }
}
