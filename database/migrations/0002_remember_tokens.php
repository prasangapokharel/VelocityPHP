<?php
/**
 * Migration: 0002_remember_tokens
 * Creates the remember_tokens table for persistent "remember me" login.
 * Supports MySQL, PostgreSQL, and SQLite.
 */

use App\Database\Migration;

class Migration_0002_Remember_Tokens extends Migration
{
    public function up()
    {
        $this->createTable('remember_tokens', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('selector', 64)->unique();
            $table->string('hashed_validator', 255);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });

        // Add foreign key (skip on SQLite — no ALTER TABLE ADD CONSTRAINT support)
        if ($this->driver !== 'sqlite') {
            $this->table('remember_tokens', function ($table) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('CASCADE');
            });
        }
    }

    public function down()
    {
        $this->dropTable('remember_tokens');
    }
}
