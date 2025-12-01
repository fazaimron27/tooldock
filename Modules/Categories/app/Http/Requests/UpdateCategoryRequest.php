<?php

namespace Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Categories\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category->id ?? $category;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $query->where('type', $this->input('type')))
                    ->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where(fn ($query) => $query->where('type', $this->input('type')))
                    ->ignore($categoryId),
            ],
            'type' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', 'lowercase'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$categoryId]),
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value) {
                        $parent = Category::find($value);
                        if ($parent) {
                            if ($this->wouldCreateCircularReference($categoryId, $value)) {
                                $fail('Cannot set parent: this would create a circular reference.');
                            }

                            if ($parent->type !== $this->input('type')) {
                                $fail('The parent category must be of the same type.');
                            }
                        }
                    }
                },
            ],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Check if setting a parent would create a circular reference.
     *
     * A circular reference occurs when the current category is an ancestor of the new parent.
     * This would create: newParent -> ... -> current -> newParent (a cycle)
     *
     * Note: Setting a parent to an ancestor of the current category is allowed.
     * For example, if C has parent B, and B has parent A, setting C's parent to A
     * is fine - it just makes C a direct child of A instead of a grandchild.
     *
     * @param  int  $categoryId  The ID of the category being updated
     * @param  int  $newParentId  The ID of the new parent
     * @return bool True if it would create a circular reference
     */
    private function wouldCreateCircularReference(int $categoryId, int $newParentId): bool
    {
        if ($categoryId === $newParentId) {
            return true;
        }

        /**
         * Check if the current category is an ancestor of the new parent.
         * This would create: newParent -> ... -> current -> newParent (a cycle).
         */
        $newParentAncestors = $this->getAllAncestors($newParentId);
        if (in_array($categoryId, $newParentAncestors, true)) {
            return true;
        }

        return false;
    }

    /**
     * Get all ancestor IDs for a category recursively.
     *
     * @param  int  $categoryId  The category ID
     * @return array<int> Array of ancestor IDs
     */
    private function getAllAncestors(int $categoryId): array
    {
        $ancestors = [];
        $visited = [];
        $currentId = $categoryId;

        /**
         * Traverse up the parent chain until we hit null or a cycle.
         * Prevents infinite loops in case of data corruption.
         */
        while ($currentId) {
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            $category = Category::with('parent')->find($currentId);
            if (! $category || ! $category->parent) {
                break;
            }

            $parentId = $category->parent->id;
            $ancestors[] = $parentId;
            $currentId = $parentId;
        }

        return $ancestors;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $this->user()->can('update', $category);
    }
}
