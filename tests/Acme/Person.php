<?php

namespace Test\ICanBoogie\Acme;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @property-read ActiveRecord\Query<Equipment> $equipment
 * @property-read Skill $hire_skill
 * @property-read Skill $summon_skill
 * @property-read Skill $teach_skill
 *
 * @extends ActiveRecord<int>
 */
#[HasMany(Equipment::class, through: PersonEquipment::class)]
class Person extends ActiveRecord
{
    #[Id, Serial]
    public int $person_id;

    #[Character]
    public string $name;

    #[BelongsTo(DanceSession::class, null: true)]
    public ?int $dance_session_id;

    #[BelongsTo(Skill::class, null: true)]
    public ?int $hire_skill_id;

    #[BelongsTo(Skill::class, null: true)]
    public ?int $summon_skill_id;

    #[BelongsTo(Skill::class, null: true)]
    public ?int $teach_skill_id;
}
