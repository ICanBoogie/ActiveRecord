<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

interface ModelResolver
{
    /**
     * @param class-string<ActiveRecord>|ActiveRecord $class_or_activerecord
     */
    public function model_for_activerecord(string|ActiveRecord $class_or_activerecord): Model;
}
