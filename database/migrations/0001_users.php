<?php
/**
 * Migration: 0001_users
 * Creates the users table.
 * Supports MySQL, PostgreSQL, and SQLite.
 */

use App\Database\Migration;

class Migration_0001_Users extends Migration
{
    public function up()
    {
        $this->createTable('users', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->string('password', 255);
            $table->string('role', 50)->default('user');
            $table->string('status', 50)->default('active');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropTable('users');
    }
}
