<!-- php artisan make:model -m Post
php artisan make:model -m Author
php artisan make:model -m Profile
The -m flag creates a migration to go along with the model that you will use to create the table schema.

The data models will have the following associations:

Post -> belongsTo -> Author
Author -> hasMany -> Post
Author -> hasOne -> Profile -->

<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model;

class Post extends Model{
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}

// end post model

namespace App\Model;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class Author extends Model
{
  /**
   * Get the comments for the blog post.
   */
  public function posts()
  {
      return $this->hasMany(Post::class);
  }

  /**
   * Get the phone record associated with the user.
   */
  public function profile()
  {
      return $this->hasOne(Profile::class);
  }

}
// end Author class section

// Migration and FACTORY SYSTEM

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('author_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('bio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authors');
    }
}
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('author_id');
            $table->date('birthday');
            $table->string('city');
            $table->string('state');
            $table->string('website');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}

// Model Factories
// To create some fake data that we can run queries against, let’s add a few model factories that we can use to seed the database with test data.
//
// Open the database/factories/ModelFactory.php file and append the following three factories to the file below the existing User factory:

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Post::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence,
        'author_id' => function () {
            return factory(App\Author::class)->create()->id;
        },
        'body' => $faker->paragraphs(rand(3,10), true),
    ];
});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Author::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'bio' => $faker->paragraph,
    ];
});

$factory->define(App\Profile::class, function (Faker\Generator $faker) {
    return [
        'birthday' => $faker->dateTimeBetween('-100 years', '-18 years'),
        'author_id' => function () {
            return factory(App\Author::class)->create()->id;
        },
        'city' => $faker->city,
        'state' => $faker->state,
        'website' => $faker->domainName,
    ];
});
// These factories will make it easy to populate a bunch of posts that we can query; we can use them to create associated model data with database seeding.
//
// Open the database/seeds/DatabaseSeeder.php file and add the following to the DatabaseSeeder::run() method:

public function run()
{
    $authors = factory(App\Author::class, 5)->create();
    $authors->each(function ($author) {
        $author
            ->profile()
            ->save(factory(App\Profile::class)->make());
        $author
            ->posts()
            ->saveMany(
                factory(App\Post::class, rand(20,30))->make()
            );
    });
}
// You create five authors and then loop through each author and save an associated profile and many posts (between 20 and 30 posts per author).
// We are done creating migrations, models, model factories, and database seeds. We can combine it all and re-run our migrations and database seeding in a repeatable way:
// php artisan migrate:refresh
// php artisan db:seed

// Experimenting with Eager Loading
// We are finally ready to see eager loading in action. In my opinion, the best way to visualize eager loading is logging queries to the storage/logs/laravel.log file.
// To log database queries, you can either enable the MySQL log or listen for database calls from Eloquent. To log queries through Eloquent, add the following code to
// the app/Providers/AppServiceProvider.php boot() method:

namespace App\Providers;

use DB;
use Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::listen(function($query) {
            Log::info(
                $query->sql,
                $query->bindings,
                $query->time
            );
        });
    }

    // ...
}
// I like to wrap this listener around a configuration check so that I can toggle query logging on and off. You can also get this information from the Laravel Debugbar.
// Let’s see what happens when we don’t load model relations eagerly. Clear out your storage/log/laravel.log file and run the “tinker” command.

php artisan tinker

>>> $posts = App\Post::all();
>>> $posts->map(function ($post) {
...     return $post->author;
... });
>>> ...
If you check your laravel.log file, you should see a bunch of queries to get the associated author:

[2017-08-04 06:21:58] local.INFO: select * from `posts`
[2017-08-04 06:22:06] local.INFO: select * from `authors` where `authors`.`id` = ? limit 1 [1]
[2017-08-04 06:22:06] local.INFO: select * from `authors` where `authors`.`id` = ? limit 1 [1]
[2017-08-04 06:22:06] local.INFO: select * from `authors` where `authors`.`id` = ? limit 1 [1]
....
Empty your laravel.log file again, and this time call with() to eager load the author records:

php artisan tinker

>>> $posts = App\Post::with('author')->get();
>>> $posts->map(function ($post) {
...     return $post->author;
... });
...
This time you should only see two queries in the log file. The first query for all the posts, and the second query for all the associated authors.

[2017-08-04 07:18:02] local.INFO: select * from `posts`
[2017-08-04 07:18:02] local.INFO: select * from `authors` where `authors`.`id` in (?, ?, ?, ?, ?) [1,2,3,4,5]
If you had multiple related associations, you can eager load them with an array:

$posts = App\Post::with(['author', 'comments'])->get();
Nested Eager Loading in Eloquent
Nested eager loading works the same way. In our example, the Author model has one profile. Thus, a query will be executed for each profile.
Empty out the laravel.log file and let’s try it out:

php artisan tinker

>>> $posts = App\Post::with('author')->get();
>>> $posts->map(function ($post) {
...     return $post->author->profile;
... });
...
// You will now have seven queries. The first two are eagerly loaded, and then each time we get a new profile a query is required to get the profile data for each author.
// With eager loading we can avoid the extra queries in nested relationships. Clear your laravel.log file one last time and run the following:

>>> $posts = App\Post::with('author.profile')->get();
>>> $posts->map(function ($post) {
...     return $post->author->profile;
... });
Now, you should only have 3 queries total:

[2017-08-04 07:27:27] local.INFO: select * from `posts`
[2017-08-04 07:27:27] local.INFO: select * from `authors` where `authors`.`id` in (?, ?, ?, ?, ?) [1,2,3,4,5]
[2017-08-04 07:27:27] local.INFO: select * from `profiles` where `profiles`.`author_id` in (?, ?, ?, ?, ?) [1,2,3,4,5]
Lazy Eager Loading
You might only need to gather associated models based on a conditional. In this case, you can lazily invoke additional queries for related data:

php artisan tinker

>>> $posts = App\Post::all();
...
>>> $posts->load('author.profile');
>>> $posts->first()->author->profile;
...
You should see three queries total, but only if $posts->load() is called.
