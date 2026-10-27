<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'description' => fake()->sentence(12),
            'offering' => 'academic',
            'body' => "## Introduction\n\n".fake()->paragraph(),
            'published_on' => now()->subDay(),
            'is_published' => true,
        ];
    }
}
