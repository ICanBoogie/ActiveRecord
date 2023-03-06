<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

/**
 * Provides models.
 */
interface ModelProvider
{
    /**
     * Provides a model for a given identifier.
     *
     * @param string $id
     *     A model identifier.
     *
     * @throws ModelNotDefined if the model is not defined.
     */
    public function model_for_id(string $id): Model;
}
