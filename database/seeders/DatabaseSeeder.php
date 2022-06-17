<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Like;
use App\Models\RumHashtag;
use App\Models\RumPost;
use App\Models\UserRum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rum;
use App\Models\User;
use Faker\Generator as Faker;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(Faker $faker)
    {
        User::factory()->count(
            collect()->range(10, 30)->random()
        )->create([
            'password' => Hash::make('123'),
        ]);

        Rum::factory()
            ->has(RumPost::factory()->count(
                collect()->range(3, 12)->random()
            ), 'posts')
            ->count(7)
            ->create()
            ->each(function(Rum $rum) {
                UserRum::factory()->create([
                    'rum_id' => $rum->id,
                    'user_id' => User::all()->random()->id
                ]);

                RumHashtag::factory()
                    ->count(
                        collect()->range(3, 20)->random()
                    )
                    ->create([
                        'rum_id' => $rum->id,
                    ]);

                $rum->posts()->each(function(RumPost $post) use($rum) {
                    Like::factory()
                        ->count(
                            collect()->range(7, 150)->random()
                        )
                        ->create([
                            'user_id' => $rum->users->random()->id,
                            'post_id' => $post->id,
                        ]);
                    Comment::factory()
                        ->count(
                            collect()->range(3, 50)->random()
                        )
                        ->create([
                            'user_id' => $rum->users->random()->id,
                            'post_id' => $post->id,
                        ]);
                });
            });
    }
}
