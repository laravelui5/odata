<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class Airline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->integer('gate')->nullable();
            $table->float('duration')->nullable();
        });

        Schema::create('airports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code');
            $table->date('construction_date')->nullable();
            $table->dateTime('sam_datetime')->nullable();
            $table->time('open_time')->nullable();
            $table->float('review_score')->nullable();
            $table->boolean('is_big')->nullable();
            $table->bigInteger('country_id')->nullable();
        });

        Schema::create('passengers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('flight_id')->nullable();
            $table->string('name');
            $table->dateTime('dob')->nullable();
            $table->float('age')->nullable();
            $table->boolean('chips')->nullable();
            $table->date('dq')->nullable();
            $table->float('in_role')->nullable();
            $table->time('open_time')->nullable();
            $table->unsignedBigInteger('colour')->nullable();
            $table->unsignedBigInteger('sock_colours')->nullable();
            $table->json('emails')->nullable();
        });

        // Regression fixture for cast discovery on models using the modern
        // `casts()` method. Every column here is typed so that the *database*
        // type and the *cast* disagree — so each assertion fails if casts are
        // not read.
        Schema::create('crews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('on_duty')->nullable();   // tinyint  → Int32  unless the cast wins
            $table->string('rank')->nullable();       // varchar  → String unless the cast wins
            $table->string('hired_at')->nullable();   // varchar  → String unless the cast wins
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('passenger_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
        });

        // Pivot table for Flight ↔ Airport (BelongsToMany).
        // Has its own auto-increment `id` to reproduce the column-name
        // collision edge case when using getQuery() on the relation.
        Schema::create('airport_flight', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('flight_id');
            $table->bigInteger('airport_id');
            $table->string('role')->default('stopover'); // origin, destination, stopover
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (['airport_flight', 'flights', 'airports', 'passengers', 'crews', 'pets', 'countries'] as $table) {
            Schema::drop($table);
        }
    }
}
