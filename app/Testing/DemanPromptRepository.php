<?php

namespace App\Testing;

class DemanPromptRepository
{
    /**
     * @return array<string, string>
     */
    public function promptsForCategory(?string $categoryName): array
    {
        $slug = TestingFlowCategoryMapper::slugFromCategoryName($categoryName);
        if ($slug === null) {
            return [];
        }

        $prompts = config('deman_prompts', []);

        return is_array($prompts[$slug] ?? null) ? $prompts[$slug] : [];
    }
}
