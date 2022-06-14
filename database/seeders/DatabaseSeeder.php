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
        /**/
         User::create([
            'name' => 'user',
            'email' => 'user@rumz.com',
            'password' => Hash::make('123'),
         ]);

        Rum::create([
            'user_id' => User::first()->id,
            'title' => $faker->text,
            'description' => $faker->text,
            'image' => $faker->image,
            'type' => Rum::TYPE_FREE,
            'privilege' => Rum::FOR_ALL,
        ]);

        RumHashtag::create([
            'rum_id' => Rum::first()->id,
            'hashtag' => $faker->name,
        ]);

        UserRum::create([
            'user_id' => User::first()->id,
            'rum_id' => Rum::first()->id,
        ]);

        RumPost::create([
            'rum_id' => Rum::first()->id,
            'approved' => 0,
            'title' => $faker->text,
            'description' => $faker->text,
        ]);

        Like::create([
            'user_id' => User::first()->id,
            'post_id' => RumPost::first()->id,
        ]);

        Comment::create([
            'user_id' => User::first()->id,
            'post_id' => RumPost::first()->id,
            'comment' => $faker->text,
        ]);
    }
}
