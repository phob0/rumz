<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Dislike;
use App\Models\HistoryPayment;
use App\Models\Like;
use App\Models\RumHashtag;
use App\Models\RumPost;
use App\Models\Subscription;
use App\Models\UserRum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Rum;
use App\Models\User;
use Faker\Generator as Faker;

class DatabaseSeeder extends Seeder
{
    /**
     * 
     * IMPORTANT!! BEFORE RUNNING COMMENT FROM RUM MODEL DEFAULT WITH`S is_granted and is_admin
     * 
     */
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(Faker $faker)
    {
        $rumTypes = [
            'free',
            'paid',
            'private',
            'confidential',
        ];

        $rumPrivileges = [
            'me',
            'all',
            'all',
            'members',
        ];

        User::factory()->count(1)->create([
            'superadmin' => 1,
            'password' => Hash::make('123'),
        ]);

        User::factory()->count(20)->create([
            'password' => Hash::make('123'),
        ]);

        $users = User::where('superadmin', 0)->get()->chunk(4)->toArray();
        $rumAdmins = $users[0];

        array_shift($users);

        $users = collect($users)->map(function($item) {
            return collect($item)->values()->toArray();
        })->toArray();

        Rum::factory()
            ->has(RumPost::factory()->count(4), 'posts')
            ->has(RumHashtag::factory()->count(collect()->range(3, 10)->random()), 'hashtags')
            ->createMany([
                [
                    'user_id' => $rumAdmins[0]['id'],
                    'type' => $rumTypes[0],
                    'privilege' => $rumPrivileges[0],
                ],
                [
                    'user_id' => $rumAdmins[1]['id'],
                    'type' => $rumTypes[1],
                    'privilege' => $rumPrivileges[1],
                ],
                [
                    'user_id' => $rumAdmins[2]['id'],
                    'type' => $rumTypes[2],
                    'privilege' => $rumPrivileges[2],
                ],
                [
                    'user_id' => $rumAdmins[3]['id'],
                    'type' => $rumTypes[3],
                    'privilege' => $rumPrivileges[3],
                ]
            ])
            ->each(function(Rum $rum, $key) use($users) {
                if ($rum->type !== Rum::TYPE_PAID) {
                    UserRum::factory()
                        ->createMany([
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][0]['id']
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][1]['id']
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][2]['id']
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][3]['id']
                            ]
                        ]);
                } else {
                    Subscription::factory()
                        ->has(HistoryPayment::factory()->count(4), 'history_payments')
                        ->createMany([
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][0]['id'],
                                'expire_at' => Carbon::now()->addMonth()
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][0]['id'],
                                'expire_at' => Carbon::now()->addMonth()
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][0]['id'],
                                'expire_at' => Carbon::now()->addMonth()
                            ],
                            [
                                'rum_id' => $rum->id,
                                'user_id' => $users[$key][0]['id'],
                                'expire_at' => Carbon::now()->addMonth()
                            ]
                        ]);
                }

                $rum->posts()->each(function(RumPost $post) use($rum) {
                    if ($rum->type !== Rum::TYPE_PAID) {
                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'likeable_type' => RumPost::class,
                                'likeable_id' => $post->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'dislikeable_type' => RumPost::class,
                                'dislikeable_id' => $post->id
                            ]);
                        Comment::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'post_id' => $post->id
                            ]);

                        $post->refresh();

                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'likeable_type' => Comment::class,
                                'likeable_id' => $post->comments->random()->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'dislikeable_type' => Comment::class,
                                'dislikeable_id' => $post->comments->random()->id
                            ]);

                        CommentReply::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'comment_id' => function() use($post){
                                    return $post->comments->random()->id;
                                },
                            ]);

                        $post->refresh();

                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'likeable_type' => CommentReply::class,
                                'likeable_id' => $post->comment_replies->random()->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->users->random()->id;
                                },
                                'dislikeable_type' => CommentReply::class,
                                'dislikeable_id' => $post->comment_replies->random()->id
                            ]);
                    } else {
                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'likeable_type' => RumPost::class,
                                'likeable_id' => $post->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'dislikeable_type' => RumPost::class,
                                'dislikeable_id' => $post->id
                            ]);
                        Comment::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'post_id' => $post->id
                            ]);

                        $post->refresh();

                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'likeable_type' => Comment::class,
                                'likeable_id' => $post->comments->random()->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'dislikeable_type' => Comment::class,
                                'dislikeable_id' => $post->comments->random()->id
                            ]);

                        CommentReply::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'comment_id' => function() use($post){
                                    return $post->comments->random()->id;
                                },
                            ]);

                        $post->refresh();

                        Like::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'likeable_type' => CommentReply::class,
                                'likeable_id' => $post->comment_replies->random()->id
                            ]);
                        Dislike::factory()
                            ->count(
                                collect()->range(1, 3)->random()
                            )
                            ->create([
                                'user_id' => function() use($rum){
                                    return $rum->subscribed->random()->id;
                                },
                                'dislikeable_type' => CommentReply::class,
                                'dislikeable_id' => $post->comment_replies->random()->id
                            ]);
                    }
                });
            });
    }
}
