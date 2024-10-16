<?php

namespace Telkins\Validation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Telkins\Validation\Contracts\FieldRuleSetContract;

/**
 * Source/inspiration: https://medium.com/@juampi92/laravel-5-5-validation-ruleception-rule-inside-rule-2762d2cf4471.
 */
abstract class AbstractFieldRuleSet implements Rule, FieldRuleSetContract
{
    protected $data = [];

    protected $except = [];

    protected $validator;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Use the rule set without the specified rules.
     *
     * @param  mixed  $rules
     * @return self
     */
    public function except($rules): self
    {
        $this->except = ! is_array($rules) ? func_get_args() : $rules;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $this->validate($value, $this->rules(), $attribute);
    }

    abstract public function rules(): array;

    /**
     * @param  mixed  $value
     * @param  array|string|Rule  $rules
     * @param  string  $name  Name of the property (optional)
     * @return bool
     */
    protected function validate($value, $rules, string $name = 'variable'): bool
    {
        $rules = $this->prepareRules($rules);

        $data = empty($this->data) ? [$name => $value] : $this->data;

        $this->validator = Validator::make($data, [$name => $rules]);

        return $this->validator->passes();
    }

    /**
     * Prepare the rules.
     *
     * @param  mixed  $rules
     * @return array
     */
    protected function prepareRules($rules): array
    {
        if (! is_string($rules) && ! is_array($rules)) {
            $rules = [$rules];
        }

        return collect($rules)
            ->reject(function ($rule) {
                return in_array($rule, $this->except);
            })->toArray();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        $errors = $this->validator->errors();

        if ($errors->any()) {
            return $errors->first();
        }

        return 'The :attribute is not valid.';
    }

    /**
     * Get the validation rules with exceptions removed.
     * - optionally 'prepend' or 'append' additional rules.
     *
     * @param  array  $prepend
     * @param  array  $append
     * @return array
     */
    public function getRules(array $prepend = [], array $append = []): array
    {
        return $this->prepareRules(array_merge($prepend, $this->rules(), $append));
    }

    /**
     * Get the validation rules with exceptions removed & 'required' rule prepended.
     * - optionally 'prepend' or 'append' additional rules.
     *
     * @param  array  $prepend
     * @param  array  $append
     * @return array
     */
    public function getRulesRequired(array $prepend = [], array $append = []): array
    {
        return $this->getRules(array_merge(['required'], $prepend), $append);
    }
}
