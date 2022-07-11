<?php

namespace App\Models;

use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    /**
     * The current Faker instance.
     *
     * @var \Faker\Generator
     */
    protected Generator $faker;

    protected $fillable = [
        'url'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->faker = $this->withFaker();
    }

    public function imageable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get a new Faker instance.
     *
     * @return \Faker\Generator
     */
    protected function withFaker(): Generator
    {
        return Container::getInstance()->make(Generator::class);
    }

    /**
     * TODO: move image upload to storage from controller
     * Interact with the rums image.
     *
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => (!is_null($value) ? $value : $this->faker->imageUrl('300', '300', null, false, env('APP_NAME')))
        );
    }
}
