<?php

namespace Database\Factories;

use App\Enums\Offering;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'offering' => Offering::Tuition,
            'subject' => fake()->sentence(4),
            'last_message_at' => now(),
        ];
    }

    /**
     * Started by this person, who becomes its member.
     */
    public function startedBy(User $user): static
    {
        return $this->state(['started_by' => $user->id])
            ->afterCreating(fn (Conversation $conversation) => $conversation->users()->attach($user->id, ['is_member' => true]));
    }
}
