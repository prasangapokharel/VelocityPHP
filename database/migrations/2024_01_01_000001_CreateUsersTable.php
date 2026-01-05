<?php
/**
 * Create Users Table Migration
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Database\Migrations;

use App\Database\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $this->createTable('users', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('active');
            $table->string('avatar', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        
        // Add indexes
        $this->execute("CREATE INDEX idx_users_email ON users (email)");
        $this->execute("CREATE INDEX idx_users_status ON users (status)");
    }
    
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->dropTable('users');
    }
}
