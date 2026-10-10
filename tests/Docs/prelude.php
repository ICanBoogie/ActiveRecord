<?php

/**
 * Variables available to the blocks of the documentation, in addition to those of the setup.
 *
 * @var \ICanBoogie\ActiveRecord\Config $config
 * @var \ICanBoogie\ActiveRecord\ConnectionRegistry $connections
 * @var \ICanBoogie\ActiveRecord\ModelRegistry $models
 * @var \App\User $alice
 */

use App\Article;
use App\Comment;
use App\Node;
use App\User;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use Test\ICanBoogie\Fixtures;

$builder = Fixtures::with_main_connection(new ConfigBuilder())
    ->use_attributes()
    ->add_record(User::class)
    ->add_record(Node::class)
    ->add_record(Article::class)
    ->add_record(Comment::class);

$connection = $connections->connection_for_id('primary');
$model = Article::model();
$query = Article::query();
$article = Article::model()->find(1);
$user = $alice;
$comment = Comment::query()->one;
