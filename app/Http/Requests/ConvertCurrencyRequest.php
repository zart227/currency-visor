<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from' => ['required', 'string', 'size:3'],
            'to' => ['required', 'string', 'size:3'],
            'api_method' => ['required', 'string', 'in:rest,soap'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Сумма обязательна для заполнения',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.min' => 'Сумма должна быть больше 0',
            'from.required' => 'Выберите исходную валюту',
            'from.string' => 'Неверный формат исходной валюты',
            'from.size' => 'Код валюты должен состоять из 3 символов',
            'to.required' => 'Выберите целевую валюту',
            'to.string' => 'Неверный формат целевой валюты',
            'to.size' => 'Код валюты должен состоять из 3 символов',
            'api_method.required' => 'Выберите метод API',
            'api_method.string' => 'Неверный формат метода API',
            'api_method.in' => 'Выбран неподдерживаемый метод API',
        ];
    }
} 