<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read ActiveRecord\Query<Person> $hires
 * @property-read ActiveRecord\Query<Person> $teachers
 * @property-read ActiveRecord\Query<Person> $followers
 */
#[HasMany(Person::class, foreign_key: 'hire_skill_id', as: 'hires')]
#[HasMany(Person::class, foreign_key: 'teach_skill_id', as: 'teachers')]
#[HasMany(Person::class, foreign_key: 'summon_skill_id', as: 'followers')]
final class Skill extends ActiveRecord
{
    #[Id, Serial]
    public int $skill_id;

    #[Character(unique: true)]
    public string $name;
}
